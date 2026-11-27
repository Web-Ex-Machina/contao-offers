<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'offer_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'offer_displayAttributes';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersalert'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feed,offer_alertTeaser,offer_conditions,offer_pageGdpr,offer_pageSubscribe,offer_ncSubscribe,offer_pageUnsubscribe,offer_ncUnsubscribe;
    {template_legend:collapsed},customTpl;
    {expert_legend:collapsed},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersfilters'] = '
    {title_legend},name,headline,type;
    {config_legend},jumpTo,offer_feeds,offer_filters,offer_addSearch;
    {template_legend:collapsed},customTpl;
    {expert_legend:collapsed},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offerslist'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feeds,offer_displayTeaser,offer_displayAttributes,offer_addFilters;
    {list_legend},numberOfItems,skipFirst,perPage;
    {form_legend},offer_applicationForm;
    {template_legend:collapsed},offer_template,customTpl;
    {expert_legend:collapsed},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offersreader'] = '
    {title_legend},name,headline,type;
    {config_legend},offer_feeds,offer_displayAttributes,overviewPage,customLabel;
    {form_legend},offer_applicationForm,offer_applicationFormDisplay;
    {template_legend:collapsed},offer_template,customTpl;
    {image_legend:collapsed},imgSize;
    {protected_legend:collapsed},protected;
    {expert_legend:collapsed},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['offer_addFilters'] = 'offer_filters_module';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['offer_displayAttributes'] = 'offer_attributes';

$GLOBALS['TL_DCA']['tl_module']['fields']['offer_feed'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_wem_offer_feed.title',
    'eval' => [
        'mandatory' => true,
    ],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => true,
        'mandatory' => true,
    ],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_displayTeaser'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'doNotCopy' => true,
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_alertTeaser'] = [
    'exclude' => true,
    'search' => true,
    'inputType' => 'textarea',
    'eval' => [
        'rte' => 'tinyMCE',
        'helpwizard' => true,
        'tl_class' => 'clr',
    ],
    'explanation' => 'insertTags',
    'sql' => 'mediumtext NULL',
];
// @todo add several gateways for alerts
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_alertsGateways'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => true,
        'mandatory' => true,
    ],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_addFilters'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'doNotCopy' => true,
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_filters'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'multiple' => true,
        'mandatory' => true,
        'tl_class' => 'w50',
    ],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_conditions'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'multiple' => true,
        'tl_class' => 'w50',
    ],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_addSearch'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'doNotCopy' => true,
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_applicationForm'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_form.title',
    'eval' => [
        'includeBlankOption' => true,
        'chosen' => true,
        'tl_class' => 'w50',
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_template'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'tl_class' => 'w50',
    ],
    'sql' => "varchar(128) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_pageGdpr'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => [
        'fieldType' => 'radio',
        'tl_class' => 'clr',
    ],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_pageSubscribe'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => [
        'fieldType' => 'radio',
        'tl_class' => 'clr',
    ],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_ncSubscribe'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'includeBlankOption' => true,
        'chosen' => true,
        'tl_class' => 'w50',
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_pageUnsubscribe'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => [
        'fieldType' => 'radio',
        'tl_class' => 'clr',
    ],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_ncUnsubscribe'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'includeBlankOption' => true,
        'chosen' => true,
        'tl_class' => 'w50',
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_displayAttributes'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'doNotCopy' => true,
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_attributes'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'multiple' => true,
        'mandatory' => true,
        'tl_class' => 'w50',
    ],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_filters_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_module.name',
    'eval' => [
        'mandatory' => true,
    ],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['offer_applicationFormDisplay'] = [
    'exclude' => true,
    'default' => 'inPage',
    'inputType' => 'select',
    'options' => ['inPage', 'modal'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['offer_applicationFormDisplay'],
    'eval' => [
        'tl_class' => 'w50',
    ],
    'sql' => "varchar(16) NOT NULL default ''",
];
