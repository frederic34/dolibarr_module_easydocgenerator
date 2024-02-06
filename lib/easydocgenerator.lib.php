<?php
/* Copyright (C) 2023-2024  Frédéric France <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    easydocgenerator/lib/easydocgenerator.lib.php
 * \ingroup easydocgenerator
 * \brief   Library files with common functions for EasyBuilder
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function easydocgeneratorAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("easydocgenerator@easydocgenerator");

	$h = 0;
	$head = [];

	$head[$h][0] = dol_buildpath("/easydocgenerator/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/easydocgenerator/admin/changelog.php", 1);
	$head[$h][1] = $langs->trans("Changelog");
	$head[$h][2] = 'changelog';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/easydocgenerator/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = is_countable($extrafields->attributes['myobject']['label']) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/easydocgenerator/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@easydocgenerator:/easydocgenerator/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@easydocgenerator:/easydocgenerator/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'easydocgenerator@easydocgenerator');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'easydocgenerator@easydocgenerator', 'remove');

	return $head;
}

/**
 * Define array with couple substitution key => substitution value
 *
 * @param   Object		$object    		Dolibarr Object
 * @param   Translate	$outputlangs    Language object for output
 * @param   boolean|int	$recursive    	Want to fetch child array or child object.
 * @param   string      $objectname     Name of object
 * @return	array						Array of substitution key->code
 */
function getEachVarObject($object, $outputlangs, $recursive = 1, $objectname = 'object')
{
	$array_other = [];
	if (!empty($object)) {
		foreach ($object as $key => $value) {
			if (in_array($key, ['db', 'fields', 'lines', 'modelpdf', 'model_pdf'])) {		// discard some properties
				continue;
			}
			if (!empty($value)) {
				if (!is_array($value) && !is_object($value)) {
					$toreplace  = ["\r\n", "\n", "\r"];
					$value = str_replace($toreplace, "<br>", $value);
					$array_other[$objectname][$key] = str_replace("\n", "<br>", $value);
				} elseif (is_array($value) && ($recursive || $key == 'array_options')) {
					$tmparray = getEachVarObject($value, $outputlangs, 0);
					foreach ($tmparray as $key2 => $value2) {
						$array_other[$objectname][$key] = $value2;
					}
				} elseif (is_object($value) && $recursive) {
					$tmparray = getEachVarObject($value, $outputlangs, 0);
					foreach ($tmparray as $key2 => $value2) {
						$array_other[$objectname][$key] = $value2;
					}
				}
			}
		}
	}

	return $array_other;
}


/**
 *  Return footer info of page for PDF generation
 *
 *  @param  Translate		$outputlangs	Object lang for output
 * 	@param	string			$paramfreetext	Constant name of free text
 * 	@param	Societe			$fromcompany	Object company
 * 	@param	CommonObject	$object			Object shown in PDF
 * 	@param	int				$showdetails	Show company address details into footer (0=Nothing, 1=Show address, 2=Show managers, 3=Both)
 *  @param	int				$hidefreetext	1=Hide free text, 0=Show free text
 *  @param	int				$page_largeur	Page width
 *  @param	string			$watermark		Watermark text to print on page
 * 	@return	array							Return lines for footer
 */
