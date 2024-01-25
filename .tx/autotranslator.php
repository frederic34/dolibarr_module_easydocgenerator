#!/usr/bin/env php
<?php
/* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       dev/translation/autotranslator.php
 *		\ingroup    dev
 * 		\brief      This script uses google language ajax api as the translator engine
 *                  The main translator function can be found at:
 *                  defunct -http://code.google.com/intl/fr/apis/language/translate/overview.html-
 *                  defunct -http://translate.google.com/translate_tools-
 *                  https://code.google.com/apis/console
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit;
}

require_once "./.tx/class/translate.class.php";
$langs = new Translate($path);
$langs->setDefaultLang('fr_FR');
// Load main language strings
$langs->load("main");

// Global variables
$version = '1.14';
$error = 0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0);
print "***** " . $script_file . " (" . $version . ") *****\n";
$dir = "./langs";

// Check parameters
if (!isset($argv[3])) {
	print "Usage:   " . $script_file . "  lang_code_src lang_code_dest|all APIKEY [langfile.lang]\n";
	print "Example: " . $script_file . "  en_US pt_PT 123456 abcdef.lang\n";
	print "Rem:     lang_code to use can be found on https://translate.google.com\n";
	exit;
}

// Show parameters
print 'Argument 1=' . $argv[1] . "\n";
print 'Argument 2=' . $argv[2] . "\n";
print 'Argument 3=' . $argv[3] . "\n";
$file = '';
if (isset($argv[4])) {
	$file = $argv[4];
	print 'Argument 4=' . $argv[4] . "\n";
}
print 'Files will be generated/updated in directory ' . $dir . "\n";

if ($argv[2] != 'all') {
	if (!is_dir($dir . '/' . $argv[2])) {
		print 'Create directory ' . $dir . '/' . $argv[2] . "\n";
		$result = mkdir($dir . '/' . $argv[2]);
		if (!$result) {
			// $db->close();
			return -1;
		}
	}
}

require_once "./.tx/class/autotranslator.class.php";

$langParser = new AutoTranslator($argv[2], $argv[1], $dir, $file, $argv[3]);

print "***** Finished *****\n";

// -------------------- END OF YOUR CODE --------------------

return $error;
