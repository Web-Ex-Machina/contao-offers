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

use WEM\OffersBundle\DataContainer\OfferFeedAttributeContainer;

$GLOBALS['TL_DCA']['tl_wem_offer_feed_attribute'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_offer_feed',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['name ASC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [OfferFeedAttributeContainer::class, 'listItems'],
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type'],
        'default' => '
            {title_legend},name,label;
            {field_legend},type,mandatory;
            {design_legend},insertInDca,insertType,class
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'type_text' => 'value,isFilter,isAlertCondition',
        'type_textarea' => 'allowHtml,helpwizard,rte,explanation',
        'type_select' => 'options,multiple,chosen,isFilter,isAlertCondition',
        'type_picker' => 'fkey',
        'type_fileTree' => 'multiple,filesOnly,fieldType,extensions',
        'type_listWizard' => 'multiple,allowHtml,maxlength,isFilter,isAlertCondition',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'pid' => [
            'foreignKey' => 'tl_wem_offer_feed.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'name' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'fieldname', 'spaceToUnderscore' => true, 'maxlength' => 64, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'label' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'type' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [OfferFeedAttributeContainer::class, 'getFieldOptions'],
            'eval' => ['helpwizard' => true, 'submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'reference' => &$GLOBALS['TL_LANG']['CTE'],
            'sql' => ['name' => 'type', 'type' => 'string', 'length' => 64, 'default' => 'text'],
        ],
        'value' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'options' => [
            'exclude' => true,
            'inputType' => 'optionWizard',
            'eval' => ['mandatory' => true, 'allowHtml' => true],
            'sql' => 'blob NULL',
        ],
        'fkey' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'multiple' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'chosen' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'filesOnly' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'fieldType' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['radio', 'checkbox'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['name' => 'fieldType', 'type' => 'string', 'length' => 128, 'default' => 'radio'],
        ],
        'extensions' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'maxlength' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'allowHtml' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50 cbx'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'helpwizard' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50 cbx'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'mandatory' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'sql' => "char(1) NOT NULL default ''",
        ],
        'isFilter' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 cbx'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'isAlertCondition' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 cbx'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'insertInDca' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [OfferFeedAttributeContainer::class, 'getFieldsAndLegends'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['name' => 'insertInDca', 'type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'insertType' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['POSITION_BEFORE', 'POSITION_AFTER', 'POSITION_PREPEND', 'POSITION_APPEND'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['name' => 'insertType', 'type' => 'string', 'length' => 128, 'default' => 'POSITION_APPEND'],
        ],
        'class' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'rte' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'explanation' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ],
];
