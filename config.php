<?php
/**
 ***********************************************************************************************
 * Configuration file for bar
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

// der Name der Sicherungsdateien ( <$backupFileName>_web.zip und <$backupFileName>_db.sql )
$backupFileName = 'backup';

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
$whitelistDir = array('adm_my_files', 'adm_plugins', 'install', 'languages', 'libs', 'modules', 'rss', 'src', 'system', 'vendor', 'themes');

// ab Admidio 5 werden durch Admidio erstellte Backup-Dateien nicht mehr auf dem Server im Ordner adm_my_files/backup gespeichert,
// sondern sofort heruntergeladen und lokal auf dem PC gespeichert 
// um diese Backup-Dateien wiederherzustellen, müssen sie sich in nachfolgendem Ordner befinden:
$admidioDumpFileDir = 'C:/ADMIDIO-BACKUPS/';


