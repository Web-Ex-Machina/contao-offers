<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use WEM\OffersBundle\Controller\Frontend\FiltersModuleController;
use WEM\OffersBundle\Controller\Frontend\ModuleController;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeedAttribute;
use WEM\OffersBundle\NotificationType\AlertSubscribeNotificationType;
use WEM\OffersBundle\NotificationType\AlertUnsubscribeNotificationType;
use WEM\UtilsBundle\Classes\StringUtil;

class ModuleContainer
{
    public function __construct(
        private readonly Connection $connection,
        private readonly NotificationCenter $notificationCenter,
        private readonly FinderFactory $finderFactory,
    ) {
    }

    /**
     * Return all offer templates as array.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_template.options')]
    public function getTemplates(): array
    {
        $options = [
            ...$this->finderFactory->create()->identifier(ModuleController::DEFAULT_OFFER_TEMPLATE)->withVariants()->asTemplateOptions(),
            ...$this->finderFactory->create()->identifier(ModuleController::DETAILS_OFFER_TEMPLATE)->withVariants()->asTemplateOptions(false),
        ];

        // Legacy PHP templates still present in the website
        foreach (Controller::getTemplateGroup('offer_') as $name => $label) {
            if (! str_starts_with($name, 'offer_alert_') && ! str_starts_with($name, 'offer_apply')) {
                $options[$name] = $label;
            }
        }

        return $options;
    }

    /**
     * Return all feeds as array.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_feed.options')]
    #[AsCallback(table: 'tl_module', target: 'fields.offer_feeds.options')]
    public function getFeeds(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT id, title FROM tl_wem_offer_feed ORDER BY title');
    }

    /**
     * Return all forms as array.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_applicationForm.options')]
    public function getForms(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT id, title FROM tl_form ORDER BY title');
    }

    /**
     * Return all alerts available gateways.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_alertsGateways.options')]
    public function getAlertsOptions(): array
    {
        return [
            'email' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['GATEWAY']['email'],
        ];
    }

    /**
     * Return the offer fields usable as alert conditions.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_conditions.options')]
    public function getConditionsOptions(): array
    {
        return $this->getOfferFieldsWithFlag('isAlertCondition');
    }

    /**
     * Return the offer fields usable as filters.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_filters.options')]
    public function getFiltersOptions(): array
    {
        return $this->getOfferFieldsWithFlag('isFilter');
    }

    #[AsCallback(table: 'tl_module', target: 'fields.offer_ncSubscribe.options')]
    public function getSubscribeNotificationChoices(): array
    {
        return $this->notificationCenter->getNotificationsForNotificationType(AlertSubscribeNotificationType::NAME);
    }

    #[AsCallback(table: 'tl_module', target: 'fields.offer_ncUnsubscribe.options')]
    public function getUnsubscribeNotificationChoices(): array
    {
        return $this->notificationCenter->getNotificationsForNotificationType(AlertUnsubscribeNotificationType::NAME);
    }

    /**
     * Return all offer attributes available.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_attributes.options')]
    public function getAttributesOptions(DataContainer|null $dc = null): array
    {
        $arrPids = StringUtil::deserialize($dc?->getCurrentRecord()['offer_feeds'] ?? null, true);
        $fields = [];

        foreach (OfferFeedAttribute::findItems($arrPids !== [] ? [
            'pid' => $arrPids,
        ] : []) ?? [] as $objAttribute) {
            $fields[$objAttribute->name] = $objAttribute->label ?: $objAttribute->name;
        }

        return $fields;
    }

    /**
     * Return all the filters modules.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.offer_filters_module.options')]
    public function getFiltersModules(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT id, name FROM tl_module WHERE type = ? ORDER BY name', [FiltersModuleController::TYPE]);
    }

    private function getOfferFieldsWithFlag(string $flag): array
    {
        Controller::loadDataContainer(Offer::getTable());
        $fields = [];

        foreach ($GLOBALS['TL_DCA'][Offer::getTable()]['fields'] as $k => $v) {
            if (true === ($v['eval'][$flag] ?? false)) {
                $fields[$k] = ($v['label'][0] ?? null) ?: $k;
            }
        }

        return $fields;
    }
}
