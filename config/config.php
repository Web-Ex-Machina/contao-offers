<?php

/**
 * Prezioso Extension for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */



// Backend modules
array_insert($GLOBALS['BE_MOD'], 1, array
(
	'prezioso' => array
	(
		'pzl-jobs' => array
		(
			'tables'      => array('tl_pzl_job', 'tl_pzl_job_application'),
			'icon'        => 'system/modules/prezioso/assets/icon_jobs.png',
		)
	)
));

// Frontend modules
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'prezioso' => array
	(
		'jobslist'    => 'Prezioso\Module\ModuleJobsList',
	)
));

// Hooks
$GLOBALS['TL_HOOKS']['storeFormData'][] = array('Prezioso\Hooks', 'storeFormData');

// Models
$GLOBALS['TL_MODELS'][\Prezioso\Model\Job::getTable()] 				= 'Prezioso\Model\Job';
$GLOBALS['TL_MODELS'][\Prezioso\Model\Application::getTable()] 		= 'Prezioso\Model\Application';