<?php

// Backend modules
array_insert(
    $GLOBALS['BE_MOD']['content'],
    array_search('form', array_keys($GLOBALS['BE_MOD']['content'])) + 1,
    array(
        'wem-job-offers' => array(
            'tables'      => array('tl_wem_job', 'tl_wem_job_application'),
            'icon'        => 'bundles/wem-job-offers/icon_jobs.png',
        )
    )
);

// Frontend modules
array_insert(
    $GLOBALS['FE_MOD'],
    2,
    array(
        'wem-job-offers' => array(
            'jobslist'    => 'WEM\JobOffersBundle\Module\ModuleJobsList',
        )
    )
);

// Hooks
$GLOBALS['TL_HOOKS']['storeFormData'][] = array('WEM\JobOffersBundle\Hooks\StoreFormDataHook', 'storeFormData');

// Models
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Job::getTable()]               = 'WEM\JobOffersBundle\Model\Job';
$GLOBALS['TL_MODELS'][\WEM\JobOffersBundle\Model\Application::getTable()]       = 'WEM\JobOffersBundle\Model\Application';
