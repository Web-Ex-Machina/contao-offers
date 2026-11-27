<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

use Contao\DataContainer;

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
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['activatedAt'],
            'flag' => DataContainer::SORT_DESC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['email', 'feed', 'frequency', 'lastJob', 'activatedAt'],
            'format' => '%s - %s',
            'showColumns' => true,
        ],
        'global_operations' => [
            'sendAlerts' => [
                'href' => 'key=sendAlerts',
                'icon' => 'sync.svg',
            ],
            'all',
        ],
        'operations' => [
            'edit',
            'children',
            'copy',
            'delete',
            'show',
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {recipient_legend},email;
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
            'flag' => DataContainer::SORT_MONTH_DESC,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'activatedAt' => [
            'default' => 0,
            'flag' => DataContainer::SORT_MONTH_DESC,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'token' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        'email' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 255,
                'rgxp' => 'email',
                'decodeEntities' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'language' => [
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'moduleOffersAlert' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
            ],
            'foreignKey' => 'tl_module.name',
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => [
                'type' => 'hasOne',
                'load' => 'eager',
            ],
        ],
        'feed' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'eval' => [
                'includeBlankOption' => true,
                'chosen' => true,
                'tl_class' => 'w50',
            ],
            'foreignKey' => 'tl_wem_offer_feed.title',
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => [
                'type' => 'hasOne',
                'load' => 'eager',
            ],
        ],
        'frequency' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => ['hourly', 'daily', 'weekly', 'monthly'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_offer_alert']['frequency'],
            'eval' => [
                'includeBlankOption' => true,
                'chosen' => true,
                'tl_class' => 'w50',
            ],
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
