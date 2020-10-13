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

$GLOBALS['TL_DCA']['tl_wem_job_alert'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_job_alert_condition'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['name', 'email'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['name', 'email'],
            'format' => '%s - %s',
            'label_callback' => [WEM\JobOffersBundle\DataContainer\JobAlertContainer::class, 'listItems'],
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {recipient_legend},name,position,phone,email;
            {alert_legend},feed,frequency,sendViaEmail;
            {filters_legend},conditions
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
        'lastJob' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['createdAt'],
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'name' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['name'],
            'default' => BackendUser::getInstance()->name,
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'position' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['position'],
            'default' => '',
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['phone'],
            'default' => '',
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['email'],
            'default' => BackendUser::getInstance()->email,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'feed' => [
            'label'                     => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['feed'],
            'exclude'                   => true,
            'inputType'                 => 'select',
            'options_callback'          => array(WEM\JobOffersBundle\DataContainer\JobAlertContainer::class, 'getJobFeeds'),
            'eval'                      => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                       => "int(10) unsigned NOT NULL default '0'"
        ],
        'frequency' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['frequency'],
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['hourly', 'daily', 'weekly', 'monthly'],
            'reference' => $GLOBALS['TL_LANG']['tl_wem_job_alert']['frequency'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'sendViaEmail' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['sendViaEmail'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],

        'conditions' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_alert']['conditions'],
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_job_alert_condition',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem-job-alerts',
            ],
            'eval' => [
                'fields' => ['field', 'value'],
                'orderField' => 'field ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],
    ],
];
