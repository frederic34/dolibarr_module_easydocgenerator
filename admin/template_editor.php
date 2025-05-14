<?php
/* Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
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
 * \file    htdocs/modulebuilder/template/admin/setup.php
 * \ingroup easydocgenerator
 * \brief   Easydocgenerator setup page.
 */

if (!defined('NOSCANPOSTFORINJECTION')) {
	define('NOSCANPOSTFORINJECTION', '1'); // Do not check anti SQL+XSS injection attack test
}
// Load Dolibarr environment
include '../config.php';

global $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once '../lib/easydocgenerator.lib.php';

$action  = GETPOST('action', 'aZ09');
$file = GETPOST('file', 'alpha');
$fullfilenameid = GETPOSTINT('fullfilenameid');
$now = dol_now();
$newmask = getDolGlobalString('MAIN_UMASK', '0664');

// Translations
$langs->loadLangs(['admin', 'companies', 'languages', 'members', 'other', 'products', 'stocks', 'trips', 'easydocgenerator@easydocgenerator']);

// Access control
if (!$user->admin) {
	accessforbidden();
}
$constants = [
	"BOM_ADDON_EASYDOC_TEMPLATES_PATH",
	"CONTRACT_ADDON_EASYDOC_TEMPLATES_PATH",
	"EXPEDITION_ADDON_EASYDOC_TEMPLATES_PATH",
	"EXPENSEREPORT_ADDON_EASYDOC_TEMPLATES_PATH",
	"INTERVENTION_ADDON_EASYDOC_TEMPLATES_PATH",
	"ORDER_ADDON_EASYDOC_TEMPLATES_PATH",
	"SUPPLIER_ORDER_ADDON_EASYDOC_TEMPLATES_PATH",
	"INVOICE_ADDON_EASYDOC_TEMPLATES_PATH",
	"PRODUCT_ADDON_EASYDOC_TEMPLATES_PATH",
	"PROPALE_ADDON_EASYDOC_TEMPLATES_PATH",
	"SHIPPING_ADDON_EASYDOC_TEMPLATES_PATH",
	"STOCK_ADDON_EASYDOC_TEMPLATES_PATH",
	"TICKET_ADDON_EASYDOC_TEMPLATES_PATH",
];
$listoffiles = [];
$arrayselect = [];
foreach ($constants as $constant) {
	$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($constant))));
	foreach ($listofdir as $key => $tmpdir) {
		$tmpdir = trim($tmpdir);
		$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
		if (!$tmpdir) {
			unset($listofdir[$key]);
			continue;
		}
		if (!is_dir($tmpdir)) {
			setEventMessage($langs->trans("ErrorDirNotFound", $tmpdir), 0);
		} else {
			$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '');
			if (count($tmpfiles)) {
				$listoffiles = array_merge($listoffiles, $tmpfiles);
				foreach ($tmpfiles as $item) {
					$arrayselect[] = $item['fullname'];
				}
			}
		}
	}
}


// Save file
if ($action == 'savefile' && empty($cancel)) {
	// Save old version
	if (dol_is_file($file)) {
		// dol_copy($file, $file . '.back', 0, 1, 0, 1);
	}

	$content = GETPOST('editfilecontent', 'none');
	// Save file on disk
	if ($content) {
		dol_delete_file($file, 0, 0, 0, null, false, 1);
		$result = file_put_contents($file, $content);
		if ($result) {
			dolChmod($file, $newmask);

			setEventMessages($langs->trans("FileSaved"), null);
		} else {
			setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
		}
	} else {
		setEventMessages($langs->trans("ContentCantBeEmpty"), null, 'errors');
		$action = '';
	}
}
if ($action == 'selectfile') {
	$file = $listoffiles[$fullfilenameid]['name'];
	$fullpathoffile = $listoffiles[$fullfilenameid]['fullname'];
} else {
	if (GETPOST('file', 'alpha')) {
		$file = GETPOST('file', 'alpha');
		$fullpathoffile = $listoffiles[$fullfilenameid]['fullname'];
	} else {
		$file = $listoffiles[0]['name'];
		$fullpathoffile = $listoffiles[0]['fullname'];
	}
}

$content = '';
if ($fullpathoffile) {
	$content = file_get_contents($fullpathoffile);
}

/*
 * View
 */
$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	//'/includes/ace/src/ext-chromevox.js'
];
$morecss = [];
llxHeader('', $langs->trans("TemplateEditor"), '', '', 0, 0, $morejs, $morecss, '', 'classforhorizontalscrolloftabs');
$form = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">';
$linkback .= $langs->trans("BackToModuleList") . '</a>';
//print load_fiche_titre($langs->trans('EasydocgeneratorConfig'), $linkback, 'tools');

$head = easydocgeneratorAdminPrepareHead();
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="selectfile">';
print $form->selectarray('fullfilenameid', $arrayselect, $fullfilenameid);
print '<input type="submit" class="button buttonforacesave button-save" id="selectfile" name="selectfile" value="' . dol_escape_htmltag($langs->trans("SelectFile")) . '">';
print '</form>';

// New module
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="savefile">';
print '<input type="hidden" name="fullfilenameid" value="'.$fullfilenameid.'">';
print '<input type="hidden" name="file" value="' . dol_escape_htmltag($fullpathoffile) . '">';


print dol_get_fiche_head($head, 'editor', $langs->trans('Settings'), -1, 'technic');

$doleditor = new DolEditor('editfilecontent', $content, '', '500', 'Full', 'In', true, false, 'ace', 0, '99%', '');
print $doleditor->Create(1, '', false, $langs->trans("File") . ' : ' . $file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'twig'));

print dol_get_fiche_end();

print '<center>';
print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="' . dol_escape_htmltag($langs->trans("Save")) . '">';
print ' &nbsp; ';
print '<input type="submit" class="button button-cancel" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '">';
print '</center>';

print '</form>';
// End of page
llxFooter();
$db->close();
