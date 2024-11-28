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

use Contao\BackendUser;
use Contao\System;
use WEM\OffersBundle\DataContainer\OfferAlertContainer;

$GLOBALS['TL_DCA']['tl_wem_offer_alert'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_offer_alert_condition'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['activatedAt'],
            'flag' => 12,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['email','feed','frequency','lastJob','activatedAt'],
            'format' => '%s - %s',
            'showColumns' => true,
            'label_callback' => [OfferAlertContainer::class, 'listItems'],
        ],
        'global_operations' => [
            'sendAlerts' => [
                'href' => 'key=sendAlerts',
                'class' => 'header_css_import',
            ],
            'all' => [
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
        'default' => '
            {recipient_legend},email,activatedAt;
            {alert_legend},feed,frequency,language,moduleOffersAlert;
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
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'token' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'email' => [
            'default' => BackendUser::getInstance()->email,
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'activatedAt' => [
            'default' => 0,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'language'=>[
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['chosen' => true, 'tl_class' => 'w50'],
            'options_callback' => fn() => System::getContainer()->get('contao.intl.locales')->getLocales(),
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'moduleOffersAlert'=>[
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options_callback' => [OfferAlertContainer::class, 'getOffersAlertModules'],
            'eval' => ['chosen' => true, 'tl_class' => 'w50'],
            'foreignKey' => 'tl_module.name',
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
        ],
        'feed' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options_callback' => [OfferAlertContainer::class, 'getFeeds'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'foreignKey' => 'tl_wem_offer_feed.title',
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
        ],
        'frequency' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => ['hourly', 'daily', 'weekly', 'monthly'],
            'reference' => $GLOBALS['TL_LANG']['tl_wem_offer_alert']['frequency'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],

        'conditions' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_offer_alert_condition',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem-offers-alerts',
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
