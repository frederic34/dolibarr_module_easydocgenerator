<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2024 		Frédéric FRANCE
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
 *	\file       easydocgenerator/easybuilderindex.php
 *	\ingroup    easybuilder
 *	\brief      Home page of easybuilder top menu
 */

// Load Dolibarr environment
require 'config.php';


// Load translation files required by the page
$langs->loadLangs(["easybuilder@easybuilder"]);

$action = GETPOST('action', 'aZ09');

$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('easybuilder')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('easybuilder', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'easybuilder', 0, 'easybuilder_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("EasyBuilderArea"));

print load_fiche_titre($langs->trans("EasyBuilderArea"), '', 'easybuilder.png@easybuilder');

print '<div class="fichecenter"><div class="fichethirdleft">';


// Draft MyObject
// if (isModEnabled('easybuilder') && $user->rights->easybuilder->read) {
// 	$langs->load("orders");

// 	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
// 	$sql .= ", s.code_client";
// 	$sql .= " FROM " . MAIN_DB_PREFIX . "commande as c";
// 	$sql .= ", " . MAIN_DB_PREFIX . "societe as s";
// 	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
// 	$sql .= " WHERE c.fk_soc = s.rowid";
// 	$sql .= " AND c.fk_statut = 0";
// 	$sql .= " AND c.entity IN (" . getEntity('commande') . ")";
// 	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . ((int) $user->id);
// 	if ($socid)	$sql .= " AND c.fk_soc = " . ((int) $socid);

// 	$resql = $db->query($sql);
// 	if ($resql) {
// 		$total = 0;
// 		$num = $db->num_rows($resql);

// 		print '<table class="noborder centpercent">';
// 		print '<tr class="liste_titre">';
// 		print '<th colspan="3">' . $langs->trans("DraftMyObjects") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

// 		$var = true;
// 		if ($num > 0) {
// 			$i = 0;
// 			while ($i < $num) {

// 				$obj = $db->fetch_object($resql);
// 				print '<tr class="oddeven"><td class="nowrap">';

// 				$myobjectstatic->id = $obj->rowid;
// 				$myobjectstatic->ref = $obj->ref;
// 				$myobjectstatic->ref_client = $obj->ref_client;
// 				$myobjectstatic->total_ht = $obj->total_ht;
// 				$myobjectstatic->total_tva = $obj->total_tva;
// 				$myobjectstatic->total_ttc = $obj->total_ttc;

// 				print $myobjectstatic->getNomUrl(1);
// 				print '</td>';
// 				print '<td class="nowrap">';
// 				print '</td>';
// 				print '<td class="right" class="nowrap">' . price($obj->total_ttc) . '</td></tr>';
// 				$i++;
// 				$total += $obj->total_ttc;
// 			}
// 			if ($total > 0) {

// 				print '<tr class="liste_total"><td>' . $langs->trans("Total") . '</td><td colspan="2" class="right">' . price($total) . "</td></tr>";
// 			}
// 		} else {

// 			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
// 		}
// 		print "</table><br>";

// 		$db->free($resql);
// 	} else {
// 		dol_print_error($db);
// 	}
// }


print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('easybuilder') && $user->rights->easybuilder->read)
{
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".MAIN_DB_PREFIX."easybuilder_myobject as s";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.entity IN (".getEntity($myobjectstatic->element).")";
	//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	//if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/

print '</div></div>';

// End of page
llxFooter();
$db->close();
