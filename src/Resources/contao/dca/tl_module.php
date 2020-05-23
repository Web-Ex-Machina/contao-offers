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

$this->loadDataContainer('tl_content');

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobslist'] = '
    {title_legend},name,headline,type;
    {config_legend},job_displayTeaser,job_applicationForm;numberOfItems,skipFirst,perPage;
    {template_legend:hide},job_template,customTpl;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['fields']['job_displayTeaser'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_displayTeaser'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_applicationForm'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_applicationForm'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_content', 'getForms'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50 wizard'],
    'wizard' => [
        ['tl_content', 'editForm'],
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_template'],
    'default' => 'job_default',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_jobs', 'getJobsTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_module_jobs extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Return all news templates as array.
     *
     * @return array
     */
    public function getJobsTemplates()
    {
        return $this->getTemplateGroup('job_');
    }
}
