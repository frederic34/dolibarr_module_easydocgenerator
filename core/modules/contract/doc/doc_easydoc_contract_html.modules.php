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
 *	\file       htdocs/core/modules/contract/doc/doc_easydoc_contract_html.modules.php
 *	\ingroup    contract
 *	\brief      File of class to build PDF documents for contracts
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
dol_include_once('/easydocgenerator/lib/easydocgenerator.lib.php');
dol_include_once('/easydocgenerator/class/EasyDocHtmlTrait.php');

// phpcs:disable
/**
 *	Class to build documents using HTML templates
 */
class doc_easydoc_contract_html extends ModelePDFContract
{
	// phpcs:enable
	use EasyDocHtmlTrait;

	/** @var string Dolibarr version of the loaded document */
	public $version = 'dolibarr';

	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		$langs->loadLangs(['main', 'companies', 'easydocgenerator@easydocgenerator']);

		$this->db = $db;
		$this->name = 'Easydoc templates';
		$this->description = $langs->trans('DocumentModelEasydocgeneratorTemplate');
		$this->scandir = 'CONTRACT_ADDON_EASYDOC_TEMPLATES_PATH';
		$this->templateFileSection = 'contracts';
		$this->update_main_doc_field = 1;

		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = [$this->page_largeur, $this->page_hauteur];
		$this->marge_gauche = getDolGlobalInt('EASYDOC_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('EASYDOC_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('EASYDOC_PDF_MARGIN_TOP', 48);
		$this->marge_basse = getDolGlobalInt('EASYDOC_PDF_MARGIN_BOTTOM', 48);

		$this->option_logo = 1;
		$this->option_tva = 0;
		$this->option_modereg = 0;
		$this->option_condreg = 0;
		$this->option_multilang = 1;
		$this->option_escompte = 0;
		$this->option_credit_note = 0;
		$this->option_freetext = 1;
		$this->option_draft_watermark = 0;

		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2);
		}
	}

	// phpcs:disable
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Contrat 	$object				Object source to build document
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
		global $action, $langs, $conf, $mysoc, $hookmanager, $user;

		if (empty($srctemplatepath)) {
			dol_syslog('doc_easydoc_contract_html::write_file parameter srctemplatepath empty', LOG_WARNING);
			return -1;
		}

		$object->fetch_thirdparty();

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$langfiles = ['main', 'dict', 'companies', 'bills', 'products', 'contracts', 'deliveries', 'banks', 'easydocgenerator@easydocgenerator'];
		$outputlangs->loadLangs($langfiles);

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs($langfiles);
		}

		$linkedObjects = pdf_getLinkedObjects($object, $outputlangs);
		$outputlangs->charset_output = 'UTF-8';
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$currency = !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(['pdfgeneration']);
		$parameters = ['object' => $object, 'outputlangs' => $outputlangs, 'outputlangsbis' => $outputlangsbis];
		$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

		$twig = $this->buildTwigEnvironment($srctemplatepath);
		try {
			$template = $twig->load(basename($srctemplatepath));
		} catch (\Twig\Error\SyntaxError $e) {
			$this->errors = $e->getMessage() . ' at line ' . $e->getLine() . ' in file ' . $e->getFile();
			return -1;
		} catch (Exception $e) {
			$this->errors = $e->getMessage();
			return -1;
		}

		$logo = $this->buildLogo();
		$flagImage = $this->buildFlagImage();

		$substitutionarray = [
			'__FROM_NAME__' => $this->emetteur->name,
			'__FROM_EMAIL__' => $this->emetteur->email,
			'__TOTAL_TTC__' => $object->total_ttc,
			'__TOTAL_HT__' => $object->total_ht,
			'__TOTAL_VAT__' => $object->total_tva,
		];
		complete_substitutions_array($substitutionarray, $langs, $object);
		$substitutionarray = array_merge(getCommonSubstitutionArray($outputlangs, 0, null, $object), $substitutionarray);
		$parameters = ['object' => $object, 'outputlangs' => $outputlangs, 'outputlangsbis' => $outputlangsbis, 'substitutionarray' => &$substitutionarray];
		$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);

		$paramfreetext = 'CONTRACT_FREE_TEXT';
		$newfreetext = '';
		if (!empty($conf->global->$paramfreetext)) {
			$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray);
		}

		$substitutions = $this->buildMysocSubstitutions($outputlangs, $flagImage);
		$substitutions = array_merge($substitutions, getEachVarObject($object, $outputlangs, 0));
		$substitutions = array_merge($substitutions, $this->buildThirdpartySubstitutions($object, $outputlangs));

		$typescontact = [
			'external' => ['BILLING', 'SHIPPING', 'SALESREPFOLL', 'CUSTOMER'],
			'internal' => ['BILLING', 'SHIPPING', 'SALESREPFOLL', 'CUSTOMER'],
		];
		$substitutions = array_merge($substitutions, $this->buildContactsSubstitutionsSimple($object, $outputlangs, $typescontact));

		$substitutions = array_merge($substitutions, [
			'logo' => $logo,
			'freetext' => $newfreetext,
			'linkedObjects' => $linkedObjects,
			'footerinfo' => getPdfPagefoot($outputlangs, $paramfreetext, $mysoc, $object),
			'currency' => $currency,
			'currencyinfo' => $outputlangs->trans('AmountInCurrency', $outputlangs->trans('Currency' . $currency)),
		]);

		$substitutions = array_merge($substitutions, ['lines' => $this->buildLinesSubstitutions($object, $outputlangs)]);

		$substitutions['parameters'] = ['hidedetails' => $hidedetails, 'hidedesc' => $hidedesc, 'hideref' => $hideref];
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

		$mpdf = new \Mpdf\Mpdf([
			'tempDir' => DOL_DATA_ROOT . '/easydocgenerator/temp',
			'format' => $this->format,
			'margin_left' => $this->marge_gauche,
			'margin_right' => $this->marge_droite,
			'margin_top' => $this->marge_haute,
			'margin_bottom' => $this->marge_basse,
			'margin_header' => getDolGlobalInt('EASYDOC_PDF_MARGIN_HEADER', 10),
			'margin_footer' => getDolGlobalInt('EASYDOC_PDF_MARGIN_FOOTER', 10),
		]);
		$mpdf->SetProtection(['print']);
		$mpdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
		$mpdf->SetCreator('Dolibarr ' . DOL_VERSION);
		$mpdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$mpdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . ' ' . $outputlangs->transnoentities('Contract') . ' ' . $outputlangs->convToOutputCharset($object->thirdparty->name));
		$text = getDolGlobalString('CONTRACT_DRAFT_WATERMARK');
		$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, null);
		complete_substitutions_array($substitutionarray, $outputlangs, null);
		$text = make_substitutions($text, $substitutionarray, $outputlangs);
		$mpdf->SetWatermarkText($text);
		$mpdf->showWatermarkText = ($object->status == Contrat::STATUS_DRAFT && getDolGlobalString('CONTRACT_DRAFT_WATERMARK'));
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');
		$mpdf->Bookmark($outputlangs->trans('PdfOrderTitle'));
		$mpdf->WriteHTML($html);

		return $this->savePdf($mpdf, $object, $outputlangs, $conf->contract->multidir_output[$object->entity], $srctemplatepath);
	}
}
