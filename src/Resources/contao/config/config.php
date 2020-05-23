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

array_insert(
    $GLOBALS['BE_MOD']['content'],
    array_search('form', array_keys($GLOBALS['BE_MOD']['content']), true) + 1,
    [
        'wem-job-offers' => [
            'tables' => ['tl_wem_job', 'tl_wem_job_application'],
            'icon' => 'bundles/wem-job-offers/icon_jobs.png',
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

// Hooks
$GLOBALS['TL_HOOKS']['storeFormData'][] = ['WEM\JobOffersBundle\Hooks\StoreFormDataHook', 'storeFormData'];

// Models
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Job::getTable()] = 'WEM\JobOffersBundle\Model\Job';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Application::getTable()] = 'WEM\JobOffersBundle\Model\Application';
