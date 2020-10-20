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

$this->loadDataContainer('tl_content');

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'job_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobslist'] = '
    {title_legend},name,headline,type;
    {config_legend},job_feeds,job_displayTeaser,job_addFilters;
    {list_legend},numberOfItems,skipFirst,perPage;
    {form_legend},job_applicationForm;
    {template_legend:hide},job_template,customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobsalert'] = '
    {title_legend},name,headline,type;
    {config_legend},job_feed,job_conditions,job_pageGdpr,job_pageSubscribe,job_ncSubscribe,job_pageUnsubscribe,job_ncUnsubscribe;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['job_addFilters'] = 'job_filters,job_addSearch';

$GLOBALS['TL_DCA']['tl_module']['fields']['job_feed'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_feed'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getJobFeeds'],
    'foreignKey' => 'tl_wem_job_feed.title',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_feeds'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_feeds'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getJobFeeds'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_displayTeaser'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_displayTeaser'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
// @todo add several gateways for alerts
$GLOBALS['TL_DCA']['tl_module']['fields']['job_alertsGateways'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_alertsGateways'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getJobAlertsOptions'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_addFilters'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed_alert']['job_addFilters'],
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_filters'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_filters'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getJobFiltersOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_conditions'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_conditions'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getJobConditionsOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_addSearch'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed_alert']['job_addSearch'],
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
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
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getJobsTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_pageGdpr'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_pageGdpr'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_pageSubscribe'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_pageSubscribe'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_ncSubscribe'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_ncSubscribe'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getSubscribeNotificationChoices'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_pageUnsubscribe'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_pageUnsubscribe'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['job_ncUnsubscribe'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['job_ncUnsubscribe'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\JobOffersBundle\DataContainer\ModuleContainer::class, 'getUnsubscribeNotificationChoices'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
