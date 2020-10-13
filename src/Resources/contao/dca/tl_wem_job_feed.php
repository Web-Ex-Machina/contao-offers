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

$GLOBALS['TL_DCA']['tl_wem_job_feed'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_job', 'tl_wem_job_feed_attribute'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['title'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
            'attributes' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed']['attributes'],
                'href' => 'table=tl_wem_job_feed_attribute',
                'icon' => 'header.gif',
            ],
            'jobs' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed']['jobs'],
                'href' => 'table=tl_wem_job',
                'icon' => 'folderOP.gif',
            ]
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},title,alias;
            {attributes_legend},attributes;
            {alert_legend},ncEmailAlert,tplJobAlert
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
        'createdAt' => [
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'save_callback' => [
                [WEM\JobOffersBundle\DataContainer\JobFeedContainer::class, 'generateAlias'],
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],

        'attributes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['attributes'],
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_job_feed_attribute',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem-job-offers',
            ],
            'eval' => [
                'fields' => ['name', 'label', 'type'],
                'orderField' => 'name ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],

        'ncEmailAlert' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_feed']['ncEmailAlert'],
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => [WEM\JobOffersBundle\DataContainer\JobFeedContainer::class, 'getAlertEmailNotificationChoices'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tplJobAlert' => [
            'exclude'                 => true,
            'inputType'               => 'select',
            'options_callback' => static function ()
            {
                return Controller::getTemplateGroup('job_alert_');
            },
            'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
        ]
    ],
];
