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
        'wemjoboffers' => [
            'wem-job-offers' => [
                'tables' => ['tl_wem_job_feed', 'tl_wem_job', 'tl_wem_job_application', 'tl_wem_job_feed_attribute'],
            ],
            'wem-job-alerts' => [
                'tables' => ['tl_wem_job_alert', 'tl_wem_job_alert_condition'],
            ],
        ],
    ]
);

// Frontend modules
array_insert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem-job-offers' => [
            'jobslist' => 'WEM\JobOffersBundle\Module\ModuleJobOffersList',
            'jobsalert' => 'WEM\JobOffersBundle\Module\ModuleJobOffersAlert',
        ],
    ]
);

// Load icon in Contao 4.2 backend
if ('BE' === TL_MODE) {
    if (version_compare(VERSION, '4.4', '<')) {
        $GLOBALS['TL_CSS'][] = 'bundles/joboffers/backend/backend.css';
    } else {
        $GLOBALS['TL_CSS'][] = 'bundles/joboffers/backend/backend_svg.css';
    }
}

// Hooks
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [WEM\JobOffersBundle\Hooks\LoadDataContainerHook::class, 'addAttributesToJobDca'];
$GLOBALS['TL_HOOKS']['storeFormData'][] = [WEM\JobOffersBundle\Hooks\StoreFormDataHook::class, 'storeFormData'];
$GLOBALS['TL_HOOKS']['processFormData'][] = [WEM\JobOffersBundle\Hooks\ProcessFormDataHook::class, '__invoke'];

// Models
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Alert::getTable()] = 'WEM\JobOffersBundle\Model\Alert';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\AlertCondition::getTable()] = 'WEM\JobOffersBundle\Model\AlertCondition';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Job::getTable()] = 'WEM\JobOffersBundle\Model\Job';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\JobFeed::getTable()] = 'WEM\JobOffersBundle\Model\JobFeed';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\JobFeedAttribute::getTable()] = 'WEM\JobOffersBundle\Model\JobFeedAttribute';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Application::getTable()] = 'WEM\JobOffersBundle\Model\Application';

// Cronjobs
$GLOBALS['TL_CRON']['hourly'][] = [WEM\JobOffersBundle\Cronjob\SendAlertsJob::class, 'do'];

/*
 * Notification Center Notification Types
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'] = array_merge_recursive(
    (array) $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'],
    [
        'wem_joboffers' => [
            'wem_joboffers_alerts_email' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['jobfeed_*', 'recipient_*'],
                'email_text' => ['jobfeed_*', 'recipient_*', 'jobstext'],
                'email_html' => ['jobfeed_*', 'recipient_*', 'jobshtml'],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
            'wem_joboffers_alerts_subscribe' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['jobfeed_*', 'recipient_*'],
                'email_text' => ['jobfeed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_html' => ['jobfeed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
            'wem_joboffers_alerts_unsubscribe' => [
                'recipients' => ['recipient_email'],
                'email_subject' => ['jobfeed_*', 'recipient_*'],
                'email_text' => ['jobfeed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_html' => ['jobfeed_*', 'recipient_*', 'subscription_*', 'link_*'],
                'email_replyTo' => ['admin_email'],
                'email_sender_address' => ['admin_email'],
            ],
        ],
    ]
);
