<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Cronjob;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use WEM\OffersBundle\Controller\Frontend\AlertModuleController;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeed;
use WEM\OffersBundle\Model\OfferFeedAttribute;
use WEM\OffersBundle\Twig\TemplateRenderer;

#[AsCronJob('hourly')]
class SendAlerts
{
    public const DEFAULT_TEMPLATE = 'offer_alert/default';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly NotificationCenter $notificationCenter,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly LoggerInterface $logger,
        private readonly TemplateRenderer $templateRenderer,
    ) {
    }

    public function __invoke(): void
    {
        $this->do();
    }

    /**
     * Send the alerts matching new offers.
     *
     * @param bool $blnUpdateAlertLastJob Whether to update the lastJob field of the alerts (and only send the alerts due)
     *
     * @throws \Exception
     */
    public function do(bool $blnUpdateAlertLastJob = true): void
    {
        $this->framework->initialize();
        $this->logger->info('Cronjob SendAlerts started');

        $t = Alert::getTable();
        $t3 = Offer::getTable();
        $nbAlerts = 0;
        $nbOffers = 0;

        // Retrieve the alerts depending on their frequency
        $c = [
            'active' => 1,
        ];

        if ($blnUpdateAlertLastJob) {
            $c['where'] = [\sprintf(
                "(%1\$s.frequency = 'hourly' OR (%1\$s.frequency = 'daily' AND %1\$s.lastJob < %2\$d) OR (%1\$s.frequency = 'weekly' AND %1\$s.lastJob < %3\$d) OR (%1\$s.frequency = 'monthly' AND %1\$s.lastJob < %4\$d))",
                $t,
                strtotime('-1 day'),
                strtotime('-1 week'),
                strtotime('-1 month'),
            )];
        }

        $objAlerts = Alert::findItems($c, 0, 0, [
            'order' => 'language ASC, moduleOffersAlert ASC',
        ]);

        if (! $objAlerts instanceof \Contao\Model\Collection || $objAlerts->count() === 0) {
            $this->logger->info('Cronjob SendAlerts: nothing to send');

            return;
        }

        $arrCache = [];

        /** @var Alert $objAlert */
        foreach ($objAlerts as $objAlert) {
            $objFeed = OfferFeed::findById($objAlert->feed);

            if (! $objFeed instanceof OfferFeed || ! $objFeed->ncEmailAlert) {
                continue;
            }

            // Setup default conditions
            // Do not filter on the current request locale, alerts gather the offers of every language
            $arrConditions = [
                'pid' => $objFeed->id,
                'published' => 1,
                'lang' => null,
                'where' => [],
            ];

            // @todo > Take in consideration that we can have multiple values per field.
            foreach (AlertCondition::findItems([
                'pid' => $objAlert->id,
            ]) ?? [] as $objCondition) {
                $arrConditions[$objCondition->field] = $objCondition->value;
            }

            // Depending on frequency, adjust offer time condition
            $arrConditions['where'][] = \sprintf('%s.date > %d', $t3, strtotime(match ($objAlert->frequency) {
                'daily' => '-1 day',
                'weekly' => '-1 week',
                'monthly' => '-1 month',
                default => '-1 hour',
            }));

            $objItems = Offer::findItems($arrConditions);

            if (! $objItems instanceof \Contao\Model\Collection || $objItems->count() === 0) {
                continue;
            }

            $arrTokens = [
                'admin_email' => Config::get('adminEmail'),
            ];

            foreach ($objFeed->row() as $k => $v) {
                $arrTokens['feed_' . $k] = $v;
            }

            foreach ($objAlert->row() as $k => $v) {
                $arrTokens['recipient_' . $k] = $v;
            }

            $arrBuffer = [];

            /** @var Offer $objItem */
            foreach ($objItems as $objItem) {
                $arrBuffer[] = $arrCache[$objAlert->language][$objItem->id] ??= $this->parseItem($objItem, (string) $objAlert->language, $objFeed->tplOfferAlert ?: self::DEFAULT_TEMPLATE);
                ++$nbOffers;
            }

            $arrTokens['offershtml'] = implode('<hr>', $arrBuffer);
            $arrTokens['offerstext'] = strip_tags($arrTokens['offershtml']);
            $arrTokens['link_unsubscribe'] = $this->getUnsubscribeLink($objAlert);

            $this->notificationCenter->sendNotification((int) $objFeed->ncEmailAlert, $arrTokens, $objAlert->language ?: null);
            ++$nbAlerts;

            if ($blnUpdateAlertLastJob) {
                $objAlert->lastJob = time();
                $objAlert->save();
            }
        }

        $this->logger->info(\sprintf('Cronjob SendAlerts done, %d alerts and %d offers sent', $nbAlerts, $nbOffers));
    }

    private function getUnsubscribeLink(Alert $objAlert): string
    {
        $objModule = ModuleModel::findById($objAlert->moduleOffersAlert) ?? ModuleModel::findOneByType(AlertModuleController::TYPE);
        $objPage = $objModule?->offer_pageUnsubscribe ? PageModel::findById($objModule->offer_pageUnsubscribe) : null;

        if (! $objPage instanceof PageModel) {
            return '';
        }

        return $this->contentUrlGenerator->generate($objPage, [], UrlGeneratorInterface::ABSOLUTE_URL) . '?wem_action=unsubscribe&token=' . $objAlert->token;
    }

    /**
     * Parse an offer using the alert template.
     *
     * @throws \Exception
     */
    private function parseItem(Offer $objItem, string $language, string $strTemplate): string
    {
        System::loadLanguageFile(OfferFeed::getTable(), $language);
        System::loadLanguageFile(OfferFeedAttribute::getTable(), $language);
        System::loadLanguageFile(Offer::getTable(), $language);

        return $this->templateRenderer->render($strTemplate, [
            ...$objItem->row(),
            'model' => $objItem,
            'date' => Date::parse(Config::get('dateFormat'), (int) $objItem->date),
            'attributes' => $objItem->getAttributesFull(),
            'language' => $language,
        ]);
    }
}
