<?php

/**
 * Prezioso Extension for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Table tl_pzl_job_application
 */
$GLOBALS['TL_DCA']['tl_pzl_job_application'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'					  => 'tl_pzl_job',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('country DESC'),
			'headerFields'            => array('title'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_pzl_job_application', 'listItems'),
			'child_record_class'      => 'no_padding'
		),
		'global_operations' => array
		(
			/*'export' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['export'],
				'href'                => 'key=export',
				'class'               => 'header_css_import',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),*/
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '
			{statut_legend},createdAt,status;
			{name_legend},firstname,lastname;
			{street_legend},street,postal,city,country;
			{contact_legend},phone,email,comments;
			{files_legend},cv,applicationLetter;
		'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		// {statut_legend},createdAt,status;
		'createdAt' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['createdAt'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'status' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['status'],
			'default'				  => 'not-answered',
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options'				  => array('not-answered', 'refused', 'accepted'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['status'],
			'eval'                    => array('helpwizard'=>true, 'mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default 'not-answered'"
		),

		// {name_legend},firstname,lastname;
		'firstname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['firstname'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'lastname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['lastname'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		// {street_legend},street,postal,city,country;
		'street' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['street'],
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('tl_class'=>'w100 clr'),
			'sql'                     => "text NULL"
		),
		'postal' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['postal'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'city' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['city'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'country' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['country'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'inputType'               => 'select',
			'options'                 => System::getCountries(),
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2) NOT NULL default ''"
		),

		// {contact_legend},phone,email,comments;
		'phone' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['phone'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['email'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'rgxp'=>'email', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'comments' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['comments'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('tl_class'=>'clr'),
			'sql'                     => "mediumtext NULL"
		),

		// {files_legend},cv,applicationLetter;
		'cv' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['cv'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'applicationLetter' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_pzl_job_application']['applicationLetter'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_pzl_job_application extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct(){
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Design each row of the DCA
	 * @param  Array  $arrRow
	 * @return String
	 */
	public function listItems($row){
		return sprintf(
			'(%s) %s <span style="color:#888">[%s - %s]</span>'
			,$GLOBALS['TL_LANG']['tl_pzl_job_application']['status'][$row['status']]
			,$row['firstname'].' '.$row['lastname']
			,$row['city']
			,$GLOBALS['TL_LANG']['CNT'][$row['country']]
		);
	}
}