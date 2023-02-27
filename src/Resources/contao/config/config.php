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
array_insert(
    $GLOBALS['BE_MOD'],
    2,
    [
        'wemoffers' => [
            'wem-offers' => [
                'tables' => ['tl_wem_offer_feed', 'tl_wem_offer', 'tl_wem_offer_application', 'tl_wem_offer_feed_attribute'],
            ],
            'wem-alerts' => [
                'tables' => ['tl_wem_offer_alert', 'tl_wem_offer_alert_condition'],
            ],
        ],
    ]
);

// Frontend modules
array_insert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem-offers' => [
            'offerslist' => 'WEM\OffersBundle\Module\ModuleOffersList',
            'offersalert' => 'WEM\OffersBundle\Module\ModuleOffersAlert',
        ],
    ]
);

// Hooks
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [WEM\OffersBundle\Hooks\LoadDataContainerHook::class, 'addAttributesToJobDca'];
$GLOBALS['TL_HOOKS']['storeFormData'][] = [WEM\OffersBundle\Hooks\StoreFormDataHook::class, 'storeFormData'];
$GLOBALS['TL_HOOKS']['processFormData'][] = [WEM\OffersBundle\Hooks\ProcessFormDataHook::class, '__invoke'];

// Models
$GLOBALS['TL_MODELS'][WEM\OffersBundle\Model\Alert::getTable()] = WEM\OffersBundle\Model\Alert::class;
$GLOBALS['TL_MODELS'][WEM\OffersBundle\Model\AlertCondition::getTable()] = WEM\OffersBundle\Model\AlertCondition::class;
$GLOBALS['TL_MODELS'][WEM\OffersBundle\Model\Application::getTable()] = WEM\OffersBundle\Model\Application::class;
$GLOBALS['TL_MODELS'][WEM\OffersBundle\Model\Offer::getTable()] = WEM\OffersBundle\Model\Offer::class;
$GLOBALS['TL_MODELS'][WEM\OffersBundle\Model\OfferFeed::getTable()] = WEM\OffersBundle\Model\OfferFeed::class;
$GLOBALS['TL_MODELS'][WEM\OffersBundle\Model\OfferFeedAttribute::getTable()] = WEM\OffersBundle\Model\OfferFeedAttribute::class;

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
                'email_text' => ['feed_*', 'recipient_*', 'offerstext'],
                'email_html' => ['feed_*', 'recipient_*', 'offershtml'],
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
        ],
    ]
);
