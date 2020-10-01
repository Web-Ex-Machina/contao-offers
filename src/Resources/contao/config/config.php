<?php

declare(strict_types=1);

/**
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
        'wem-job-offers' => [
            'wem-job-offers' => [
                'tables' => ['tl_wem_job_feed', 'tl_wem_job', 'tl_wem_job_application'],
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
            'jobslist' => 'WEM\JobOffersBundle\Module\ModuleJobsList',
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
$GLOBALS['TL_HOOKS']['storeFormData'][] = ['WEM\JobOffersBundle\Hooks\StoreFormDataHook', 'storeFormData'];

// Models
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Job::getTable()] = 'WEM\JobOffersBundle\Model\Job';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Application::getTable()] = 'WEM\JobOffersBundle\Model\Application';