function getPdfPagefoot($outputlangs, $paramfreetext, $fromcompany, $object, $showdetails = 0, $hidefreetext = 0)
{
	global $conf, $hookmanager;

	$outputlangs->load("dict");
	$line = '';
	$reg = [];


	// Line of free text
	if (empty($hidefreetext) && !empty($conf->global->$paramfreetext)) {
		$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
		// More substitution keys
		$substitutionarray['__FROM_NAME__'] = $fromcompany->name;
		$substitutionarray['__FROM_EMAIL__'] = $fromcompany->email;
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray, $outputlangs);

		// Make a change into HTML code to allow to include images from medias directory.
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.DOL_DATA_ROOT.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		$newfreetext = preg_replace('/(<img.*src=")[^\"]*viewimage\.php[^\"]*modulepart=medias[^\"]*file=([^\"]*)("[^\/]*\/>)/', '\1file:/' . DOL_DATA_ROOT . '/medias/\2\3', $newfreetext);

		$line .= $outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos
	$line1 = "";
	$line2 = "";
	$line3 = "";
	$line4 = "";

	if ($showdetails == 1 || $showdetails == 3) {
		// Company name
		if ($fromcompany->name) {
			$line1 .= ($line1 ? " - " : "") . $outputlangs->transnoentities("RegisteredOffice") . ": " . $fromcompany->name;
		}
		// Address
		if ($fromcompany->address) {
			$line1 .= ($line1 ? " - " : "") . str_replace("\n", ", ", $fromcompany->address);
		}
		// Zip code
		if ($fromcompany->zip) {
			$line1 .= ($line1 ? " - " : "") . $fromcompany->zip;
		}
		// Town
		if ($fromcompany->town) {
			$line1 .= ($line1 ? " " : "") . $fromcompany->town;
		}
		// Country
		if ($fromcompany->country) {
			$line1 .= ($line1 ? ", " : "") . $fromcompany->country;
		}
		// Phone
		if ($fromcompany->phone) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("Phone") . ": " . $fromcompany->phone;
		}
		// Fax
		if ($fromcompany->fax) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("Fax") . ": " . $fromcompany->fax;
		}
		// URL
		if ($fromcompany->url) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->url;
		}
		// Email
		if ($fromcompany->email) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->email;
		}
	}

	if ($showdetails == 2 || $showdetails == 3 || (!empty($fromcompany->country_code) && $fromcompany->country_code == 'DE')) {
		// Managers
		if ($fromcompany->managers) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->managers;
		}
	}

	// Line 3 of company infos
	// Juridical status
	if (!empty($fromcompany->forme_juridique_code) && $fromcompany->forme_juridique_code) {
		$line3 .= ($line3 ? " - " : "") . $outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
	}
	// Capital
	if (!empty($fromcompany->capital)) {
		$tmpamounttoshow = price2num($fromcompany->capital); // This field is a free string or a float
		if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) {
			$line3 .= ($line3 ? " - " : "") . $outputlangs->transnoentities("CapitalOf", price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
		} elseif (!empty($fromcompany->capital)) {
			$line3 .= ($line3 ? " - " : "") . $outputlangs->transnoentities("CapitalOf", $fromcompany->capital, $outputlangs);
		}
	}
	// Prof Id 1
	if (!empty($fromcompany->idprof1) && $fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $outputlangs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if (!empty($fromcompany->idprof2) && $fromcompany->idprof2) {
		$field = $outputlangs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof2);
	}

	// Line 4 of company infos
	// Prof Id 3
	if (!empty($fromcompany->idprof3) && $fromcompany->idprof3) {
		$field = $outputlangs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof3);
	}
	// Prof Id 4
	if (!empty($fromcompany->idprof4) && $fromcompany->idprof4) {
		$field = $outputlangs->transcountrynoentities("ProfId4", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof4);
	}
	// Prof Id 5
	if (!empty($fromcompany->idprof5) && $fromcompany->idprof5) {
		$field = $outputlangs->transcountrynoentities("ProfId5", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof5);
	}
	// Prof Id 6
	if (!empty($fromcompany->idprof6) &&  $fromcompany->idprof6) {
		$field = $outputlangs->transcountrynoentities("ProfId6", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof6);
	}
	// Prof Id 7
	if (!empty($fromcompany->idprof7) &&  $fromcompany->idprof7) {
		$field = $outputlangs->transcountrynoentities("ProfId7", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof7);
	}
	// Prof Id 8
	if (!empty($fromcompany->idprof8) &&  $fromcompany->idprof8) {
		$field = $outputlangs->transcountrynoentities("ProfId8", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof8);
	}
	// Prof Id 9
	if (!empty($fromcompany->idprof9) &&  $fromcompany->idprof9) {
		$field = $outputlangs->transcountrynoentities("ProfId9", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof9);
	}
	// Prof Id 10
	if (!empty($fromcompany->idprof10) &&  $fromcompany->idprof10) {
		$field = $outputlangs->transcountrynoentities("ProfId10", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof10);
	}
	// IntraCommunautary VAT
	if (!empty($fromcompany->tva_intra)  && $fromcompany->tva_intra != '') {
		$line4 .= ($line4 ? " - " : "") . $outputlangs->transnoentities("VATIntraShort") . ": " . $outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	$lines = [
		'line' => $line,
		'line1' => $line1,
		'line2' => $line2,
		'line3' => $line3,
		'line4' => $line4,
	];

	return $lines;
}
