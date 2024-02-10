<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019-2023  Frédéric France         <frederic.france@free.fr>
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

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
// paper formats
$sql = "SELECT code, label, width, height, unit";
$sql .= " FROM " . $db->prefix() . "c_paper_format";
$sql .= " WHERE active=1";
$paperformats = [];
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$unitKey = $langs->trans('SizeUnit' . $obj->unit);
		$paperformats[$obj->code] = $langs->trans('PaperFormat' . strtoupper($obj->code)) . ' - ' . round($obj->width) . 'x' . round($obj->height) . ' ' . ($unitKey == 'SizeUnit' . $obj->unit ? $obj->unit : $unitKey);
		$i++;
	}
}

$arrayofparameters = [
	'EASYDOC_PDF_FORMAT' => [
		'css' => 'minwidth500',
		'type' => 'selectarray',
		'array' => $paperformats,
		'default' => dol_getDefaultFormat(),
	],
	'EASYDOC_PDF_MARGIN_LEFT' => [
		'css' => 'minwidth500',
		'type' => 'number',
		'enabled' => 1,
		'default' => 10,
	],
	'EASYDOC_PDF_MARGIN_RIGHT' => [
		'css' => 'minwidth500',
		'type' => 'number',
		'enabled' => 1,
		'default' => 10,
	],
	'EASYDOC_PDF_MARGIN_TOP' => [
		'css' => 'minwidth500',
		'type' => 'number',
		'enabled' => 1,
		'default' => 48,
	],
	'EASYDOC_PDF_MARGIN_BOTTOM' => [
		'css' => 'minwidth500',
		'type' => 'number',
		'enabled' => 1,
		'default' => 25,
	],
	'EASYDOC_PDF_MARGIN_HEADER' => [
		'css' => 'minwidth500',
		'type' => 'number',
		'enabled' => 1,
		'default' => 10,
	],
	'EASYDOC_PDF_MARGIN_FOOTER' => [
		'css' => 'minwidth500',
		'type' => 'number',
		'enabled' => 1,
		'default' => 10,
	],
	// 'OAUTH_EASYDOCGENERATOR_URI' => [
	// 	'css' => 'minwidth500',
	// 	'default' => dol_buildpath('/easydocgenerator/core/modules/oauth/easydocgenerator_oauthcallback.php', 2),
	// ],
	// 'OAUTH_EASYDOCGENERATOR_URI_NOTIF' => [
	// 	'css' => 'minwidth500',
	// 	'default' => dol_buildpath('/easydocgenerator/notifications.php', 2),
	// ],
	// 'EASYDOCGENERATOR_MYPARAM1' => array(
	//     'css' => 'minwidth500',
	//     'type' => 'text',
	//     'enabled' => 1,
	// ),
	// 'EASYDOCGENERATOR_MYPARAM2' => array(
	//     'css' => 'minwidth500',
	//     'type' => 'text',
	//     'enabled' => 1,
	// )
];

// Paramètres ON/OFF
$modules = [
	'EASYDOCGENERATOR_ENABLE_DEVELOPPER_MODE' => 'EasydocgeneratorEnableDevelopperMode',
	// tweak dolibarr
	'CHECKLASTVERSION_EXTERNALMODULE' => 'CHECKLASTVERSION_EXTERNALMODULE',
	'EASYDOC_ADD_TEMPLATE_SUFFIX_TO_FILENAME' => 'EASYDOC_ADD_TEMPLATE_SUFFIX_TO_FILENAME',
	'PRODUIT_PDF_MERGE_PROPAL' => 'PRODUIT_PDF_MERGE_PROPAL',
	'BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL' => 'BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL',
	'BANK_ASK_PAYMENT_BANK_DURING_ORDER' => 'BANK_ASK_PAYMENT_BANK_DURING_ORDER',
	'BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER' => 'BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER',
	'INVOICE_ADD_ZATCA_QR_CODE' => 'INVOICE_ADD_ZATCA_QR_CODE',
	'INVOICE_ADD_SWISS_QR_CODE' => 'INVOICE_ADD_SWISS_QR_CODE',
];
if ((int) DOL_VERSION > 17) {
	// tweak dolibarr
	$modules = array_merge(
		$modules,
		[
			'MAIN_ENABLE_AJAX_TOOLTIP' => 'MAIN_ENABLE_AJAX_TOOLTIP',
		]
	);
}

/*
 * Actions
 */
foreach ($modules as $constant => $desc) {
	if ($action == 'enable_' . strtolower($constant)) {
		dolibarr_set_const($db, $constant, "1", 'chaine', 0, '', $conf->entity);
	}
	if ($action == 'disable_' . strtolower($constant)) {
		dolibarr_del_const($db, $constant, $conf->entity);
		//header("Location: ".$_SERVER["PHP_SELF"]);
		//exit;
	}
	if ($action == 'enable_MAIN_ENABLE_AJAX_TOOLTIP' || $action == 'disable_MAIN_ENABLE_AJAX_TOOLTIP') {
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
	}
}
if ($action == 'update') {
	$error = 0;
	$db->begin();
	foreach ($arrayofparameters as $key => $val) {
		$result = dolibarr_set_const($db, $key, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
		if ($result < 0) {
			$error++;
			break;
		}
	}
	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
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

print dol_get_fiche_head($head, 'settings', $langs->trans('Settings'), -1, 'technic');

if ($action == 'edit') {
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		$type = empty($val['type']) ? 'text' : $val['type'];
		$value = !empty($conf->global->$key) ? $conf->global->$key : (isset($val['default']) ? $val['default'] : '');
		print '<tr class="oddeven">';
		if ($type == 'selectarray') {
			print '<td>' . $langs->trans('EASYDOC_PDF_FORMAT') . '</td>';
			print '<td>' . $form->selectarray('EASYDOC_PDF_FORMAT', $val['array'], $value) . '</td>';
		} else {
			print '<td>';
			$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
			print $form->textwithpicto($langs->trans($key), $tooltiphelp);
			print '</td>';
			print '<td><input name="' . $key . '" type="' . $type . '" class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $value . '"></td>';
		}
		print '</tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="' . $langs->trans("Save") . '">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td class="titlefield">' . $langs->trans("Parameter") . '</td>';
	print '<td>' . $langs->trans("Value") . '</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		print '<tr class="oddeven"><td>';
		$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
		print $form->textwithpicto($langs->trans($key), $tooltiphelp);
		print '</td><td>';
		$value = getDolGlobalString($key, $val['default']);
		if (isset($val['type']) && $val['type'] == 'password') {
			$value = preg_replace('/./i', '*', $value);
		}
		if (isset($val['type']) && $val['type'] == 'selectarray') {
			$value = $val['array'][$value];
		}
		print $value;
		print '</td></tr>';
	}

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
	print '</div>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Paramètres Divers") . '</td>';
	print '<td align="center" width="100">' . $langs->trans("Action") . '</td>';
	print "</tr>\n";
	// Modules
	foreach ($modules as $constant => $desc) {
		print '<tr class="oddeven">';
		print '<td>' . $langs->trans($desc) . '</td>';
		print '<td align="center" width="100">';
		$value = getDolGlobalInt($constant);
		if ($value == 0) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable_' . strtolower($constant) . '&amp;token=' . $_SESSION['newtoken'] . '">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		} elseif ($value == 1) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable_' . strtolower($constant) . '&amp;token=' . $_SESSION['newtoken'] . '">';
			print img_picto($langs->trans("Enabled"), 'switch_on');
			print '</a>';
		}
		print "</td>";
		print '</tr>';
	}
	print '</table>' . PHP_EOL;
	print '<br>' . PHP_EOL;
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
