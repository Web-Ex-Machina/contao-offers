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
        '__selector__' => ['addImage', 'overwriteMeta'],
        'default' => '
            {title_legend},code,title,date;
            {content_legend},teaser;
            {image_legend},addImage;
            {publish_legend},published,start,stop
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'addImage' => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
        'overwriteMeta' => 'alt,imageTitle,imageUrl,caption'
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
        'date' => [
            'exclude' => true,
            'default' => time(),
            'sorting' => true,
            'flag' => 8,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],

        'teaser' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => 'mediumtext NULL',
        ],
        'addImage' => array
        (
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange'=>true),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'overwriteMeta' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'singleSRC' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>'%contao.image.valid_extensions%', 'mandatory'=>true),
            'sql'                     => "binary(16) NULL"
        ),
        'alt' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'imageTitle' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'size' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
            'exclude'                 => true,
            'inputType'               => 'imageSize',
            'reference'               => &$GLOBALS['TL_LANG']['MSC'],
            'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
            'options_callback' => static function ()
            {
                return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
            },
            'sql'                     => "varchar(64) NOT NULL default ''"
        ),
        'imagemargin' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
            'exclude'                 => true,
            'inputType'               => 'trbl',
            'options'                 => array('px', '%', 'em', 'rem'),
            'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(128) NOT NULL default ''"
        ),
        'imageUrl' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(2048) NOT NULL default ''"
        ),
        'fullsize' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'caption' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'floating' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
            'exclude'                 => true,
            'inputType'               => 'radioTable',
            'options'                 => array('above', 'left', 'right', 'below'),
            'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
            'reference'               => &$GLOBALS['TL_LANG']['MSC'],
            'sql'                     => "varchar(12) NOT NULL default 'above'"
        ),

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
