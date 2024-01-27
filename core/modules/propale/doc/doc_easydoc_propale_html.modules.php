<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2021  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
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

require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';


/**
 *	Class to build documents using HTML templates
 */
class doc_easydoc_propale_html extends ModelePDFPropales
{
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
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "HTML templates";
		$this->description = $langs->trans("DocumentModelHtml");
		// Name of constant that is used to save list of directories to scan
		$this->scandir = 'PROPALE_ADDON_EASYDOC_HTML_PATH';
		// Save the name of generated file as the main doc when generating a doc with this template
		$this->update_main_doc_field = 1;

		// Page size for A4 format
		$this->type = 'html';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
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
		$langs->loadLangs(array("errors", "companies", "easydocgenerator@easydocgenerator"));

		$form = new Form($this->db);

		$text = $this->description . ".<br>\n";
		$text .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
		$text .= '<input type="hidden" name="token" value="' . newToken() . '">';
		$text .= '<input type="hidden" name="page_y" value="">';
		$text .= '<input type="hidden" name="action" value="setModuleOptions">';
		$text .= '<input type="hidden" name="param1" value="PROPALE_ADDON_EASYDOC_HTML_PATH">';
		$text .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$text .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectoriesForHtmlTemplates");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->PROPALE_ADDON_EASYDOC_HTML_PATH)));
		$listoffiles = array();
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
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(twig)');
				if (count($tmpfiles)) {
					$listoffiles = array_merge($listoffiles, $tmpfiles);
				}
			}
		}
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenHTML");
		$texthelp .= '<br><br><span class="opacitymedium">' . $langs->trans("ExampleOfDirectoriesForModelGen") . '</span>';
		// Add list of substitution keys
		$texthelp .= '<br>' . $langs->trans("FollowingSubstitutionKeysCanBeUsed") . '<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		$text .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$text .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$text .= '<textarea class="flat" cols="60" name="value1">';
		$text .= getDolGlobalString('PROPALE_ADDON_EASYDOC_HTML_PATH');
		$text .= '</textarea>';
		$text .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$text .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="' . dol_escape_htmltag($langs->trans("Modify")) . '">';
		$text .= '<br></div></div>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if (getDolGlobalString('PROPALE_ADDON_EASYDOC_HTML_PATH')) {
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
				$text .= ' &nbsp; <a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?modulepart=doctemplates&keyforuploaddir=PROPALE_ADDON_EASYDOC_HTML_PATH&action=deletefile&token=' . newToken() . '&file=' . urlencode(basename($file['name'])) . '">' . img_picto('', 'delete') . '</a>';
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
		$text .= '<input type="hidden" value="PROPALE_ADDON_EASYDOC_HTML_PATH" name="keyforuploaddir">';
		$text .= '<input type="submit" class="button reposition smallpaddingimp" value="' . dol_escape_htmltag($langs->trans("Upload")) . '" name="upload">';
		$text .= '</div>';

		$text .= '</td>';

		$text .= '</tr>';

		$text .= '</table>';
		$text .= '</form>';

		return $text;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Propale 	$object				Object source to build document
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
			dol_syslog("doc_easydoc_propale_html::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));
		global $action;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			// test it to crash...
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "propal", "deliveries"));

		require dol_buildpath('easydocgenerator/vendor/autoload.php');

		$loader = new \Twig\Loader\FilesystemLoader(dirname($srctemplatepath));
		$twig = new \Twig\Environment($loader, [
			// 'cache' => DOL_DATA_ROOT.'/easydocgenerator/temp',
			'cache' => false,
		]);
		$template = $twig->load(basename($srctemplatepath));

		if ($this->emetteur->logo) {
			$logodir = $conf->mycompany->dir_output;
			if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
				$logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
			} else {
				$logo = $logodir . '/logos/' . $this->emetteur->logo;
			}
		}
		$substitutions = [
			'mysoc' => [
				'name' => $mysoc->name,
				'name_alias' => $mysoc->name_alias,
				'address' => $mysoc->address,
				'zip' => $mysoc->zip,
				'town' => $mysoc->town,
				'country' => $mysoc->country,
				'phone' => $mysoc->phone,
				'email' => $mysoc->email,
				'idprof1' => $mysoc->idprof1,
				'idprof2' => $mysoc->idprof2,
				'idprof3' => $mysoc->idprof3,
				'idprof4' => $mysoc->idprof4,
				'idprof5' => $mysoc->idprof5,
				'idprof6' => $mysoc->idprof6,
			],
			'object' => [
				'date' => dol_print_date($object->date, 'day'),
				'ref' => $object->ref,
			],
			'doctitle' => $outputlangs->trans('PdfCommercialProposalTitle'),
			'ref_customer' => $outputlangs->transnoentities("RefCustomer"),
			'qty' => $outputlangs->transnoentitiesnoconv('Qty'),
			'ref' => $outputlangs->transnoentitiesnoconv('Ref'),
			'unitprice_ht' => $outputlangs->transnoentitiesnoconv('PriceUHT'),
			'total_ht' => $outputlangs->transnoentitiesnoconv('TotalHTShort'),
			'total_ttc' => $outputlangs->transnoentitiesnoconv('TotalTTCShort'),
			'vat' => $outputlangs->transnoentitiesnoconv('VAT'),
			'description' => $outputlangs->trans('Description'),
			'logo' => $logo,
			'freetext' => getDolGlobalString('PROPOSAL_FREE_TEXT'),
			'lines' => [],
		];
		foreach ($object->lines as $key => $line) {
			$substitutions['lines'][$key] = [
				'qty' => $line->qty,
				'ref' => $line->product_ref,
				'label' => $line->product_label,
				'description' => $line->product_desc,
				'subprice' => price($line->subprice),
				'total_ht' => price($line->total_ht),
				'total_ttc' => price($line->total_ttc),
				'vatrate' => price($line->tva_tx) . '%',
			];
		}

		$html = $template->render($substitutions);

		$mpdf = new \Mpdf\Mpdf([
			'margin_left' => 10,
			'margin_right' => 10,
			'margin_top' => 48,
			'margin_bottom' => 25,
			'margin_header' => 10,
			'margin_footer' => 10
		]);
		$mpdf->SetProtection(array('print'));
		$mpdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
		$mpdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$mpdf->SetWatermarkText(getDolGlobalString('PROPALE_DRAFT_WATERMARK'));

		$mpdf->showWatermarkText = ($object->statut == Propal::STATUS_DRAFT && getDolGlobalString('PROPALE_DRAFT_WATERMARK'));
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;

		$mpdf->SetDisplayMode('fullpage');

		$mpdf->Bookmark($outputlangs->trans('PdfCommercialProposalTitle'));
		$mpdf->WriteHTML($html);

		//If propal merge product PDF is active
		if (getDolGlobalString('PRODUIT_PDF_MERGE_PROPAL')) {
			require_once DOL_DOCUMENT_ROOT . '/product/class/propalmergepdfproduct.class.php';

			$already_merged = array();
			foreach ($object->lines as $line) {
				if (!empty($line->fk_product) && !(in_array($line->fk_product, $already_merged))) {
					// Find the desire PDF
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
									// stop adding header on every page
									$mpdf->SetHeader();
									$mpdf->Bookmark($linefile->file_name);
									// This PDF document probably uses a compression technique which is not supported by the free parser shipped with FPDI
									// Alternatively the document you're trying to import has to be resaved without the use of compressed cross-reference streams
									// and objects by an external programm (e.g. by lowering the PDF version to 1.4).
									$pagecount = $mpdf->setSourceFile($infile);
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
											setEventMessages(null, array($infile . ' cannot be added, probably protected PDF'), 'warnings');
										}
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
		$file = $dir . "/" . $objectref . ".pdf";

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		$mpdf->Output($file, \Mpdf\Output\Destination::FILE);

		return 1;

		if ($conf->propal->dir_output) {
			// If $object is id instead of object
			if (!is_object($object)) {
				$id = $object;
				$object = new Commande($this->db);
				$result = $object->fetch($id);
				if ($result < 0) {
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}

			$object->fetch_thirdparty();

			$dir = $conf->propal->multidir_output[$object->entity];
			$objectref = dol_sanitizeFileName($object->ref);
			if (!preg_match('/specimen/i', $objectref)) {
				$dir .= "/" . $objectref;
			}
			$file = $dir . "/" . $objectref . ".odt";

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir)) {
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile = basename($srctemplatepath);
				$newfiletmp = preg_replace('/\.od[ts]/i', '', $newfile);
				$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);
				$newfiletmp = $objectref . '_' . $newfiletmp;

				// Get extension (ods or odt)
				$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
				if (getDolGlobalInt('MAIN_DOC_USE_TIMING')) {
					$format = getDolGlobalInt('MAIN_DOC_USE_TIMING');
					if ($format == '1') {
						$format = '%Y%m%d%H%M%S';
					}
					$filename = $newfiletmp . '-' . dol_print_date(dol_now(), $format) . '.' . $newfileformat;
				} else {
					$filename = $newfiletmp . '.' . $newfileformat;
				}
				$file = $dir . '/' . $filename;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				dol_mkdir($conf->propal->dir_temp);
				if (!is_writable($conf->propal->dir_temp)) {
					$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $conf->propal->dir_temp);
					dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
					return -1;
				}

				// If CUSTOMER contact defined on propale, we use it
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
				$substitutionarray = array(
					'__FROM_NAME__' => $this->emetteur->name,
					'__FROM_EMAIL__' => $this->emetteur->email,
					'__TOTAL_TTC__' => $object->total_ttc,
					'__TOTAL_HT__' => $object->total_ht,
					'__TOTAL_VAT__' => $object->total_tva
				);
				complete_substitutions_array($substitutionarray, $langs, $object);
				// Call the ODTSubstitution hook
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Line of free text
				$newfreetext = '';
				$paramfreetext = 'PROPAL_FREE_TEXT';
				if (!empty($conf->global->$paramfreetext)) {
					$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray);
				}

				// Open and load template
				require_once ODTPHP_PATH . 'odf.php';
				try {
					$odfHandler = new Odf(
						$srctemplatepath,
						array(
							'PATH_TO_TMP'	  => $conf->propal->dir_temp,
							'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
							'DELIMITER_LEFT'  => '{',
							'DELIMITER_RIGHT' => '}'
						)
					);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
				// After construction $odfHandler->contentXml contains content and
				// [!-- BEGIN row.lines --]*[!-- END row.lines --] has been replaced by
				// [!-- BEGIN lines --]*[!-- END lines --]
				//print html_entity_decode($odfHandler->__toString());
				//print exit;


				// Make substitutions into odt of freetext
				try {
					$odfHandler->setVars('free_text', $newfreetext, true, 'UTF-8');
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}

				// Define substitution array
				$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
				$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
				$array_objet = $this->get_substitutionarray_object($object, $outputlangs);
				$array_user = $this->get_substitutionarray_user($user, $outputlangs);
				$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
				$array_thirdparty = $this->get_substitutionarray_thirdparty($socobject, $outputlangs);
				$array_other = $this->get_substitutionarray_other($outputlangs);
				// retrieve contact information for use in object as contact_xxx tags
				$array_thirdparty_contact = array();

				if ($usecontact && is_object($contactobject)) {
					$array_thirdparty_contact = $this->get_substitutionarray_contact($contactobject, $outputlangs, 'contact');
				}

				$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_user, $array_soc, $array_thirdparty, $array_objet, $array_other, $array_thirdparty_contact);
				complete_substitutions_array($tmparray, $outputlangs, $object);

				// Call the ODTSubstitution hook
				$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) { // Image
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value);
							} else {
								$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
							}
						} else { // Text
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}
				// Replace tags of lines
				try {
					$foundtagforlines = 1;
					try {
						$listlines = $odfHandler->setSegment('lines');
					} catch (OdfExceptionSegmentNotFound $e) {
						// We may arrive here if tags for lines not present into template
						$foundtagforlines = 0;
						dol_syslog($e->getMessage(), LOG_INFO);
					} catch (OdfException $e) {
						$foundtagforlines = 0;
						dol_syslog($e->getMessage(), LOG_INFO);
					}
					if ($foundtagforlines) {
						$linenumber = 0;
						foreach ($object->lines as $line) {
							$linenumber++;
							$tmparray = $this->get_substitutionarray_lines($line, $outputlangs, $linenumber);
							complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
							// Call the ODTSubstitutionLine hook
							$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
							$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
							foreach ($tmparray as $key => $val) {
								try {
									$listlines->setVars($key, $val, true, 'UTF-8');
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);
					}
				} catch (OdfException $e) {
					$this->error = $e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
				}

				// Replace labels translated
				$tmparray = $outputlangs->get_translations_for_substitutions();
				foreach ($tmparray as $key => $value) {
					try {
						$odfHandler->setVars($key, $value, true, 'UTF-8');
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// Call the beforeODTSave hook
				$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (getDolGlobalString('MAIN_ODT_AS_PDF')) {
					try {
						$odfHandler->exportAsAttachedPDF($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				} else {
					try {
						$odfHandler->saveToDisk($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}

				$parameters = array(
					'odfHandler' => &$odfHandler,
					'file' => $file,
					'object' => $object,
					'outputlangs' => $outputlangs,
					'substitutionarray' => &$tmparray
				);
				// Note that $action and $object may have been modified by some hooks
				$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action);

				dolChmod($file);

				$odfHandler = null; // Destroy object

				$this->result = array('fullpath' => $file);

				return 1; // Success
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		return -1;
	}
}
