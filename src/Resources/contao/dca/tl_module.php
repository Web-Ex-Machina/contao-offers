<?php

declare(strict_types=1);
use WEM\OffersBundle\DataContainer\ModuleContainer;
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
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'offer_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'offer_displayAttributes';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersalert'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feed,offer_alertTeaser,offer_conditions,offer_pageGdpr,offer_pageSubscribe,offer_ncSubscribe,offer_pageUnsubscribe,offer_ncUnsubscribe;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersfilters'] = '
    {title_legend},name,headline,type;
    {config_legend},jumpTo,offer_feeds,offer_filters,offer_addSearch;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offerslist'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feeds,offer_displayTeaser,offer_displayAttributes,offer_addFilters;
    {list_legend},numberOfItems,skipFirst,perPage;
    {form_legend},offer_applicationForm;
    {template_legend:hide},offer_template,customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersreader'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feeds,offer_displayAttributes,overviewPage,customLabel;
    {form_legend},offer_applicationForm,offer_applicationFormDisplay;
    {template_legend:hide},offer_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['offer_addFilters'] = 'offer_filters_module';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['offer_displayAttributes'] = 'offer_attributes';

$GLOBALS['TL_DCA']['tl_module']['fields']['offer_feed'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getFeeds'],
    'foreignKey' => 'tl_wem_offer_feed.title',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [ModuleContainer::class, 'getFeeds'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_displayTeaser'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_alertTeaser'] = [
    'exclude' => true,
    'search' => true,
    'inputType' => 'textarea',
    'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
    'explanation' => 'insertTags',
    'sql' => 'mediumtext NULL',
];
// @todo add several gateways for alerts
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_alertsGateways'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [ModuleContainer::class, 'getAlertsOptions'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_addFilters'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_filters'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getFiltersOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_conditions'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getConditionsOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_addSearch'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_applicationForm'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_content', 'getForms'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50 wizard'],
    'wizard' => [
        ['tl_content', 'editForm'],
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_template'] = [
    'default' => 'offer_default',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_pageGdpr'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_pageSubscribe'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_ncSubscribe'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getSubscribeNotificationChoices'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_pageUnsubscribe'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_ncUnsubscribe'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getUnsubscribeNotificationChoices'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_displayAttributes'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_attributes'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getAttributesOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_filters_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getFiltersModules'],
    'foreignKey' => 'tl_module.name',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_applicationFormDisplay'] = [
    'exclude' => true,
    'default' => 'inPage',
    'inputType' => 'select',
    'options' => ['inPage', 'modal'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['offer_applicationFormDisplay'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];