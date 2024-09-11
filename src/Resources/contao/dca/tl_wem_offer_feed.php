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

use Contao\Controller;
use WEM\OffersBundle\DataContainer\OfferFeedContainer;

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
            'attributes' => [
                'href' => 'table=tl_wem_offer_feed_attribute',
                'icon' => 'header.gif',
            ],
            'offers' => [
                'href' => 'table=tl_wem_offer',
                'icon' => 'folderOP.gif',
            ]
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
                [OfferFeedContainer::class, 'generateAlias'],
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],

        'jumpTo' => [
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => array('fieldType'=>'radio'),
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => array('type'=>'hasOne', 'load'=>'lazy')
        ],

        'attributes' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_offer_feed_attribute',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem-offers',
            ],
            'eval' => [
                'fields' => ['name', 'label', 'type','isFilter','isAlertCondition'],
                'orderField' => 'name ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],

        'ncEmailAlert' => [
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => [OfferFeedContainer::class, 'getAlertEmailNotificationChoices'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tplOfferAlert' => [
            'exclude'                 => true,
            'inputType'               => 'select',
            'options_callback'        => static fn() => Controller::getTemplateGroup('offer_alert_'),
            'eval'                    => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'],
            'sql'                     => "varchar(64) NOT NULL default ''"
        ]
    ],
];
