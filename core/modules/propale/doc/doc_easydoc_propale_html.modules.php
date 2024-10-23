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
 *	\file       htdocs/core/modules/propal/doc/doc_easydoc_propal_html.modules.php
 *	\ingroup    propal
 *	\brief      File of class to build PDF documents for propales
 */

use NumberToWords\NumberToWords;

require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
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
class doc_easydoc_propale_html extends ModelePDFPropales
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
		$langs->loadLangs(['main', 'companies', "easydocgenerator@easydocgenerator"]);

		$this->db = $db;
		$this->name = "Easydoc templates";
		$this->description = $langs->trans("DocumentModelEasydocgeneratorTemplate");
		// Name of constant that is used to save list of directories to scan
		$this->scandir = 'PROPALE_ADDON_EASYDOC_TEMPLATES_PATH';
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
		$text .= '<input type="hidden" name="param1" value="PROPALE_ADDON_EASYDOC_TEMPLATES_PATH">';
		$text .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$text .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectoriesForHtmlTemplates");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->PROPALE_ADDON_EASYDOC_TEMPLATES_PATH)));
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
		$text .= getDolGlobalString('PROPALE_ADDON_EASYDOC_TEMPLATES_PATH');
		$text .= '</textarea>';
		$text .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$text .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="' . dol_escape_htmltag($langs->trans("Modify")) . '">';
		$text .= '<br></div></div>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if (getDolGlobalString('PROPALE_ADDON_EASYDOC_TEMPLATES_PATH')) {
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
				$text .= '- ' . $file['name'] . ' <a href="' . DOL_URL_ROOT . '/document.php?modulepart=doctemplates&file=propales/' . urlencode(basename($file['name'])) . '">' . img_picto('', 'listlight') . '</a>';
				$text .= ' &nbsp; <a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?modulepart=doctemplates&keyforuploaddir=PROPALE_ADDON_EASYDOC_TEMPLATES_PATH&action=deletefile&token=' . newToken() . '&file=' . urlencode(basename($file['name'])) . '">' . img_picto('', 'delete') . '</a>';
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
		$text .= '<input type="hidden" value="PROPALE_ADDON_EASYDOC_TEMPLATES_PATH" name="keyforuploaddir">';
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
	 *	@param		Propal  	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_easydoc_propale_html::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		$object->fetch_thirdparty();

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->loadLangs(['main', 'dict', 'companies', 'propal', 'bills', 'products', 'orders', 'deliveries', 'banks', 'easydocgenerator@easydocgenerator']);

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(['main', 'dict', 'companies', 'propal', 'bills', 'products', 'orders', 'deliveries', 'banks']);
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
				$outputlangs->loadLangs(['main', 'dict', 'companies', 'propal', 'bills', 'products', 'orders', 'deliveries', 'banks']);
			}
			return $outputlangs->trans($value, $param1, $param2, $param3);
		});
		$twig->addFunction($function);
		// create twig function which translate with $outpulangsbis->trans()
		$function = new \Twig\TwigFunction('transbis', function ($value, $param1 = '', $param2 = '', $param3 = '') {
			global $outputlangsbis, $langs;
			if (!is_object($outputlangsbis)) {
				$outputlangsbis = $langs;
				$outputlangsbis->loadLangs(['main', 'dict', 'companies', 'propal', 'bills', 'products', 'orders', 'deliveries', 'banks']);
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
			global $outputlangs, $langs;
			return price($price, 0, $outputlangs);
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
		// Line of free text
		$newfreetext = '';
		$paramfreetext = 'PROPOSAL_FREE_TEXT';
		if (!empty($conf->global->$paramfreetext)) {
			$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray);
		}
		// mysoc
		$substitutions = getEachVarObject($mysoc, $outputlangs, 1, 'mysoc');
		$substitutions['mysoc']['flag'] = DOL_DOCUMENT_ROOT . '/theme/common/flags/' . strtolower($flagImage) . '.png';
		$substitutions['mysoc']['phone_formatted'] = dol_print_phone($mysoc->phone, $mysoc->country_code, 0, 0, '', ' ');
		$substitutions['mysoc']['fax_formatted'] = dol_print_phone($mysoc->fax, $mysoc->country_code, 0, 0, '', ' ');

		// object
		$substitutions = array_merge($substitutions, getEachVarObject($object, $outputlangs, 0));

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
		$contacts = [];
		foreach ($typescontact as $key => $value) {
			foreach ($value as $idx => $type) {
				$arrayidcontact = $object->getIdContact($key, $type);
				foreach ($arrayidcontact as $idc) {
					if ($key == 'external') {
						$contact = new Contact($this->db);
					} else {
						$contact = new User($this->db);
					}
					$res = $contact->fetch($idc);
					if ($res < 0) {
						setEventMessages($contact->error, $contact->errors, 'errors');
					} else {
						$contacts[strtolower($type) . '_' . $key][$idc] = getEachVarObject($contact, $outputlangs, 0, $idc)[$idc];
					}
				}
				if (!empty($contacts[strtolower($type) . '_' . $key])) {
					foreach ($contacts[strtolower($type) . '_' . $key] as $jdx => $substitution) {
						if (!empty($substitution['photo'])) {
							$contacts[strtolower($type) . '_' . $key][$jdx]['picture'] = $conf->{$substitution['element']}->multidir_output[$conf->entity] . '/' . $substitution['ref'] . '/' . $substitution['photo'];
						}
					}
				}
			}
		}
		$substitutions = array_merge($substitutions, $contacts);

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
			$linesarray[$key]['subtotal_ht'] = $subtotal_ht;
			// $substitutions['lines'][$key] = [
			// 	'linenumber' => $linenumber,
			// 	'qty' => $line->qty,
			// 	'ref' => $line->product_ref,
			// 	'label' => $line->label,
			// 	'description' => $line->desc,
			// 	'product_label' => $line->product_label,
			// 	'product_description' => $line->product_desc,
			// 	'subprice' => $line->subprice,
			// 	'total_ht' => $line->total_ht,
			// 	'total_ttc' => $line->total_ttc,
			// 	'vatrate' => $line->tva_tx,
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
		$mpdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("PdfCommercialProposalTitle") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
		// Watermark
		$text = getDolGlobalString('PROPALE_DRAFT_WATERMARK');
		$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, null);
		complete_substitutions_array($substitutionarray, $outputlangs, null);
		$text = make_substitutions($text, $substitutionarray, $outputlangs);
		$mpdf->SetWatermarkText($text);
		$mpdf->showWatermarkText = ($object->status == Propal::STATUS_DRAFT && getDolGlobalString('PROPALE_DRAFT_WATERMARK'));
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;

		$mpdf->SetDisplayMode('fullpage');

		$mpdf->Bookmark($outputlangs->trans('PdfCommercialProposalTitle'));
		$mpdf->WriteHTML($html);

		//If propal merge product PDF is active
		if (getDolGlobalString('PRODUIT_PDF_MERGE_PROPAL')) {
			require_once DOL_DOCUMENT_ROOT . '/product/class/propalmergepdfproduct.class.php';
			$already_merged = [];
			foreach ($object->lines as $line) {
				if (!empty($line->fk_product) && !(in_array($line->fk_product, $already_merged))) {
					// Find the desired PDF
					$filetomerge = new Propalmergepdfproduct($this->db);

					if (getDolGlobalInt('MAIN_MULTILANGS')) {
						$filetomerge->fetch_by_product($line->fk_product, $outputlangs->defaultlang);
					} else {
						$filetomerge->fetch_by_product($line->fk_product);
					}

					$already_merged[] = $line->fk_product;

					$product = new Product($this->db);
					$product->fetch($line->fk_product);

					if ($product->entity != $conf->entity) {
						$entity_product_file = $product->entity;
					} else {
						$entity_product_file = $conf->entity;
					}
					// If PDF is selected and file is not empty
					if (count($filetomerge->lines) > 0) {
						foreach ($filetomerge->lines as $linefile) {
							if (!empty($linefile->id) && !empty($linefile->file_name)) {
								if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
									if (isModEnabled("product")) {
										$filetomerge_dir = $conf->product->multidir_output[$entity_product_file] . '/' . get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id . "/photos";
									} elseif (isModEnabled("service")) {
										$filetomerge_dir = $conf->service->multidir_output[$entity_product_file] . '/' . get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id . "/photos";
									}
								} else {
									if (isModEnabled("product")) {
										$filetomerge_dir = $conf->product->multidir_output[$entity_product_file] . '/' . get_exdir(0, 0, 0, 0, $product, 'product');
									} elseif (isModEnabled("service")) {
										$filetomerge_dir = $conf->service->multidir_output[$entity_product_file] . '/' . get_exdir(0, 0, 0, 0, $product, 'product');
									}
								}

								dol_syslog(get_class($this) . ':: upload_dir=' . $filetomerge_dir, LOG_DEBUG);

								$infile = $filetomerge_dir . $linefile->file_name;
								if (file_exists($infile) && is_readable($infile)) {
									try {
										$pagecount = $mpdf->setSourceFile($infile);
										// stop adding header on every page
										$mpdf->SetHeader();
										$mpdf->Bookmark($linefile->file_name);
										// This PDF document probably uses a compression technique which is not supported by the free parser shipped with FPDI
										// Alternatively the document you're trying to import has to be resaved without the use of compressed cross-reference streams
										// and objects by an external programm (e.g. by lowering the PDF version to 1.4).
										for ($i = 1; $i <= $pagecount; $i++) {
											$tplIdx = $mpdf->importPage($i);
											if ($tplIdx !== false) {
												$s = $mpdf->getTemplatesize($tplIdx);
												// array (size=5)
												//   'width' => float 210.00155555556
												//   'height' => float 296.99655555556
												//   0 => float 210.00155555556
												//   1 => float 296.99655555556
												//   'orientation' => string 'P' (length=1)
												$mpdf->AddPage($s['height'] > $s['width'] ? 'P' : 'L');
												$mpdf->useTemplate($tplIdx);
											} else {
												setEventMessages(null, [$infile . ' cannot be added, probably protected PDF'], 'warnings');
											}
										}
									} catch (Exception $e) {
										setEventMessage($langs->trans('EasydoCantAddPdfToDoc', $linefile->file_name), 'errors');
										setEventMessage($e->getMessage(), 'errors');
									}
								}
							}
						}
					}
				}
			}
		}

		$dir = $conf->propal->multidir_output[$object->entity];
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
