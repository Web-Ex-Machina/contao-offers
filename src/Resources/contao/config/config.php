<?php

declare(strict_types=1);

/*
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

// Backend modules
use WEM\OffersBundle\DataContainer\OfferAlertContainer;
use WEM\OffersBundle\Module\ModuleOffersAlert;
use WEM\OffersBundle\Module\ModuleOffersList;
use WEM\OffersBundle\Module\ModuleOffersFilters;
use WEM\OffersBundle\Module\ModuleOffersReader;
use WEM\OffersBundle\Model;

Contao\ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    2,
    [
        'wemoffers' => [
            'wem-offers' => [
                'tables' => ['tl_wem_offer_feed', 'tl_wem_offer', 'tl_wem_offer_application', 'tl_wem_offer_feed_attribute', 'tl_content'],
                'sendNotificationToApplication' => [WEM\OffersBundle\DataContainer\OfferApplicationContainer::class, 'sendNotificationToApplication'],
            ],
            'wem-offers-alerts' => [
                'tables' => ['tl_wem_offer_alert', 'tl_wem_offer_alert_condition'],
                'sendAlerts' => static function () : void {
                    (new OfferAlertContainer())->sendAlerts();
                },
            ],
        ],
    ]
);

// Frontend modules
Contao\ArrayUtil::arrayInsert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem-offers' => [
            'offerslist' => ModuleOffersList::class,
            'offersfilters' => ModuleOffersFilters::class,
            'offersalert' => ModuleOffersAlert::class,
            'offersreader' => ModuleOffersReader::class,
        ],
    ]
);

// PDM UI
$GLOBALS['WEM_HOOKS']['renderSingleItemTitle'][] = ['offers.listener.personal_data_ui', 'renderSingleItemTitle'];
$GLOBALS['WEM_HOOKS']['buildSingleItemButtons'][] = ['offers.listener.personal_data_ui', 'buildSingleItemButtons'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyOriginalModelSingle'][] = ['offers.listener.personal_data_ui', 'renderSingleItemBodyOriginalModelSingle'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyOriginalModelSingleFieldValue'][] = ['offers.listener.personal_data_ui', 'renderSingleItemBodyOriginalModelSingleFieldValue'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyPersonalDataSingle'][] = ['offers.listener.personal_data_ui', 'renderSingleItemBodyPersonalDataSingle'];
$GLOBALS['WEM_HOOKS']['buildSingleItemBodyPersonalDataSingleButtons'][] = ['offers.listener.personal_data_ui', 'buildSingleItemBodyPersonalDataSingleButtons'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyPersonalDataSingleFieldLabel'][] = ['offers.listener.personal_data_ui', 'renderSingleItemBodyPersonalDataSingleFieldLabel'];
$GLOBALS['WEM_HOOKS']['renderSingleItemBodyPersonalDataSingleFieldValue'][] = ['offers.listener.personal_data_ui', 'renderSingleItemBodyPersonalDataSingleFieldValue'];
$GLOBALS['WEM_HOOKS']['getHrefByPidAndPtableAndEmail'][] = ['offers.listener.personal_data_manager', 'getHrefByPidAndPtableAndEmail'];

// Models
$GLOBALS['TL_MODELS'][Model\Alert::getTable()] = Model\Alert::class;
$GLOBALS['TL_MODELS'][Model\AlertCondition::getTable()] = Model\AlertCondition::class;
$GLOBALS['TL_MODELS'][Model\Application::getTable()] = Model\Application::class;
$GLOBALS['TL_MODELS'][Model\Offer::getTable()] = Model\Offer::class;
$GLOBALS['TL_MODELS'][Model\OfferFeed::getTable()] = Model\OfferFeed::class;
$GLOBALS['TL_MODELS'][Model\OfferFeedAttribute::getTable()] = Model\OfferFeedAttribute::class;

// Cronjobs
$GLOBALS['TL_CRON']['hourly'][] = [WEM\OffersBundle\Cronjob\SendAlerts::class, 'do'];

/*
 * Notification Center Notification Types
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'] = array_merge_recursive(
    (array) $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'],
    [
        'wem_offers' => [
            'wem_offers_alerts_email' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['feed_*', 'recipient_*'],
                'email_text' => ['feed_*', 'recipient_*', 'offerstext','link_*'],
                'email_html' => ['feed_*', 'recipient_*', 'offershtml','link_*'],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
            'wem_offers_alerts_subscribe' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['feed_*', 'recipient_*'],
                'email_text' => ['feed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_html' => ['feed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
            'wem_offers_alerts_unsubscribe' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['feed_*', 'recipient_*'],
                'email_text' => ['feed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_html' => ['feed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
            'wem_offers_answer_to_application' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['feed_*', 'offer_*', 'recipient_*'],
                'email_text' => ['feed_*', 'offer_*', 'recipient_*',],
                'email_html' => ['feed_*', 'offer_*', 'recipient_*',],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
        ],
    ]
);
