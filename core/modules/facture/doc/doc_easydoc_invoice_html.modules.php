<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2021  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/facture/doc/doc_easydoc_invoice_html.modules.php
 *	\ingroup    facture
 *	\brief      File of class to build PDF documents for invoices
 */

use NumberToWords\NumberToWords;

require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
dol_include_once('/easydocgenerator/lib/easydocgenerator.lib.php');

// phpcs:disable
/**
 *	Class to build documents using HTML templates
 */
class doc_easydoc_invoice_html extends ModelePDFFactures
{
	// phpcs:enable
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(['main', 'companies', 'easydocgenerator@easydocgenerator']);

		$this->db = $db;
		$this->name = "Easydoc templates";
		$this->description = $langs->trans("DocumentModelEasydocgeneratorTemplate");
		// Name of constant that is used to save list of directories to scan
		$this->scandir = 'INVOICE_ADDON_EASYDOC_TEMPLATES_PATH';
		// Save the name of generated file as the main doc when generating a doc with this template
		$this->update_main_doc_field = 1;

		// Page size for A4 format
		$this->type = 'pdf';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = [$this->page_largeur, $this->page_hauteur];
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 0; // Manage the vat option COMMANDE_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}
	}


	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	public function info($langs)
	{
		global $conf, $langs;

		// Load translation files required by the page
		$langs->loadLangs(["errors", "companies", "easydocgenerator@easydocgenerator"]);

		$form = new Form($this->db);

		$text = $this->description . ".<br>\n";
		$text .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
		$text .= '<input type="hidden" name="token" value="' . newToken() . '">';
		$text .= '<input type="hidden" name="page_y" value="">';
		$text .= '<input type="hidden" name="action" value="setModuleOptions">';
		$text .= '<input type="hidden" name="param1" value="INVOICE_ADDON_EASYDOC_TEMPLATES_PATH">';
		$text .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$text .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectoriesForHtmlTemplates");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->INVOICE_ADDON_EASYDOC_TEMPLATES_PATH)));
		$listoffiles = [];
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]);
				continue;
			}
			if (!is_dir($tmpdir)) {
				$texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			} else {
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '');
				if (count($tmpfiles)) {
					$listoffiles = array_merge($listoffiles, $tmpfiles);
				}
			}
		}
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenHTML");
		$texthelp .= '<br><br><span class="opacitymedium">' . $langs->trans("ExampleOfDirectoriesForModelGen") . '</span>';
		// Add list of substitution keys
		// $texthelp .= '<br>' . $langs->trans("FollowingSubstitutionKeysCanBeUsed") . '<br>';
		// $texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		$text .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$text .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$text .= '<textarea class="flat" cols="60" name="value1">';
		$text .= getDolGlobalString('INVOICE_ADDON_EASYDOC_TEMPLATES_PATH');
		$text .= '</textarea>';
		$text .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$text .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="' . dol_escape_htmltag($langs->trans("Modify")) . '">';
		$text .= '<br></div></div>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if (getDolGlobalString('INVOICE_ADDON_EASYDOC_TEMPLATES_PATH')) {
			$text .= $langs->trans("NumberOfModelHTMLFilesFound") . ': <b>';
			//$text.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$text .= count($listoffiles);
			//$text.=$nbofiles?'</a>':'';
			$text .= '</b>';
		}

		if ($nbofiles) {
			$text .= '<div id="div_' . get_class($this) . '" class="hiddenx">';
			// Show list of found files
			foreach ($listoffiles as $file) {
				$text .= '- ' . $file['name'] . ' <a href="' . DOL_URL_ROOT . '/document.php?modulepart=doctemplates&file=invoices/' . urlencode(basename($file['name'])) . '">' . img_picto('', 'listlight') . '</a>';
				$text .= ' &nbsp; <a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?modulepart=doctemplates&keyforuploaddir=INVOICE_ADDON_EASYDOC_TEMPLATES_PATH&action=deletefile&token=' . newToken() . '&file=' . urlencode(basename($file['name'])) . '">' . img_picto('', 'delete') . '</a>';
				$text .= '<br>';
			}
			$text .= '</div>';
		}
		// Add input to upload a new template file.
		$text .= '<div>' . $langs->trans("UploadNewTemplate");
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			// MAX_FILE_SIZE must precede the field type=file
			$text .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . ($maxmin * 1024) . '">';
		}
		$text .= ' <input type="file" name="uploadfile">';
		$text .= '<input type="hidden" value="INVOICE_ADDON_EASYDOC_TEMPLATES_PATH" name="keyforuploaddir">';
		$text .= '<input type="submit" class="button reposition smallpaddingimp" value="' . dol_escape_htmltag($langs->trans("Upload")) . '" name="upload">';
		$text .= '</div>';

		$text .= '</td>';

		$text .= '</tr>';

		$text .= '</table>';
		$text .= '</form>';

		return $text;
	}

	// phpcs:disable
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Facture 	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_easydoc_invoice_html::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		$object->fetch_thirdparty();

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->loadLangs(['main', 'dict', 'companies', 'bills', 'products', 'orders', 'deliveries', 'banks', 'easydocgenerator@easydocgenerator']);

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(['main', 'dict', 'companies', 'bills', 'products', 'orders', 'deliveries', 'banks']);
		}

		// add linked objects to note_public
		$linkedObjects = pdf_getLinkedObjects($object, $outputlangs);


		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			// test it to crash...
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$currency = !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;

		// Add pdfgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(['pdfgeneration']);
		$parameters = ['object' => $object, 'outputlangs' => $outputlangs];
		global $action;
		$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		require dol_buildpath('easydocgenerator/vendor/autoload.php');
		$md5id = md5_file($srctemplatepath);
		$loader = new \Twig\Loader\FilesystemLoader(dirname($srctemplatepath));
		$enablecache = getDolGlobalInt('EASYDOCGENERATOR_ENABLE_DEVELOPPER_MODE') ? false : (DOL_DATA_ROOT . '/easydocgenerator/temp/' . ($md5id ? $md5id : ''));
		$twig = new \Twig\Environment($loader, [
			// developer mode unactive caching
			'cache' => $enablecache,
			'autoescape' => false,
		]);
		// create twig function which translate with $outpulangs->trans()
		$function = new \Twig\TwigFunction('trans', function ($value, $param1 = '', $param2 = '', $param3 = '') {
			global $outputlangs, $langs;
			if (!is_object($outputlangs)) {
				$outputlangs = $langs;
				$outputlangs->loadLangs(['main', 'dict', 'companies', 'bills', 'products', 'orders', 'deliveries', 'banks', 'easydocgenerator@easydocgenerator']);
			}
			return $outputlangs->trans($value, $param1, $param2, $param3);
		});
		$twig->addFunction($function);
		// create twig function which translate with $outpulangsbis->trans()
		$function = new \Twig\TwigFunction('transbis', function ($value, $param1 = '', $param2 = '', $param3 = '') {
			global $outputlangsbis, $langs;
			if (!is_object($outputlangsbis)) {
				$outputlangsbis = $langs;
				$outputlangsbis->loadLangs(['main', 'dict', 'companies', 'bills', 'products', 'orders', 'deliveries', 'banks', 'easydocgenerator@easydocgenerator']);
			}
			return $outputlangsbis->trans($value, $param1, $param2, $param3);
		});
		$twig->addFunction($function);
		// create twig function which returns getDolGlobalString(
		$function = new \Twig\TwigFunction('getDolGlobalString', function ($value, $default = '') {
			return getDolGlobalString($value, $default);
		});
		$twig->addFunction($function);
		// create twig function which return date formatted
		$function = new \Twig\TwigFunction('date', function ($time, $format = '') {
			return dol_print_date($time, $format);
		});
		$twig->addFunction($function);
		// create twig function which return price formatted
		$function = new \Twig\TwigFunction('price', function ($price) {
			return price($price, 0);
		});
		$twig->addFunction($function);
		// create twig function which return number to words
		$function = new \Twig\TwigFunction('numbertowords', function ($number, $currency, $language) {
			$numbertow = new NumberToWords();
			$currencyTransformer = $numbertow->getCurrencyTransformer($language);
			return $currencyTransformer->toWords($number * 100, $currency);
		});
		$twig->addFunction($function);
		// Load template
		try {
			$template = $twig->load(basename($srctemplatepath));
		} catch (\Twig\Error\SyntaxError $e) {
			$this->errors = $e->getMessage() . ' at line ' . $e->getLine() . ' in file ' . $e->getFile();
			return -1;
		} catch (Exception $e) {
			$this->errors = $e->getMessage();
			return -1;
		}
		$logo = '';
		if (!empty($this->emetteur->logo)) {
			$logodir = $conf->mycompany->dir_output;
			if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
				$logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
			} else {
				$logo = $logodir . '/logos/' . $this->emetteur->logo;
			}
		}
		$langtocountryflag = [
			'ar_AR' => '',
			'ca_ES' => 'catalonia',
			'da_DA' => 'dk',
			'fr_CA' => 'mq',
			'sv_SV' => 'se',
			'sw_SW' => 'unknown',
			'AQ' => 'unknown',
			'CW' => 'unknown',
			'IM' => 'unknown',
			'JE' => 'unknown',
			'MF' => 'unknown',
			'BL' => 'unknown',
			'SX' => 'unknown'
		];

		if (isset($langtocountryflag[$mysoc->country_code])) {
			$flagImage = $langtocountryflag[$mysoc->country_code];
		} else {
			$tmparray = explode('_', $mysoc->country_code);
			$flagImage = empty($tmparray[1]) ? $tmparray[0] : $tmparray[1];
		}
		$label_payment_conditions = '';
		if ($object->cond_reglement_code) {
			$label_payment_conditions = ($outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) != 'PaymentCondition' . $object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
			$label_payment_conditions = str_replace('\n', "\n", $label_payment_conditions);
			if ($object->deposit_percent > 0) {
				$label_payment_conditions = str_replace('__DEPOSIT_PERCENT__', $object->deposit_percent, $label_payment_conditions);
			}
		}
		// If CUSTOMER contact defined on invoice, we use it
		$usecontact = false;
		$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
		if (count($arrayidcontact) > 0) {
			$usecontact = true;
			$result = $object->fetch_contact($arrayidcontact[0]);
		}

		// Recipient name
		$contactobject = null;
		if (!empty($usecontact)) {
			// We can use the company of contact instead of thirdparty company
			if ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT'))) {
				$object->contact->fetch_thirdparty();
				$socobject = $object->contact->thirdparty;
				$contactobject = $object->contact;
			} else {
				$socobject = $object->thirdparty;
				// if we have a CUSTOMER contact and we don't use it as thirdparty recipient we store the contact object for later use
				$contactobject = $object->contact;
			}
		} else {
			$socobject = $object->thirdparty;
		}

		// Make substitution
		$substitutionarray = [
			'__FROM_NAME__' => $this->emetteur->name,
			'__FROM_EMAIL__' => $this->emetteur->email,
			'__TOTAL_TTC__' => $object->total_ttc,
			'__TOTAL_HT__' => $object->total_ht,
			'__TOTAL_VAT__' => $object->total_tva
		];
		complete_substitutions_array($substitutionarray, $langs, $object);
		$substitutionarray = array_merge(getCommonSubstitutionArray($outputlangs, 0, null, $object), $substitutionarray);

		// Call the ODTSubstitution hook
		$parameters = ['object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray];
		$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);

		// Line of free text
		$newfreetext = '';
		$paramfreetext = 'INVOICE_FREE_TEXT';
		if (!empty($conf->global->$paramfreetext)) {
			$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray);
		}
		// mysoc
		$substitutions = getEachVarObject($mysoc, $outputlangs, 1, 'mysoc');
		$substitutions['mysoc']['flag'] = DOL_DOCUMENT_ROOT . '/theme/common/flags/' . strtolower($flagImage) . '.png';
		$substitutions['mysoc']['phone_formatted'] = dol_print_phone($mysoc->phone, $mysoc->country_code, 0, 0, '', ' ');
		$substitutions['mysoc']['fax_formatted'] = dol_print_phone($mysoc->fax, $mysoc->country_code, 0, 0, '', ' ');

		// object
		$qrcodestring = '';
		if (getDolGlobalString('INVOICE_ADD_ZATCA_QR_CODE')) {
			$qrcodestring = $object->buildZATCAQRString();
		} elseif (getDolGlobalString('INVOICE_ADD_SWISS_QR_CODE') == '1') {
			$qrcodestring = $object->buildSwitzerlandQRString();
		}
		$substitutions = array_merge($substitutions, getEachVarObject($object, $outputlangs, 0));
		$substitutions['object']['qrcodestring'] = $qrcodestring;

		// thirdparty
		$substitutions = array_merge($substitutions, getEachVarObject($object->thirdparty, $outputlangs, 1, 'thirdparty'));
		$substitutions['thirdparty']['flag'] = DOL_DOCUMENT_ROOT . '/theme/common/flags/' . strtolower($object->thirdparty->country_code) . '.png';
		$substitutions['thirdparty']['phone_formatted'] = dol_print_phone($object->thirdparty->phone, $object->thirdparty->country_code, 0, 0, '', ' ');
		$substitutions['thirdparty']['fax_formatted'] = dol_print_phone($object->thirdparty->fax, $object->thirdparty->country_code, 0, 0, '', ' ');

		$typescontact = [
			'external' => [
				'BILLING',
				'SHIPPING',
				'SALESREPFOLL',
				'CUSTOMER',
			],
			'internal' => [
				'BILLING',
				'SHIPPING',
				'SALESREPFOLL',
				'CUSTOMER',
			],
		];
		foreach ($typescontact as $key => $value) {
			foreach ($value as $idx => $type) {
				$arrayidcontact = $object->getIdContact($key, $type);
				$contacts = [];
				foreach ($arrayidcontact as $idc) {
					if ($key == 'external') {
						$contact = new Contact($this->db);
					} else {
						$contact = new User($this->db);
					}
					$contact->fetch($idc);
					$contacts[] = $contact;
				}
				$substitutions = array_merge($substitutions, getEachVarObject($contacts, $outputlangs, 1, strtolower($type) . '_' . $key));
				if (!empty($substitutions[strtolower($type) . '_' . $key])) {
					foreach ($substitutions[strtolower($type) . '_' . $key] as $jdx => $substitution) {
						if (!empty($substitution['photo'])) {
							$substitutions[strtolower($type) . '_' . $key][$jdx]['picture'] = $conf->{$substitution['element']}->multidir_output[$conf->entity] . '/' . $substitution['ref'] . '/' . $substitution['photo'];
						}
					}
				}
			}
		}

		// other
		$substitutions = array_merge($substitutions, [
			'logo' => $logo,
			'freetext' => $newfreetext,
			'lines' => [],
			'linkedObjects' => $linkedObjects,
			'footerinfo' => getPdfPagefoot($outputlangs, $paramfreetext, $mysoc, $object),
			'labelpaymentconditions' => $label_payment_conditions,
			'currency' => $currency,
			'currencyinfo' => $outputlangs->trans("AmountInCurrency", $outputlangs->trans("Currency" . $currency)),
		]);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		$linenumber = 1;
		$linesarray = [];
		foreach ($object->lines as $key => $line) {
			$subtotal_ht += $line->total_ht;
			$subtotal_ttc += $line->total_ttc;
			if ($line->special_code == 104777 && $line->qty == 99) {
				$line->total_ht = $subtotal_ht;
				$line->total_ttc = $subtotal_ttc;
				$subtotal_ht = 0;
				$subtotal_ttc = 0;
			}
			$linearray = getEachVarObject($line, $outputlangs, 1, 'line');
			$linesarray[$key] = $linearray['line'];
			$linesarray[$key]['linenumber'] = $linenumber;
			// $substitutions['lines'][$key] = [
			// 	'linenumber' => $linenumber,
			// 	'qty' => $line->qty,
			// 	'ref' => $line->product_ref,
			// 	'label' => $line->label,
			// 	'description' => $line->desc,
			// 	'product_label' => $line->product_label,
			// 	'product_description' => $line->product_desc,
			// 	'subprice' => price($line->subprice),
			// 	'total_ht' => price($line->total_ht),
			// 	'total_ttc' => price($line->total_ttc),
			// 	'vatrate' => price($line->tva_tx) . '%',
			// 	'special_code' => $line->special_code,
			// 	'product_type' => $line->product_type,
			// 	'line_options' => [],
			// 	'product_options' => [],
			// ];
			if (empty($line->special_code)) {
				$linenumber++;
			}
		}
		$substitutions = array_merge($substitutions, ['lines' => $linesarray]);

		// Discounts
		$substitutions['discounts'] = [];
		// Loop on each discount available (deposits and credit notes and excess of payment included)
		$sql = "SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,  re.amount_ttc, re.multicurrency_amount_ttc,";
		$sql .= " re.description, re.fk_facture_source,";
		$sql .= " f.type, f.datef";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re, " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = " . ((int) $object->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$invoice = new Facture($this->db);
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($obj->type == 2) {
					$text = "CreditNote";
				} elseif ($obj->type == 3) {
					$text = "Deposit";
				} elseif ($obj->type == 0) {
					$text = "ExcessReceived";
				} else {
					$text = "UnknownType";
				}
				$invoice->fetch($obj->fk_facture_source);
				$substitutions['discounts'][] = [
					'text' => $text,
					'date' => $this->db->jdate($obj->datef),
					'ref' => $invoice->ref,
					'total_ttc' => $obj->amount_ttc,
					'multicurrency_total_ttc' => $obj->multicurrency_amount_ttc,
				];
				$i++;
			}
		}
		$substitutions['payments'] = [];
		// Loop on each payment
		// $sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
		// $sql .= " cp.code, ba.ref as bankref";
		// $sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf";
		// $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as p ON  pf.fk_paiement = p.rowid";
		// $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "c_paiement AS cp ON p.fk_paiement = cp.id AND cp.entity IN (" . getEntity('c_paiement') . ")";
		// $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "bank as b ON p.fk_bank = b.rowid";
		// $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON b.fk_account = ba.rowid";
		// $sql .= " WHERE pf.fk_facture = " . ((int) $object->id);
		// $sql .= " ORDER BY p.datep";
		$sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
		$sql .= " cp.code";
		$sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "paiement as p";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON p.fk_paiement = cp.id";
		$sql .= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = " . ((int) $object->id);
		// $sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = 1";
		$sql .= " ORDER BY p.datep";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$substitutions['payments'][] = [
					'text' => "PaymentTypeShort" . $obj->code,
					'date' => $this->db->jdate($obj->date),
					'num' => $obj->num,
					'total_ttc' => $obj->amount,
					'multicurrency_total_ttc' => $obj->multicurrency_amount,
					//'bankref' => $obj->bankref,
				];
				$i++;
			}
		}
		if (getDolGlobalInt('EASYDOCGENERATOR_ENABLE_DEVELOPPER_MODE')) {
			$substitutions['debug'] = '<pre>' . print_r($substitutions, true) . '</pre>';
		}
		try {
			$html = $template->render($substitutions);
		} catch (\Twig\Error\SyntaxError $e) {
			$this->errors = $e->getMessage() . ' at line ' . $e->getLine() . ' in file ' . $e->getFile();
			return -1;
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			return -1;
		}
		// print $html;
		$mpdf = new \Mpdf\Mpdf([
			'format' => [210, 297],
			'margin_left' => getDolGlobalInt('EASYDOC_PDF_MARGIN_LEFT', 10),
			'margin_right' => getDolGlobalInt('EASYDOC_PDF_MARGIN_RIGHT', 10),
			'margin_top' => getDolGlobalInt('EASYDOC_PDF_MARGIN_TOP', 48),
			'margin_bottom' => getDolGlobalInt('EASYDOC_PDF_MARGIN_BOTTOM', 25),
			'margin_header' =>  getDolGlobalInt('EASYDOC_PDF_MARGIN_HEADER', 10),
			'margin_footer' =>  getDolGlobalInt('EASYDOC_PDF_MARGIN_FOOTER', 10),
		]);
		$mpdf->SetProtection(['print']);
		$mpdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
		$mpdf->SetCreator('Dolibarr ' . DOL_VERSION);
		$mpdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$mpdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("PdfInvoiceTitle") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
		// Watermark
		$text = getDolGlobalString('FACTURE_DRAFT_WATERMARK');
		$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, null);
		complete_substitutions_array($substitutionarray, $outputlangs, null);
		$text = make_substitutions($text, $substitutionarray, $outputlangs);
		$mpdf->SetWatermarkText($text);
		$mpdf->showWatermarkText = ($object->status == Facture::STATUS_DRAFT && getDolGlobalString('FACTURE_DRAFT_WATERMARK'));
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;

		$mpdf->SetDisplayMode('fullpage');

		$mpdf->Bookmark($outputlangs->trans('PdfInvoiceTitle'));
		$mpdf->WriteHTML($html);

		$dir = $conf->facture->multidir_output[$object->entity];
		$objectref = dol_sanitizeFileName($object->ref);
		if (!preg_match('/specimen/i', $objectref)) {
			$dir .= "/" . $objectref;
		}
		$filename = str_replace('.twig', '', basename($srctemplatepath));
		if (getDolGlobalInt('EASYDOC_ADD_TEMPLATE_SUFFIX_TO_FILENAME')) {
			$file = $dir . "/" . $objectref . '_' . $filename . ".pdf";
		} else {
			$file = $dir . "/" . $objectref . ".pdf";
		}

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		$mpdf->Output($file, \Mpdf\Output\Destination::FILE);

		$parameters = [
			'file' => $file,
			'object' => $object,
			'outputlangs' => $outputlangs,
		];
		// Note that $action and $object may have been modified by some hooks
		$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);

		$this->result = ['fullpath' => $file];

		return 1;
	}
}
