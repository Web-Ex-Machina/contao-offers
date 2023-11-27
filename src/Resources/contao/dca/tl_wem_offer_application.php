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

$GLOBALS['TL_DCA']['tl_wem_offer_application'] = [
    // Config
    'config' => [
        'dataContainer' => WEM\OffersBundle\Dca\Driver\DC_Table::class,
        'ptable' => 'tl_wem_offer',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],

        'ondelete_callback' => [['wem.personal_data_manager.dca.config.callback.delete', '__invoke']],
        'onshow_callback' => [['wem.personal_data_manager.dca.config.callback.show', '__invoke']],
        'onsubmit_callback' => [['wem.personal_data_manager.dca.config.callback.submit', '__invoke']],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['country DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [WEM\OffersBundle\DataContainer\OfferApplicationContainer::class, 'listItems'],
            'child_record_class' => 'no_padding',
        ],
        'label'=>[
            'fields'=>['firstname','lastname','city','country'],
            'label_callback' => ['wem.personal_data_manager.dca.listing.callback.list_label_label_for_list', '__invoke'],
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
            'show_cv' => [
                'href' => 'key=show_cv',
                'icon' => 'pickfile.gif',
                'button_callback' => [WEM\OffersBundle\DataContainer\OfferApplicationContainer::class, 'showCv'],
            ],
            'show_applicationLetter' => [
                'href' => 'key=show_applicationLetter',
                'icon' => 'tablewizard.gif',
                'button_callback' => [WEM\OffersBundle\DataContainer\OfferApplicationContainer::class, 'showApplicationLetter'],
            ],
            'sendNotificationToApplication' => [
                'href' => 'key=sendNotificationToApplication',
                'icon' => 'rows.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {statut_legend},createdAt,status;
            {name_legend},firstname,lastname;
            {street_legend},street,postal,city,country;
            {contact_legend},phone,email,comments;
            {application_legend},disponibility;
            {files_legend},cv,documents
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

        // {statut_legend},createdAt,status;
        'createdAt' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'status' => [
            'default' => 'not-answered',
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['not-answered', 'refused', 'accepted'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_offer_application']['status'],
            'eval' => ['helpwizard' => true, 'mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default 'not-answered'",
        ],

        // {name_legend},firstname,lastname;
        'firstname' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'lastname' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {street_legend},street,postal,city,country;
        'street' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'w100 clr'],
            'sql' => 'text NULL',
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'postal' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'city' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'country' => [
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options' => System::getCountries(),
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {contact_legend},phone,email,comments;
        'phone' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'email' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'comments' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => 'mediumtext NULL',
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {application_legend},disponibility;
        'disponibility' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255],
            'sql' => "mediumtext NULL",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {files_legend},cv,documents;
        'cv' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'documents' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
    ],
];
