<?php
/* Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
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
 * \file    class/EasyDocHtmlTrait.php
 * \ingroup easydocgenerator
 * \brief   Shared logic for all EasyDoc HTML/PDF document generators
 *
 * Each using class must set in its constructor:
 *   $this->scandir            — constant name for template path (e.g. 'INVOICE_ADDON_EASYDOC_TEMPLATES_PATH')
 *   $this->templateFileSection — URL section for document.php file listing (e.g. 'invoices')
 */

use NumberToWords\NumberToWords;

/**
 * Trait EasyDocHtmlTrait
 */
trait EasyDocHtmlTrait
{
	/**
	 * Return description of a module (admin settings page).
	 * Uses $this->scandir and $this->templateFileSection set by the constructor.
	 *
	 * @param Translate $langs Lang object
	 * @return string          HTML content
	 */
	public function info($langs)
	{
		global $conf, $langs;

		$langs->loadLangs(['errors', 'companies', 'easydocgenerator@easydocgenerator']);

		$form = new Form($this->db);

		$text = $this->description . ".<br>\n";
		$text .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
		$text .= '<input type="hidden" name="token" value="' . newToken() . '">';
		$text .= '<input type="hidden" name="page_y" value="">';
		$text .= '<input type="hidden" name="action" value="setModuleOptions">';
		$text .= '<input type="hidden" name="param1" value="' . $this->scandir . '">';
		$text .= '<table class="nobordernopadding" width="100%">';
		$text .= '<tr><td>';
		$texttitle = $langs->trans('ListOfDirectoriesForHtmlTemplates');
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($this->scandir))));
		$listoffiles = [];
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]);
				continue;
			}
			if (!is_dir($tmpdir)) {
				$texttitle .= img_warning($langs->trans('ErrorDirNotFound', $tmpdir), 0);
			} else {
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(twig)');
				if (count($tmpfiles)) {
					$listoffiles = array_merge($listoffiles, $tmpfiles);
				}
			}
		}
		$texthelp = $langs->trans('ListOfDirectoriesForModelGenHTML');
		$texthelp .= '<br><br><span class="opacitymedium">' . $langs->trans('ExampleOfDirectoriesForModelGen') . '</span>';
		$texthelp .= '<br>' . $langs->trans('FollowingSubstitutionKeysCanBeUsed') . '<br>';
		$texthelp .= $langs->transnoentitiesnoconv('FullListOnOnlineDocumentation');

		$text .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$text .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$text .= '<textarea class="flat" cols="60" name="value1">';
		$text .= getDolGlobalString($this->scandir);
		$text .= '</textarea>';
		$text .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$text .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="' . dol_escape_htmltag($langs->trans('Modify')) . '">';
		$text .= '<br></div></div>';

		$nbofiles = count($listoffiles);
		if (getDolGlobalString($this->scandir)) {
			$text .= $langs->trans('NumberOfModelHTMLFilesFound') . ': <b>';
			$text .= count($listoffiles);
			$text .= '</b>';
		}

		if ($nbofiles) {
			$text .= '<div id="div_' . get_class($this) . '" class="hiddenx">';
			foreach ($listoffiles as $file) {
				$text .= '- ' . $file['name'] . ' <a href="' . DOL_URL_ROOT . '/document.php?modulepart=doctemplates&file=' . $this->templateFileSection . '/' . urlencode(basename($file['name'])) . '">' . img_picto('', 'listlight') . '</a>';
				$url = $_SERVER['PHP_SELF'] . '?modulepart=doctemplates&keyforuploaddir=' . $this->scandir . '&action=deletefile&token=' . newToken() . '&file=' . urlencode(basename($file['name']));
				$text .= ' &nbsp; <a class="reposition" href="' . $url . '">' . img_picto('', 'delete') . '</a>';
				$text .= '<br>';
			}
			$text .= '</div>';
		}
		$text .= '<div>' . $langs->trans('UploadNewTemplate');
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			$text .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . ($maxmin * 1024) . '">';
		}
		$text .= ' <input type="file" name="uploadfile">';
		$text .= '<input type="hidden" value="' . $this->scandir . '" name="keyforuploaddir">';
		$text .= '<input type="submit" class="button reposition smallpaddingimp" value="' . dol_escape_htmltag($langs->trans('Upload')) . '" name="upload">';
		$text .= '</div>';
		$text .= '</td></tr></table></form>';

		return $text;
	}

	/**
	 * Build and return a configured Twig environment for the given template.
	 *
	 * @param string $srctemplatepath Full path to the .twig template file
	 * @return \Twig\Environment
	 */
	protected function buildTwigEnvironment(string $srctemplatepath): \Twig\Environment
	{
		require_once dol_buildpath('easydocgenerator/vendor/autoload.php');
		$md5id = md5_file($srctemplatepath);
		$loader = new \Twig\Loader\FilesystemLoader(dirname($srctemplatepath));
		$enablecache = getDolGlobalInt('EASYDOCGENERATOR_ENABLE_DEVELOPPER_MODE') ? false : (DOL_DATA_ROOT . '/easydocgenerator/temp/' . ($md5id ? $md5id : ''));
		$twig = new \Twig\Environment($loader, [
			'cache' => $enablecache,
			'autoescape' => false,
		]);

		$twig->addFunction(new \Twig\TwigFunction('trans', function ($value, $param1 = '', $param2 = '', $param3 = '') {
			global $outputlangs, $langs;
			if (!is_object($outputlangs)) {
				$outputlangs = $langs;
				$outputlangs->loadLangs(['main', 'dict', 'companies', 'bills', 'products', 'orders', 'deliveries', 'banks', 'compta', 'easydocgenerator@easydocgenerator']);
			}
			return $outputlangs->trans($value, $param1, $param2, $param3);
		}));

		$twig->addFunction(new \Twig\TwigFunction('transbis', function ($value, $param1 = '', $param2 = '', $param3 = '') {
			global $outputlangsbis, $langs;
			if (!is_object($outputlangsbis)) {
				$outputlangsbis = $langs;
				$outputlangsbis->loadLangs(['main', 'dict', 'companies', 'bills', 'products', 'orders', 'deliveries', 'banks', 'compta', 'easydocgenerator@easydocgenerator']);
			}
			return $outputlangsbis->trans($value, $param1, $param2, $param3);
		}));

		$twig->addFunction(new \Twig\TwigFunction('getDolGlobalString', function ($value, $default = '') {
			return getDolGlobalString($value, $default);
		}));

		$twig->addFunction(new \Twig\TwigFunction('date', function ($time, $format = '') {
			return dol_print_date($time, $format);
		}));

		$twig->addFunction(new \Twig\TwigFunction('price', function ($price) {
			global $outputlangs, $langs;
			return price($price, 0, $outputlangs);
		}));

		$twig->addFunction(new \Twig\TwigFunction('numbertowords', function ($number, $currency, $language) {
			$numbertow = new NumberToWords();
			$currencyTransformer = $numbertow->getCurrencyTransformer($language);
			return $currencyTransformer->toWords($number * 100, $currency);
		}));

		return $twig;
	}

	/**
	 * Compute the logo path.
	 *
	 * @return string
	 */
	protected function buildLogo(): string
	{
		global $conf;
		$logo = '';
		if (!empty($this->emetteur->logo)) {
			$logodir = $conf->mycompany->dir_output;
			$logo = getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')
				? $logodir . '/logos/' . $this->emetteur->logo
				: $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
		}
		return $logo;
	}

	/**
	 * Compute the mysoc flag image code.
	 *
	 * @return string
	 */
	protected function buildFlagImage(): string
	{
		global $mysoc;
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
			'SX' => 'unknown',
		];
		if (isset($langtocountryflag[$mysoc->country_code])) {
			return $langtocountryflag[$mysoc->country_code];
		}
		$tmparray = explode('_', $mysoc->country_code);
		return empty($tmparray[1]) ? $tmparray[0] : $tmparray[1];
	}

	/**
	 * Build mysoc substitutions.
	 *
	 * @param Translate $outputlangs
	 * @param string    $flagImage
	 * @return array
	 */
	protected function buildMysocSubstitutions($outputlangs, string $flagImage): array
	{
		global $mysoc;
		$substitutions = getEachVarObject($mysoc, $outputlangs, 1, 'mysoc');
		$substitutions['mysoc']['flag'] = DOL_DOCUMENT_ROOT . '/theme/common/flags/' . strtolower($flagImage) . '.png';
		$substitutions['mysoc']['phone_formatted'] = dol_print_phone($mysoc->phone, $mysoc->country_code, 0, 0, '', ' ');
		$substitutions['mysoc']['phone_mobile_formatted'] = dol_print_phone($mysoc->phone_mobile ?? '', $mysoc->country_code, 0, 0, '', ' ');
		$substitutions['mysoc']['fax_formatted'] = dol_print_phone($mysoc->fax, $mysoc->country_code, 0, 0, '', ' ');
		return $substitutions;
	}

	/**
	 * Build thirdparty substitutions (only when $object->thirdparty is set).
	 *
	 * @param CommonObject $object
	 * @param Translate    $outputlangs
	 * @return array
	 */
	protected function buildThirdpartySubstitutions($object, $outputlangs): array
	{
		if (empty($object->thirdparty) || !is_object($object->thirdparty)) {
			return [];
		}
		$substitutions = getEachVarObject($object->thirdparty, $outputlangs, 1, 'thirdparty');
		$substitutions['thirdparty']['flag'] = DOL_DOCUMENT_ROOT . '/theme/common/flags/' . strtolower($object->thirdparty->country_code) . '.png';
		$substitutions['thirdparty']['phone_formatted'] = dol_print_phone($object->thirdparty->phone, $object->thirdparty->country_code, 0, 0, '', ' ');
		$substitutions['thirdparty']['fax_formatted'] = dol_print_phone($object->thirdparty->fax, $object->thirdparty->country_code, 0, 0, '', ' ');
		return $substitutions;
	}

	/**
	 * Build contacts substitutions with photo resolution (invoice/order/expedition/propale).
	 *
	 * Produces $contacts['{type}_{scope}'][$contactId] = [...fields...].
	 *
	 * @param CommonObject $object
	 * @param Translate    $outputlangs
	 * @param array        $typescontact ['external' => [...], 'internal' => [...]]
	 * @return array
	 */
	protected function buildContactsSubstitutions($object, $outputlangs, array $typescontact): array
	{
		global $conf;
		$contacts = [];
		foreach ($typescontact as $key => $value) {
			foreach ($value as $type) {
				$arrayidcontact = $object->getIdContact($key, $type);
				foreach ($arrayidcontact as $idc) {
					$contact = ($key === 'external') ? new Contact($this->db) : new User($this->db);
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
							$contacts[strtolower($type) . '_' . $key][$jdx]['picture'] = $conf->{$substitution['element']}->multidir_output[$conf->entity] . '/' . $substitution['id'] . '/photos/' . $substitution['photo'];
						}
					}
				}
			}
		}
		return $contacts;
	}

	/**
	 * Build contacts substitutions, simple version (contract/stock/ticket).
	 *
	 * Produces $substitutions['{type}_{scope}'][0..n] = [...fields...].
	 *
	 * @param CommonObject $object
	 * @param Translate    $outputlangs
	 * @param array        $typescontact ['external' => [...], 'internal' => [...]]
	 * @return array
	 */
	protected function buildContactsSubstitutionsSimple($object, $outputlangs, array $typescontact): array
	{
		$substitutions = [];
		foreach ($typescontact as $key => $value) {
			foreach ($value as $type) {
				$arrayidcontact = $object->getIdContact($key, $type);
				$contacts = [];
				foreach ($arrayidcontact as $idc) {
					$contact = ($key === 'external') ? new Contact($this->db) : new User($this->db);
					$contact->fetch($idc);
					$contacts[] = $contact;
				}
				$substitutions = array_merge($substitutions, getEachVarObject($contacts, $outputlangs, 1, strtolower($type) . '_' . $key));
			}
		}
		return $substitutions;
	}

	/**
	 * Build lines substitutions from $object->lines (standard, no photos/categories).
	 *
	 * @param CommonObject $object
	 * @param Translate    $outputlangs
	 * @param bool         $fetchProduct Fetch and embed product data for each line
	 * @return array
	 */
	protected function buildLinesSubstitutions($object, $outputlangs, bool $fetchProduct = false): array
	{
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
			if ($fetchProduct && $line->fk_product > 0) {
				$product = new Product($this->db);
				$product->fetch($line->fk_product);
				$linesarray[$key]['product'] = getEachVarObject($product, $outputlangs)['object'];
			}
			if (empty($line->special_code)) {
				$linenumber++;
			}
		}
		return $linesarray;
	}

	/**
	 * Build lines substitutions with product photos and categories (expedition/propale).
	 *
	 * @param CommonObject $object
	 * @param Translate    $outputlangs
	 * @param bool         $recursive Passed to getEachVarObject for line fields (true=expedition, false=propale)
	 * @return array
	 */
	protected function buildExtendedLinesSubstitutions($object, $outputlangs, bool $recursive = true): array
	{
		global $conf;
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
			$linearray = getEachVarObject($line, $outputlangs, (int) $recursive, 'line');
			$linesarray[$key] = $linearray['line'];
			$linesarray[$key]['linenumber'] = $linenumber;
			$linesarray[$key]['subtotal_ht'] = $subtotal_ht;
			if ($line->fk_product > 0) {
				$product = new Product($this->db);
				$product->fetch($line->fk_product);
				$linesarray[$key]['product'] = getEachVarObject($product, $outputlangs)['object'];
				$cat = new Categorie($this->db);
				$linesarray[$key]['categories'] = $cat->getListForItem($line->fk_product, 'product');
				$linesarray[$key]['photos'] = [];
				$pdir = [];
				if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
					$pdir[0] = get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id . '/photos/';
					$pdir[1] = get_exdir(0, 0, 0, 0, $product, 'product') . dol_sanitizeFileName($product->ref) . '/';
				} else {
					$pdir[0] = get_exdir(0, 0, 0, 0, $product, 'product');
					$pdir[1] = get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id . '/photos/';
				}
				foreach ($pdir as $midir) {
					$dir = ($conf->entity != $product->entity)
						? $conf->product->multidir_output[$product->entity] . '/' . $midir
						: $conf->product->dir_output . '/' . $midir;
					foreach ($product->liste_photos($dir, 1) as $obj) {
						$linesarray[$key]['photos'][] = $dir . $obj['photo'];
					}
				}
			}
			if (empty($line->special_code)) {
				$linenumber++;
			}
		}
		return $linesarray;
	}

	/**
	 * Save the PDF to disk, fire afterPDFCreation hook, set $this->result.
	 *
	 * The caller must have already called $mpdf->WriteHTML($html) before calling this method.
	 *
	 * @param \Mpdf\Mpdf   $mpdf
	 * @param CommonObject $object
	 * @param Translate    $outputlangs
	 * @param string       $dir             Base output directory (e.g. $conf->facture->multidir_output[$entity])
	 * @param string       $srctemplatepath Full path to the .twig source template
	 * @return int 1 on success, -1 on error
	 */
	protected function savePdf(\Mpdf\Mpdf $mpdf, $object, $outputlangs, string $dir, string $srctemplatepath): int
	{
		global $action, $langs, $hookmanager, $outputlangsbis;

		$objectref = dol_sanitizeFileName($object->ref);
		if (!preg_match('/specimen/i', $objectref)) {
			$dir .= '/' . $objectref;
		}
		$filename = str_replace('.twig', '', basename($srctemplatepath));
		$file = getDolGlobalInt('EASYDOC_ADD_TEMPLATE_SUFFIX_TO_FILENAME')
			? $dir . '/' . $objectref . '_' . $filename . '.pdf'
			: $dir . '/' . $objectref . '.pdf';

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
				return -1;
			}
		}

		$mpdf->Output($file, \Mpdf\Output\Destination::FILE);

		$parameters = [
			'file' => $file,
			'object' => $object,
			'outputlangs' => $outputlangs,
			'outputlangsbis' => $outputlangsbis,
		];
		$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);

		$this->result = ['fullpath' => $file];
		return 1;
	}
}
