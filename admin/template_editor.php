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

// Load Dolibarr environment
include '../config.php';

global $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/easydocgenerator.lib.php';

// Translations
$langs->loadLangs(['admin', 'companies', 'languages', 'members', 'other', 'products', 'stocks', 'trips', 'easydocgenerator@easydocgenerator']);

// Access control
if (!$user->admin) {
	accessforbidden();
}

/*
 * View
 */

llxHeader();
$form = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">';
$linkback .= $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('EasydocgeneratorConfig'), $linkback, 'tools');

$head = easydocgeneratorAdminPrepareHead();

print dol_get_fiche_head($head, 'editor', $langs->trans('Settings'), -1, 'technic');


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
