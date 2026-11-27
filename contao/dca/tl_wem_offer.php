<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

use Contao\BackendUser;
use Contao\DataContainer;
use Contao\System;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_wem_offer'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_offer_feed',
        'ctable' => ['tl_content', 'tl_wem_offer_application'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'code,lang' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['code ASC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'operations' => [
            'edit',
            'children',
            'copy',
            'delete',
            'toggle',
            'show',
            'applications' => [
                'href' => 'table=tl_wem_offer_application',
                'icon' => 'mgroup.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['addImage', 'overwriteMeta'],
        'default' => '
            {title_legend},code,title,date,lang;
            {content_legend},teaser;
            {media_legend},addImage;
            {publish_legend},published,start,stop
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'addImage' => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
        'overwriteMeta' => 'alt,imageTitle,imageUrl,caption',
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
            'relation' => [
                'type' => 'belongsTo',
                'load' => 'eager',
            ],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'default' => time(),
            'flag' => DataContainer::SORT_MONTH_DESC,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'lang' => [
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => static fn (): array => System::getContainer()->get('contao.intl.locales')->getEnabledLocales(),
            'eval' => [
                'mandatory' => true,
                'tl_class' => 'w50',
                'maxlength' => 5,
            ],
            'sql' => "char(5) NOT NULL default ''",
        ],
        'code' => [
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTERS_ASC,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'tl_class' => 'w50',
                'maxlength' => 255,
            ],
            'sql' => "varchar(255) NOT NULL default ''",
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
        'date' => [
            'exclude' => true,
            'default' => time(),
            'sorting' => true,
            'flag' => DataContainer::SORT_MONTH_DESC,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'datim',
                'datepicker' => true,
                'tl_class' => 'w50 wizard',
            ],
            'sql' => "varchar(10) NOT NULL default ''",
        ],

        'teaser' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => [
                'rte' => 'tinyMCE',
                'helpwizard' => true,
                'tl_class' => 'clr',
            ],
            'explanation' => 'insertTags',
            'sql' => 'mediumtext NULL',
        ],
        'addImage' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['addImage'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'submitOnChange' => true,
            ],
            'sql' => "char(1) NOT NULL default ''"],
        'overwriteMeta' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'submitOnChange' => true,
                'tl_class' => 'w50 clr',
            ],
            'sql' => "char(1) NOT NULL default ''"],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => '%contao.image.valid_extensions%',
                'mandatory' => true,
            ],
            'sql' => 'binary(16) NULL'],
        'alt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['alt'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''"],
        'imageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''"],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['size'],
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => [
                'rgxp' => 'natural',
                'includeBlankOption' => true,
                'nospace' => true,
                'helpwizard' => true,
                'tl_class' => 'w50',
            ],
            'options_callback' => static fn (): array => System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance()),
            'sql' => "varchar(64) NOT NULL default ''"],
        'imagemargin' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
            'exclude' => true,
            'inputType' => 'trbl',
            'options' => ['px', '%', 'em', 'rem'],
            'eval' => [
                'includeBlankOption' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(128) NOT NULL default ''"],
        'imageUrl' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'url',
                'decodeEntities' => true,
                'maxlength' => 2048,
                'dcaPicker' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(2048) NOT NULL default ''"],
        'fullsize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
            ],
            'sql' => "char(1) NOT NULL default ''"],
        'caption' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['caption'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'allowHtml' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''"],
        'floating' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['floating'],
            'exclude' => true,
            'inputType' => 'radioTable',
            'options' => ['above', 'left', 'right', 'below'],
            'eval' => [
                'cols' => 4,
                'tl_class' => 'w50',
            ],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'sql' => "varchar(12) NOT NULL default 'above'"],

        'published' => [
            'exclude' => true,
            'filter' => true,
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'doNotCopy' => true,
                'tl_class' => 'w50 m12 clr',
            ],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'datim',
                'datepicker' => true,
                'tl_class' => 'w50 clr wizard',
            ],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'datim',
                'datepicker' => true,
                'tl_class' => 'w50 wizard',
            ],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];
