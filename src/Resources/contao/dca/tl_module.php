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
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'offer_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offerslist'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feeds,offer_displayTeaser,offer_displayAttributes,offer_addFilters;
    {list_legend},numberOfItems,skipFirst,perPage;
    {form_legend},offer_applicationForm;
    {template_legend:hide},offer_template,customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersalert'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feed,offer_alertTeaser,offer_conditions,offer_pageGdpr,offer_pageSubscribe,offer_ncSubscribe,offer_pageUnsubscribe,offer_ncUnsubscribe;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['offer_addFilters'] = 'offer_filters,offer_addSearch';

$GLOBALS['TL_DCA']['tl_module']['fields']['offer_feed'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getFeeds'],
    'foreignKey' => 'tl_wem_offer_feed.title',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getFeeds'],
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
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getAlertsOptions'],
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
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getFiltersOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_conditions'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getConditionsOptions'],
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
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getTemplates'],
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
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getSubscribeNotificationChoices'],
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
    'options_callback' => [WEM\OffersBundle\DataContainer\ModuleContainer::class, 'getUnsubscribeNotificationChoices'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_displayAttributes'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
