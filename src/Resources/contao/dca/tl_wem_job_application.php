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

$GLOBALS['TL_DCA']['tl_wem_job_application'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_job',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['country DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [WEM\JobOffersBundle\DataContainer\JobApplicationContainer::class, 'listItems'],
            'child_record_class' => 'no_padding',
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
            'show_cv' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['showCv'],
                'title'=> &$GLOBALS['TL_LANG']['tl_wem_job_application']['showCvModalTitle'],
                'href' => 'key=show_cv',
                'icon' => 'pickfile.gif',
                'button_callback' => [WEM\JobOffersBundle\DataContainer\JobApplicationContainer::class, 'showCv'],
            ],
            'show_applicationLetter' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['showApplicationLetter'],
                'title'=> &$GLOBALS['TL_LANG']['tl_wem_job_application']['showApplicationLetterModalTitle'],
                'href' => 'key=show_applicationLetter',
                'icon' => 'tablewizard.gif',
                'button_callback' => [WEM\JobOffersBundle\DataContainer\JobApplicationContainer::class, 'showApplicationLetter'],
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
            {files_legend},cv,applicationLetter;
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
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['createdAt'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['status'],
            'default' => 'not-answered',
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['not-answered', 'refused', 'accepted'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['status'],
            'eval' => ['helpwizard' => true, 'mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default 'not-answered'",
        ],

        // {name_legend},firstname,lastname;
        'firstname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['firstname'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'lastname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['lastname'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        // {street_legend},street,postal,city,country;
        'street' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['street'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'w100 clr'],
            'sql' => 'text NULL',
        ],
        'postal' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['postal'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'city' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['city'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'country' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['country'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options' => System::getCountries(),
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default ''",
        ],

        // {contact_legend},phone,email,comments;
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['phone'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['email'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'comments' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['comments'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => 'mediumtext NULL',
        ],

        // {files_legend},cv,applicationLetter;
        'cv' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['cv'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'applicationLetter' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_job_application']['applicationLetter'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
    ],
];
