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
$now = dol_now();
$newmask = 0;
if (empty($newmask) && getDolGlobalString('MAIN_UMASK')) {
	$newmask = getDolGlobalString('MAIN_UMASK');
}
if (empty($newmask)) {	// This should no happen
	$newmask = '0664';
}

// Translations
$langs->loadLangs(['admin', 'companies', 'languages', 'members', 'other', 'products', 'stocks', 'trips', 'easydocgenerator@easydocgenerator']);

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Save file
if ($action == 'savefile' && empty($cancel)) {
	$pathoffile = DOL_DATA_ROOT . $file;

	// Save old version
	if (dol_is_file($pathoffile)) {
		dol_copy($pathoffile, $pathoffile . '.back', 0, 1);
	}

	$content = GETPOST('editfilecontent', 'none');

	// Save file on disk
	if ($content) {
		dol_delete_file($pathoffile);
		$result = file_put_contents($pathoffile, $content);
		if ($result) {
			dolChmod($pathoffile, $newmask);

			setEventMessages($langs->trans("FileSaved"), null);
		} else {
			setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
		}
	} else {
		setEventMessages($langs->trans("ContentCantBeEmpty"), null, 'errors');
		//$action='editfile';
		$error++;
	}
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


$file = '/ecm/easydocgenerator/invoices/easydoc_invoice';

$fullpathoffile = DOL_DATA_ROOT . $file;
$content = '';
if ($fullpathoffile) {
	$content = file_get_contents($fullpathoffile);
}

// New module
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="savefile">';
print '<input type="hidden" name="file" value="' . dol_escape_htmltag($file) . '">';


print dol_get_fiche_head($head, 'editor', $langs->trans('Settings'), -1, 'technic');

$doleditor = new DolEditor('editfilecontent', $content, '', '500', 'Full', 'In', true, false, 'ace', 0, '99%', '');
print $doleditor->Create(1, '', false, $langs->trans("File") . ' : ' . $file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));

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
