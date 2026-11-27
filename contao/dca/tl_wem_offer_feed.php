<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_wem_offer_feed'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_offer', 'tl_wem_offer_feed_attribute'],
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
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'operations' => [
            'edit',
            'children',
            'attributes' => [
                'href' => 'table=tl_wem_offer_feed_attribute',
                'icon' => 'modules.svg',
            ],
            'copy',
            'delete',
            'show',
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},title,alias;
            {config_legend},jumpTo;
            {attributes_legend},attributes;
            {alert_legend},ncEmailAlert,tplOfferAlert
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
            'flag' => DataContainer::SORT_MONTH_DESC,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'tl_class' => 'w50',
                'maxlength' => 255,
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'eval' => [
                'rgxp' => 'alias',
                'doNotCopy' => true,
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],

        'jumpTo' => [
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => [
                'fieldType' => 'radio',
            ],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => [
                'type' => 'hasOne',
                'load' => 'lazy',
            ],
        ],

        'attributes' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_offer_feed_attribute',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem-offers',
            ],
            'eval' => [
                'fields' => ['name', 'label', 'type', 'isFilter', 'isAlertCondition'],
                'orderField' => 'name ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],

        'ncEmailAlert' => [
            'exclude' => true,
            'inputType' => 'select',
            'eval' => [
                'includeBlankOption' => true,
                'chosen' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tplOfferAlert' => [
            'exclude' => true,
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
    ],
];
