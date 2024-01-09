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

$GLOBALS['TL_DCA']['tl_wem_offer'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_offer_feed',
        'ctable' => ['tl_wem_offer_application', 'tl_content'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
        'onload_callback' => [
            [WEM\OffersBundle\DataContainer\OfferContainer::class, 'updatePalettes'],
        ]
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['code ASC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [WEM\OffersBundle\DataContainer\OfferContainer::class, 'listItems'],
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
                'href' => 'table=tl_content',
                'icon' => 'edit.svg'
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg'
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
            'toggle' => [
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [WEM\OffersBundle\DataContainer\OfferContainer::class, 'toggleIcon'],
                'showInHeader' => true,
            ],
            'applications' => [
                'href' => 'table=tl_wem_offer_application',
                'icon' => 'folderOP.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},code,title,postedAt,availableAt;
            {location_legend},countries,locations;
            {content_legend},text,file;
            {hr_legend},hrName,hrPosition,hrPhone,hrEmail;
            {publish_legend},published,start,stop
        ',
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
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'code' => [
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => 3,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'postedAt' => [
            'exclude' => true,
            'default' => time(),
            'sorting' => true,
            'flag' => 8,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'availableAt' => [
            'exclude' => true,
            'default' => time(),
            'sorting' => true,
            'flag' => 8,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'countries' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['multiple' => true, 'chosen' => true, 'wemoffers_isAvailableForAlerts' => true, 'wemoffers_isAvailableForFilters' => true],
            'options_callback' => function () {
                return System::getCountries();
            },
            'sql' => 'blob NULL',
        ],
        'locations' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'listWizard',
            'eval' => ['wemoffers_isAvailableForAlerts' => true],
            'sql' => 'blob NULL',
        ],

        'hrName' => [
            'default' => BackendUser::getInstance()->name,
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrPosition' => [
            'default' => '',
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrPhone' => [
            'default' => '',
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrEmail' => [
            'default' => BackendUser::getInstance()->email,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'text' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => 'mediumtext NULL',
        ],
        'file' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],

        'published' => [
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];
