<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   easydocgenerator     Module Easydocgenerator
 *  \brief      Easydocgenerator module descriptor.
 *
 *  \file       htdocs/easydocgenerator/core/modules/modEasydocgenerator.class.php
 *  \ingroup    easydocgenerator
 *  \brief      Description and activation file for module Easydocgenerator
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

// phpcs:disable
/**
 *  Description and activation class for module Easydocgenerator
 */
class modEasydocgenerator extends DolibarrModules
{
	// phpcs:enable
	/**
	 * @var array configuration from json file
	 */
	public $configuration;

	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// check if we have configuration
		$configurationfile = dol_buildpath('/easydocgenerator/json/configuration.json');
		$this->configuration = [];
		if (file_exists($configurationfile)) {
			$this->configuration = json_decode(file_get_contents($configurationfile), true);
		}
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = $this->configuration['numero']; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = $this->configuration['right_class'];

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = $this->configuration['family'];

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = $this->configuration['module_position'];

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		// $this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleEasydocgeneratorName' not found (Easydocgenerator is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleEasydocgeneratorDesc' not found (Easydocgenerator is name of module).
		$this->description = $this->configuration['description'];
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = $this->configuration['descriptionlong'];

		// Author
		$this->editor_name = $this->configuration['editor_name'];
		$this->editor_url = $this->configuration['editor_url'];

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0'; // TODO remove this line
		$this->version = $this->configuration['version'];
		// Url to the file with your last numberversion of this module
		// $this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where EASYDOCGENERATOR is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = $this->configuration['picto'];

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = $this->configuration['module_parts'];

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/easydocgenerator/temp","/easydocgenerator/subdir");
		$this->dirs = $this->configuration['dirs'];

		// Config pages. Put here list of php page, stored into easydocgenerator/admin directory, to use to setup module.
		$this->config_page_url = ["setup.php@easydocgenerator"];

		// Dependencies
		// A condition to hide module
		$this->hidden = $this->configuration['hidden'];
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = [];
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = [];
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = [];

		// The language file dedicated to your module
		$this->langfiles = ["easydocgenerator@easydocgenerator"];

		// Prerequisites
		$this->phpmin = [7, 4]; // Minimum version of PHP required by module
		$this->need_dolibarr_version = [16, -3]; // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		// $this->automatic_activation = array('FR'=>'EasydocgeneratorWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		// $this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		$this->const = $this->configuration['const'];

		// Some keys to add into the overwriting translation tables
		// $this->overwrite_translation = [
		// 	'en_US:ParentCompany'=>'Parent company or reseller',
		// 	'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		// ];

