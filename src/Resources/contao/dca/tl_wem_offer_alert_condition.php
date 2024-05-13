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

use WEM\OffersBundle\DataContainer\OfferAlertConditionContainer;

$GLOBALS['TL_DCA']['tl_wem_offer_alert_condition'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_offer_alert',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'onload_callback' => [
            static fn($dc): array => (new OfferAlertConditionContainer())->getValueChoices($dc)
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'field' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['field ASC'],
            'headerFields' => ['name', 'position', 'phone', 'email'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => static fn($row): string => (new OfferAlertConditionContainer())->listItems($row),
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
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},field,value
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

        'field' => [
            'exclude' => true,
            'filter' => true,
            'inputType'                 => 'select',
            'options_callback'          => static fn($dc): array => (new OfferAlertConditionContainer())->getFieldChoices($dc),
            'eval'                      => ['includeBlankOption'=>true, 'submitOnChange'=>true, 'chosen'=>true, 'tl_class'=>'w50'],
            'sql'                       => "varchar(255) NOT NULL default ''"
        ],
        'value' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ],
];
