<?php
/**
 ***********************************************************************************************
 * Configuration file for bar
 *
 *
 * @copyright 2004-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

//************************************************
// hier sind die zu sichernden Dateien und Ordner anzugeben
//
// Bei einem Update von Admidio kann es vorkommen,
// dass Dateien umbenannt, entfernt oder durch andere ersetzt werden.
// Eine Rücksicherung einer alten Version könnte somit Dateileichen erzeugen.
// Um dem vorzubeugen, werden die angegebenen Dateien und Ordner vor
// einer Rücksicherung zuerst gelöscht.

// zu zippende bzw. wiederherzustellende Dateien
$whitelistFile = array( 'index.php');

// zu zippende bzw. wiederherzustellende Ordner
$whitelistDir = array('adm_my_files', 'adm_plugins', 'adm_program', 'adm_themes');

// der Name der Sicherungsdateien ( <$backupFileName>_web.zip und <$backupFileName>_db.sql )
$backupFileName = 'backup';