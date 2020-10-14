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

$GLOBALS['TL_DCA']['tl_wem_job'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_job_feed',
        'ctable' => ['tl_wem_job_application'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
        'onload_callback' => [
            [WEM\JobOffersBundle\DataContainer\JobContainer::class, 'updatePalettes'],
        ]
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['code ASC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [WEM\JobOffersBundle\DataContainer\JobContainer::class, 'listItems'],
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['toggle'],
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [WEM\JobOffersBundle\DataContainer\JobContainer::class, 'toggleIcon'],
                'showInHeader' => true,
            ],
            'applications' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['applications'],
                'href' => 'table=tl_wem_job_application',
                'icon' => 'folderOP.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},code,title,postedAt,availableAt;
            {location_legend},countries,locations;
            {details_legend},field,remuneration,status;
            {content_legend},text,file;
            {hr_legend},hrName,hrPosition,hrPhone,hrEmail,hrPicture;
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
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['createdAt'],
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'code' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['code'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => 3,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'postedAt' => [
            'exclude' => true,
            'sorting' => true,
            'flag' => 8,
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['postedAt'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'availableAt' => [
            'exclude' => true,
            'sorting' => true,
            'flag' => 8,
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['availableAt'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'countries' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['countries'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['multiple' => true, 'chosen' => true, 'wemjoboffers_isAvailableForAlerts' => true, 'wemjoboffers_availableForFilters' => true],
            'options_callback' => function () {
                return System::getCountries();
            },
            'sql' => 'blob NULL',
        ],
        'locations' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['locations'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'listWizard',
            'eval' => ['wemjoboffers_isAvailableForAlerts' => true],
            'sql' => 'blob NULL',
        ],

        'field' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['field'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'wemjoboffers_isAvailableForAlerts' => true, 'wemjoboffers_availableForFilters' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'remuneration' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['remuneration'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['status'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'wemjoboffers_availableForFilters' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'hrName' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['hrName'],
            'default' => BackendUser::getInstance()->name,
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrPosition' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['hrPosition'],
            'default' => '',
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrPhone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['hrPhone'],
            'default' => '',
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrEmail' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['hrEmail'],
            'default' => BackendUser::getInstance()->email,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'hrPicture' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['hrPicture'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr', 'extensions' => Config::get('validImageTypes')],
            'sql' => 'binary(16) NULL',
        ],

        'text' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['text'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => 'mediumtext NULL',
        ],
        'file' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['file'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],

        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['published'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['start'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job']['stop'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];
