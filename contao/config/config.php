<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

use Contao\ArrayUtil;
use WEM\OffersBundle\DataContainer\OfferAlertContainer;
use WEM\OffersBundle\DataContainer\OfferApplicationContainer;
use WEM\OffersBundle\EventListener\PersonalDataManager\ManagerListener;
use WEM\OffersBundle\EventListener\PersonalDataManager\UiListener;
use WEM\OffersBundle\Model;

// Backend modules
ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    2,
    [
        'wemoffers' => [
            'wem-offers' => [
                'tables' => ['tl_wem_offer_feed', 'tl_wem_offer', 'tl_wem_offer_application', 'tl_wem_offer_feed_attribute', 'tl_content'],
                'sendNotificationToApplication' => [OfferApplicationContainer::class, 'sendNotificationToApplication'],
            ],
            'wem-offers-alerts' => [
                'tables' => ['tl_wem_offer_alert', 'tl_wem_offer_alert_condition'],
                'sendAlerts' => [OfferAlertContainer::class, 'sendAlerts'],
            ],
        ],
    ]
);

// Personal Data Manager UI
$GLOBALS['WEM_HOOKS']['renderSingleItemTitle'][] = [UiListener::class, 'renderSingleItemTitle'];
$GLOBALS['WEM_HOOKS']['buildSingleItemButtons'][] = [UiListener::class, 'buildSingleItemButtons'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyOriginalModelSingle'][] = [UiListener::class, 'renderSingleItemBodyOriginalModelSingle'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyOriginalModelSingleFieldValue'][] = [UiListener::class, 'renderSingleItemBodyOriginalModelSingleFieldValue'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyPersonalDataSingle'][] = [UiListener::class, 'renderSingleItemBodyPersonalDataSingle'];
$GLOBALS['WEM_HOOKS']['buildSingleItemBodyPersonalDataSingleButtons'][] = [UiListener::class, 'buildSingleItemBodyPersonalDataSingleButtons'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyPersonalDataSingleFieldLabel'][] = [UiListener::class, 'renderSingleItemBodyPersonalDataSingleFieldLabel'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyPersonalDataSingleFieldValue'][] = [UiListener::class, 'renderSingleItemBodyPersonalDataSingleFieldValue'];
$GLOBALS['WEM_HOOKS']['getHrefByPidAndPtableAndEmail'][] = [ManagerListener::class, 'getHrefByPidAndPtableAndEmail'];

// Models
$GLOBALS['TL_MODELS'][Model\Alert::getTable()] = Model\Alert::class;
$GLOBALS['TL_MODELS'][Model\AlertCondition::getTable()] = Model\AlertCondition::class;
$GLOBALS['TL_MODELS'][Model\Application::getTable()] = Model\Application::class;
$GLOBALS['TL_MODELS'][Model\Offer::getTable()] = Model\Offer::class;
$GLOBALS['TL_MODELS'][Model\OfferFeed::getTable()] = Model\OfferFeed::class;
$GLOBALS['TL_MODELS'][Model\OfferFeedAttribute::getTable()] = Model\OfferFeedAttribute::class;
