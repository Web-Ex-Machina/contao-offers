<?php

/**
 * Prezioso Extension for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_jobslist' 	=> 'system/modules/prezioso/templates',
	'job_default' 	=> 'system/modules/prezioso/templates',
	'job_apply' 	=> 'system/modules/prezioso/templates',

	'mail_service'  => 'templates/emails',
));