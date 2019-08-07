<?php

/**
 * Prezioso Extension for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobslist']    = '{title_legend},name,headline,type;{config_legend},numberOfItems,skipFirst,perPage;{template_legend:hide},job_template,customTpl;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['job_template'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['job_template'],
    'default'                 => 'job_default',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_jobs', 'getJobsTemplates'),
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_module_jobs extends Backend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Return all news templates as array
     *
     * @return array
     */
    public function getJobsTemplates()
    {
        return $this->getTemplateGroup('job_');
    }
}