		if (!isset($conf->easydocgenerator) || !isset($conf->easydocgenerator->enabled)) {
			$conf->easydocgenerator = new stdClass();
			$conf->easydocgenerator->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = $this->configuration['tabs'];
		// Example:
		// $this->tabs[] = [
		// 	// To add a new tab identified by code tabname1
		// 	'data'=>'objecttype:+tabname1:Title1:mylangfile@easydocgenerator:$user->rights->easydocgenerator->read:/easydocgenerator/mynewtab1.php?id=__ID__'
		// ];
		// // To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@easydocgenerator:$user->rights->othermodule->read:/easydocgenerator/mynewtab2.php?id=__ID__');
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sale order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		/* Example:
		 $this->dictionaries=array(
		 'langs'=>'easydocgenerator@easydocgenerator',
		 // List of tables we want to see into dictonnary editor
		 'tabname'=>array("table1", "table2", "table3"),
		 // Label of tables
		 'tablib'=>array("Table1", "Table2", "Table3"),
		 // Request to select fields
		 'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
		 // Sort order
		 'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
		 // List of fields (result of select to show dictionary)
		 'tabfield'=>array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields to edit a record)
		 'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields for insert)
		 'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
		 // Name of columns with primary key (try to always name it 'rowid')
		 'tabrowid'=>array("rowid", "rowid", "rowid"),
		 // Condition to show each dictionary
		 'tabcond'=>array(isModEnabled('easydocgenerator'), isModEnabled('easydocgenerator'), isModEnabled('easydocgenerator')),
		 // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
		 'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		 );
		 */
		$this->dictionaries = $this->configuration['dictionaries'];

		// Boxes/Widgets
		$this->boxes = $this->configuration['boxes'];

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = [
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/easydocgenerator/class/myobject.class.php',
			//      'objectname' => 'MyObject',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("easydocgenerator")',
			//      'priority' => 50,
			//  ),
		];

		// Permissions provided by this module
		$this->rights = [];
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		/*
		$o = 1;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read objects of Easydocgenerator'; // Permission label
		$this->rights[$r][4] = 'myobject';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->hasRight('easydocgenerator', 'myobject', 'read'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 2); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update objects of Easydocgenerator'; // Permission label
		$this->rights[$r][4] = 'myobject';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->hasRight('easydocgenerator', 'myobject', 'write'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 3); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete objects of Easydocgenerator'; // Permission label
		$this->rights[$r][4] = 'myobject';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->easydocgenerator->myobject->delete)
		$r++;
		*/
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = [];
		// $r = 0;
		// // Add here entries to declare new menus
		// /* BEGIN MODULEBUILDER TOPMENU */
		// $this->menu[$r++] = [
		// 	'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'top', // This is a Top menu entry
		// 	'titre'=>'ModuleEasydocgeneratorName',
		// 	'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
		// 	'mainmenu'=>'easydocgenerator',
		// 	'leftmenu'=>'',
		// 	'url'=>'/easydocgenerator/easydocgeneratorindex.php',
		// 	'langs'=>'easydocgenerator@easydocgenerator', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000 + $r,
		// 	'enabled'=>'isModEnabled("easydocgenerator")', // Define condition to show or hide menu entry. Use 'isModEnabled("easydocgenerator")' if entry must be visible if module is enabled.
		// 	'perms'=>'1', // Use 'perms'=>'$user->hasRight("easydocgenerator", "myobject", "read")' if you want your menu with a permission rules
		// 	'target'=>'',
		// 	'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		// ];
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		/*$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=easydocgenerator',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'MyObject',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu'=>'easydocgenerator',
			'leftmenu'=>'myobject',
			'url'=>'/easydocgenerator/easydocgeneratorindex.php',
			'langs'=>'easydocgenerator@easydocgenerator',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("easydocgenerator")', // Define condition to show or hide menu entry. Use 'isModEnabled("easydocgenerator")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("easydocgenerator", "myobject", "read")',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=easydocgenerator,fk_leftmenu=myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List_MyObject',
			'mainmenu'=>'easydocgenerator',
			'leftmenu'=>'easydocgenerator_myobject_list',
			'url'=>'/easydocgenerator/myobject_list.php',
			'langs'=>'easydocgenerator@easydocgenerator',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("easydocgenerator")', // Define condition to show or hide menu entry. Use 'isModEnabled("easydocgenerator")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("easydocgenerator", "myobject", "read")'
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=easydocgenerator,fk_leftmenu=myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_MyObject',
			'mainmenu'=>'easydocgenerator',
			'leftmenu'=>'easydocgenerator_myobject_new',
			'url'=>'/easydocgenerator/myobject_card.php?action=create',
			'langs'=>'easydocgenerator@easydocgenerator',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("easydocgenerator")', // Define condition to show or hide menu entry. Use 'isModEnabled("easydocgenerator")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->hasRight("easydocgenerator", "myobject", "write")'
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);*/
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		//$result = $this->_load_tables('/install/mysql/', 'easydocgenerator');
		$result = $this->_load_tables('/easydocgenerator/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		if (!empty($this->module_parts['extrafields'])) {
			$tocreate = $this->module_parts['extrafields'];
			include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			foreach ($tocreate as $extra) {
				$extrafields->addExtraField(
					$extra['attrname'],
					$extra['label'],
					$extra['type'],
					$extra['pos'],
					$extra['size'],
					$extra['elementtype'],
					$extra['unique'],
					$extra['required'],
					$extra['default_value'],
					$extra['param'],
					$extra['alwayseditable'],
					$extra['perms'],
					$extra['list'],
					$extra['help'],
					$extra['computed'],
					$extra['entity'] ?? '',
					$extra['langfile'] ?? '',
					$extra['enabled'] ?? '1',
					$extra['totalizable'] ?? 0,
					$extra['printable'] ?? 0,
					$extra['moreparams'] ?? []
				);
			}
		}

		// Permissions
		$this->remove($options);

		$sql = [];

		// Document templates
		$moduledir = dol_sanitizeFileName('easydocgenerator');
		$modules = [];
		$modules = [
			'order',
			'invoice',
		];

		foreach ($modules as $module) {
			$src = dol_buildpath('/easydocgenerator/templates/');
			$dirtwig = DOL_DATA_ROOT . '/doctemplates/' . $moduledir;
			$dest = $dirtwig . '/' . $module . '/template_myobjects.twig';

			if (file_exists($src) && !file_exists($dest)) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
				dol_mkdir($dirtwig);
				$result = dol_copy($src, $dest, 0, 0);
				if ($result < 0) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
					return 0;
				}
			}

			$sql = array_merge($sql, [
				"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = 'standard_" . strtolower($myTmpObjectKey) . "' AND type = '" . $this->db->escape(strtolower($myTmpObjectKey)) . "' AND entity = " . ((int) $conf->entity),
				"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('standard_" . strtolower($myTmpObjectKey) . "', '" . $this->db->escape(strtolower($myTmpObjectKey)) . "', " . ((int) $conf->entity) . ")",
				"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = 'generic_" . strtolower($myTmpObjectKey) . "_twig' AND type = '" . $this->db->escape(strtolower($myTmpObjectKey)) . "' AND entity = " . ((int) $conf->entity),
				"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('generic_" . strtolower($myTmpObjectKey) . "_twig', '" . $this->db->escape(strtolower($myTmpObjectKey)) . "', " . ((int) $conf->entity) . ")"
			]);
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = [];
		return $this->_remove($sql, $options);
	}
}
