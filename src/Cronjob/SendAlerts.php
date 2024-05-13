<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2019-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\OffersBundle\Cronjob;

use Contao\FrontendTemplate;
use Contao\System;
use Contao\ModuleModel;
use Contao\PageModel;
use NotificationCenter\Model\Notification;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer;
use Psr\Log\LoggerInterface;
use WEM\OffersBundle\Model\OfferFeed;
use WEM\OffersBundle\Model\OfferFeedAttribute;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class SendAlerts
{

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Retrieve and send all the new job offers matching user alerts.
     *
     * Executed every hour
     * @throws \Exception
     */
    public function do($blnUpdateAlertLastJob = true): void
    {
        // Log the start of the job and setup some vars
        $this->logger->log("WEMOFFERS",'Cronjob SendAlerts started');

        $t = Alert::getTable();
        AlertCondition::getTable(); //TODO : Not used ?
        $t3 = Offer::getTable();
        $nbAlerts = 0;
        $nbOffers = 0;

        // We need to retrieve the alerts depending on their frequency
        // hourly
        // or daily and lastJob < time - 1 day
        // or weekly and lastJob < time - 1 week
        // or monthly and lastJob < time - 1 month
        $c = ['active'=>1];

        if ($blnUpdateAlertLastJob) {
            $arrWhere = [];
            $arrWhere[] = sprintf(
                "(
                    $t.frequency = 'hourly'
                    OR ($t.frequency = 'daily' AND $t.lastJob < %s)
                    OR ($t.frequency = 'weekly' AND $t.lastJob < %s)
                    OR ($t.frequency = 'monthly' AND $t.lastJob < %s)
                )",
                strtotime('-1 day'),
                strtotime('-1 week'),
                strtotime('-1 month'),
            );
            $c['where'] = $arrWhere;
        }

        $objAlerts = Alert::findItems($c, 0, 0, ['order'=>'language ASC, moduleOffersAlert ASC']);

        // Quit the job if there is no alerts to retrieve
        if (!$objAlerts || 0 === $objAlerts->count()) {
            $this->logger->log("WEMOFFERS",'Nothing to send, abort !');
            return;
        }

        $arrCache = [];
        $arrFeedCache = [];

        // Now, loop on the alerts and check if there is jobs matching its conditions
        while ($objAlerts->next()) {
            if (!array_key_exists($objAlerts->language, $arrCache)){
                $arrCache[$objAlerts->language] = [];
            }

            // Retrieve the feed linked
            if (\array_key_exists($objAlerts->feed, $arrFeedCache)) {
                $objFeed = $arrFeedCache[$objAlerts->feed]['model'];
            } else {
                $objFeed = $objAlerts->getRelated('feed');
                $arrFeedCache[$objAlerts->feed]['model'] = $objFeed;
                $arrFeedCache[$objAlerts->feed]['tokens'] = [];

                // Format and store tokens
                foreach ($objFeed->row() as $k => $v) {
                    $arrFeedCache[$objAlerts->feed]['tokens']['offersfeed_'.$k] = $v;
                }
            }

            // Setup default conditions
            $arrConditions = [];
            $arrConditions['pid'] = $objFeed->id;
            $arrConditions['published'] = 1;
            $arrConditions['where'] = [];

            // Retrieve the alert conditions
            $objConditions = AlertCondition::findItems(['pid' => $objAlerts->id]);

            // Format alert conditions for request
            // @todo > Take in consideration that we can have multiple values per field.
            // Ex : 2 cities for locations, 2 cities for country etc...
            if ($objConditions && 0 < $objConditions->count()) {
                while ($objConditions->next()) {
                    $arrConditions[$objConditions->field] = $objConditions->value;
                }
            }

            // Depending on frequency, adjust job time condition
            switch ($objAlerts->frequency) {
                case 'daily':
                    $arrConditions['where'][] = sprintf('%s.date > %s', $t3, strtotime('-1 day'));
                    break;
                case 'weekly':
                    $arrConditions['where'][] = sprintf('%s.date > %s', $t3, strtotime('-1 week'));
                    break;
                case 'monthly':
                    $arrConditions['where'][] = sprintf('%s.date > %s', $t3, strtotime('-1 month'));
                    break;
                case 'hourly':
                default:
                    $arrConditions['where'][] = sprintf('%s.date > %s', $t3, strtotime('-1 hour'));
            }

            // Retrieve items matching the conditions
            $objItems = Offer::findItems($arrConditions);

            // Skip if no items were found
            if (!$objItems || 0 === $objItems->count()) {
                continue;
            }

            // Prepare some tokens for notification
            $arrTokens = [];
            $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];
            $arrBuffer = [];

            foreach ($objAlerts->row() as $k => $v) {
                $arrTokens['recipient_'.$k] = $v;
            }

            // Loop on the items, format everything and send the notification \o/
            while ($objItems->next()) {
                if (is_array($arrCache[$objAlerts->language]) && \array_key_exists($objItems->id, $arrCache[$objAlerts->language])) {
                    $arrBuffer[] = $arrCache[$objAlerts->language][$objItems->id];
                } else {
                    $bufferTmp = $this->parseItem($objItems->current(), $objAlerts->language, $objFeed->tplOfferAlert);
                    $arrBuffer[] = $bufferTmp;
                    $arrCache[$objAlerts->language][$objItems->id] = $bufferTmp;
                }

                ++$nbOffers;
            }

            $arrTokens['offershtml'] = implode('<hr>', $arrBuffer);
            $arrTokens['offerstext'] = strip_tags($arrTokens['offershtml']);

            $arrTokens['link_unsubscribe'] = '';
            $objModuleOffersAlert = $objAlerts->getRelated('moduleOffersAlert');
            if (!$objModuleOffersAlert) {
                $objModuleOffersAlert = ModuleModel::findBy('type', 'offersalert');
            }

            if ($objModuleOffersAlert) {
                $objPageUnsubscribe = PageModel::findByPk($objModuleOffersAlert->offer_pageUnsubscribe);
                $arrTokens['link_unsubscribe'] = $objPageUnsubscribe->getAbsoluteUrl().'?wem_action=unsubscribe&token='.$objAlerts->token;
            }

            if ($objNotification = Notification::findByPk($objFeed->ncEmailAlert)) {
                ++$nbAlerts;
                $objNotification->send($arrTokens, $objAlerts->language);

                if ($blnUpdateAlertLastJob) {
                    $objAlert = $objAlerts->current();
                    $objAlert->lastJob = time();
                    $objAlert->save();
                }
            }
        }

        // Step 5 - Log the results (how many alerts sents & how job offers sent)
        $this->logger->log("WEMOFFERS",'Cronjob done, {nbAlerts} alerts and {nbOffers} offers sent',[
            "nbAlerts"=>$nbAlerts,
            "nbOffers"=>$nbOffers
        ]);
    }

    /**
     * Format a job block for the notification.
     *
     *
     */
    protected function parseItem(Offer $objItem, string $language, string $strTemplate = 'offer_alert_default'): string
    {
        System::loadLanguageFile(OfferFeed::getTable(),$language);
        System::loadLanguageFile(OfferFeedAttribute::getTable(),$language);
        System::loadLanguageFile(Offer::getTable(),$language);

        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->setData($objItem->row());

        $objTemplate->date = \Contao\Date::parse(\Contao\Config::get('dateFormat'), (int) $objItem->date);
        $objTemplate->attributes = $objItem->getAttributesFull();
        $objTemplate->language = $language;

        return $objTemplate->parse();
    }
}
