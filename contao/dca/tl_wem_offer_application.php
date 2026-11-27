<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

use Contao\DataContainer;
use WEM\OffersBundle\Dca\Driver\DC_Table;
use WEM\UtilsBundle\Classes\CountriesUtil;

$GLOBALS['TL_DCA']['tl_wem_offer_application'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
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
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['country DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_class' => 'no_padding',
        ],
        'label' => [
            'fields' => ['firstname', 'lastname', 'city', 'country'],
            'label_callback' => ['wem.personal_data_manager.dca.listing.callback.list_label_label_for_list', '__invoke'],
        ],
        'operations' => [
            'edit',
            'delete',
            'show',
            'show_cv' => [
                'href' => 'key=show_cv',
                'icon' => 'pickfile.svg',
                'attributes' => 'onclick="Backend.openModalIframe({\'title\':this.title,\'url\':this.href});return false"',
            ],
            'show_applicationLetter' => [
                'href' => 'key=show_applicationLetter',
                'icon' => 'document.svg',
                'attributes' => 'onclick="Backend.openModalIframe({\'title\':this.title,\'url\':this.href});return false"',
            ],
            'sendNotificationToApplication' => [
                'href' => 'key=sendNotificationToApplication',
                'icon' => 'resend.svg',
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
            'foreignKey' => 'tl_wem_offer.title',
            'relation' => [
                'type' => 'belongsTo',
                'load' => 'eager',
            ],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        // {statut_legend},createdAt,status;
        'createdAt' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'datim',
                'datepicker' => true,
                'tl_class' => 'w50 wizard',
            ],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'status' => [
            'default' => 'not-answered',
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['not-answered', 'refused', 'accepted'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_offer_application']['status'],
            'eval' => [
                'helpwizard' => true,
                'mandatory' => true,
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default 'not-answered'",
        ],

        // {name_legend},firstname,lastname;
        'firstname' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'lastname' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {street_legend},street,postal,city,country;
        'street' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => [
                'tl_class' => 'w100 clr',
            ],
            'sql' => 'text NULL',
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'postal' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'city' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'country' => [
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => CountriesUtil::getCountries(...),
            'eval' => [
                'includeBlankOption' => true,
                'chosen' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(2) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {contact_legend},phone,email,comments;
        'phone' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 64,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(64) NOT NULL default ''",
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],
        'email' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'rgxp' => 'email',
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'comments' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => [
                'tl_class' => 'clr',
            ],
            'sql' => 'mediumtext NULL',
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {application_legend},disponibility;
        'disponibility' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
            ],
            'sql' => 'mediumtext NULL',
            'load_callback' => [['wem.personal_data_manager.dca.field.callback.load', '__invoke']],
        ],

        // {files_legend},cv,documents;
        'cv' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => [
                'filesOnly' => true,
                'fieldType' => 'radio',
                'tl_class' => 'clr',
            ],
            'sql' => 'binary(16) NULL',
        ],
        'documents' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => [
                'filesOnly' => true,
                'multiple' => true,
                'fieldType' => 'checkbox',
                'tl_class' => 'clr',
            ],
            'sql' => 'blob NULL',
        ],
    ],
];
