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
use NotificationCenter\Model\Notification;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer;

class SendAlerts
{
    /**
     * Retrieve and send all the new job offers matching user alerts.
     *
     * Executed every hour
     */
    public function do(): void
    {
        // Log the start of the job and setup some vars
        System::log('Cronjob SendAlerts started', __METHOD__, 'WEMOFFERS');

        $t = Alert::getTable();
        $t2 = AlertCondition::getTable();
        $t3 = Offer::getTable();
        $nbAlerts = 0;
        $nbOffers = 0;
        $arrFeedCache = [];
        $arrCache = [];

        // We need to retrieve the alerts depending on their frequency
        // hourly
        // or daily and lastJob < time - 1 day
        // or weekly and lastJob < time - 1 week
        // or monthly and lastJob < time - 1 month
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
        $objAlerts = Alert::findItems($c);

        // Quit the job if there is no alerts to retrieve
        if (!$objAlerts || 0 === $objAlerts->count()) {
            System::log('Nothing to send, abort !', __METHOD__, 'WEMOFFERS');

            return;
        }

        // Now, loop on the alerts and check if there is jobs matching its conditions
        while ($objAlerts->next()) {
            // Retrieve the alert conditions
            $objConditions = AlertCondition::findItems(['pid' => $objAlerts->id]);

            // It should not happen but hey. Expect the unexpected ಠ_ಠ
            if (!$objConditions || 0 === $objConditions->count()) {
                continue;
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

            // Format alert conditions for request
            // @todo > Take in consideration that we can have multiple values per field.
            // Ex : 2 cities for locations, 2 cities for country etc...
            while ($objConditions->next()) {
                $arrConditions[$objConditions->field] = $objConditions->value;
            }

            // Depending on frequency, adjust job time condition
            switch ($objAlerts->frequency) {
                case 'daily':
                    $arrConditions['where'][] = sprintf('%s.postedAt > %s', $t3, strtotime('-1 day'));
                    break;
                case 'weekly':
                    $arrConditions['where'][] = sprintf('%s.postedAt > %s', $t3, strtotime('-1 week'));
                    break;
                case 'monthly':
                    $arrConditions['where'][] = sprintf('%s.postedAt > %s', $t3, strtotime('-1 month'));
                    break;
                case 'hourly':
                default:
                    $arrConditions['where'][] = sprintf('%s.postedAt > %s', $t3, strtotime('-1 hour'));
            }

            // Retrieve items matching the conditions
            $objItems = Offer::findItems($arrConditions);

            // Skip if no items were found
            if (!$objItems || 0 === $objItems->count()) {
                continue;
            }

            // Prepare some tokens for notification
            $arrTokens = [];
            $arrBuffer = [];

            foreach ($objAlerts->row() as $k => $v) {
                $arrTokens['recipient_'.$k] = $v;
            }

            // Loop on the items, format everything and send the notification \o/
            while ($objItems->next()) {
                if (\array_key_exists($objItems->id, $arrCache)) {
                    $arrBuffer[] = $arrCache[$objItems->id];
                } else {
                    $arrBuffer[] = $this->parseItem($objItems->current(), $objFeed->tplAlertJob);
                }

                ++$nbOffers;
            }

            $arrTokens['offershtml'] = implode('', $arrBuffer);
            $arrTokens['offerstext'] = strip_tags($arrTokens['offershtml']);

            if ($objNotification = Notification::findByPk($objFeed->ncEmailAlert)) {
                ++$nbAlerts;
                $objNotification->send($arrTokens);
            }
        }

        // Step 5 - Log the results (how many alerts sents & how job offers sent)
        System::log(sprintf('Cronjob done, %s alerts and %s offers sent', $nbAlerts, $nbOffers), __METHOD__, 'WEMOFFERS');
    }

    /**
     * Format a job block for the notification.
     *
     * @param WEM\OffersBundle\Model\Offer $objItem
     * @param string                       $strTemplate
     *
     * @return string
     */
    protected function parseItem($objItem, $strTemplate = 'offer_alert_default')
    {
        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->setData($objItem->row());

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['parseArticles']) && \is_array($GLOBALS['TL_HOOKS']['parseArticles'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseArticles'] as $callback) {
                $this->import($callback[0]);
                $this->{$callback[0]}->{$callback[1]}($objTemplate, $objItem->row(), $this);
            }
        }

        return $objTemplate->parse();
    }
}
