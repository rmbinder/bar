<?php
/**
 ***********************************************************************************************
 * bar
 * 
 * (b)ackup (a)nd (r)estore
 * 
 * Bar is a program for Admidio, but not a classic plugin. It backs up and restores an Admidio SQL database and its associated web space.
 *
 * Version: 1.0
 *
 * Date: 26.04.2023
 *
 * Compatible with Admidio version 4.1
 * 
 * Bar must be in a subfolder of root (on the same level as e.g. adm_plugins). The folder name can be freely selected.
 * 
 * Usage:
 * 
 * The program is called via the command line with the following parameters:
 * 
 * mode=backup  or mode=restore
 * source=sql or source=web or source=all
 * show=list (optional)
 * 
 * Example:
 *
 * ...bar.php?mode=restore&source=sql
 * or
 * ...bar.php?mode=backup&source=all
 * 
 * Backup files:
 * 
 * The created backup files are saved in the folder of bar. By default these are backup_web.zip and backup_db.sql
 * 
 * 
 * @inspired by @url http://andreknieriem.de
 *
 * @copyright 2004-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
*/

include_once('config.php');

$getMode   = checkVariableIsValid($_GET, 'mode',   array('backup', 'restore'));
$getSource = checkVariableIsValid($_GET, 'source', array('sql', 'web', 'all'));
$getShow   = checkVariableIsValid($_GET, 'show',   array('list'));

if ($getMode === 'ERROR' || $getSource === 'ERROR' || $getShow === 'ERROR'
    || ($getMode === '' && $getSource === '' && $getShow === '')
    || ($getMode === '' XOR $getSource === ''))
{
    echo '<h4>Script aborted</h4>';
    echo 'No or incorrect parameters were passed!';
    echo '<br><br>';
    echo 'Required parameters:';
    echo '<br>';
    echo '- mode=backup or mode=restore';
    echo '<br>';
    echo '- source=sql or source=web or source=all';
    echo '<br>';
    echo '- show=list (optional)';
    echo '<br><br>';
    echo 'Examples:';
    echo '<br>';
    echo 'e.g.: .../bar.php?mode=backup&source=sql';
    echo '<br>';
    echo 'or';
    echo '<br>';
    echo 'e.g.: .../bar.php?mode=restore&source=web';
    die;
}

//den Plugin-Verzeichnisnamen auslesen
$folders = explode(DIRECTORY_SEPARATOR, __DIR__);
$pluginFolder = $folders[sizeof($folders)-1];
unset($folders);

// den Namen der ZIP-Datei zusammensetzen (Name des Plugin-Folders notwendig, da von root gestartet wird)
$zipFile = $backupFileName. '_web.zip';

$zipfileExists = false;
if (file_exists($zipFile))
{
    $zipfileExists = true;
}

// den Namen der SQL-Datei zusammensetzen
$dumpfile = $backupFileName .'_db.sql';

$dumpFileExists = false;
if (file_exists($dumpfile))
{
    $dumpFileExists = true;
}

if ($getShow === 'list')
{
    if ($zipfileExists)
    {
        echo 'File ' .$zipFile. ' ('.date('d.m.Y - H:m', filemtime($zipFile)) .') exists';
    }
    else
    {
        echo 'File ' .$zipFile  . '  does not exist';
    }
    echo '<br>';
    if ($dumpFileExists)
    {
        echo 'File ' .$dumpfile. ' ('.date('d.m.Y - H:m', filemtime($dumpfile)) .') exists';
    }
    else
    {
        echo 'File ' .$dumpfile . ' does not exist';
    }
    echo '<br>';
}

ini_set('max_execution_time', 600);
ini_set('memory_limit','1024M');

if($getSource === 'web' || $getSource === 'all')               // Source webspace or, if all is selected (webspace and sql)
{
    // nach root wechseln (ZIP-Datei kann dadurch mit relativen Pfaden erzeugt werden)
    chdir('..'. DIRECTORY_SEPARATOR);
    
    if($getMode === 'backup')           // Webspace: backup
    {
        // diese Zeilen sind aus dem Netz
        
        // file und dir counter
        $fc = 0;
        $dc = 0;
        
        // Objekt erstellen und schauen, ob der Server zippen kann
        $zip = new ZipArchive();
        if ($zip->open('.'. DIRECTORY_SEPARATOR . $pluginFolder . DIRECTORY_SEPARATOR .$zipFile, (ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE )) !== TRUE) {
            die ('The archive could not be created.');
        }

        echo "<pre>";
        
        foreach ($whitelistFile as $file)
        {
            $zip->addFile($file);
        }
        
        foreach ($whitelistDir as $folder)
        {
            $folder = $folder . DIRECTORY_SEPARATOR;
            
            // Gehe durch die Ordner und f�ge alles dem Archiv hinzu
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
            
            foreach ($iterator as $key=>$value)
            {
                if(!is_dir($key))
                { // wenn es kein ordner sondern eine datei ist
                    // echo $key . " _ _ _ _Datei wurde �bernommen</br>";
                    $zip->addFile(realpath($key), $key) or die ('ERROR: Cannot attach file: '. $key);
                    $fc++;
                    
                }
                elseif (count(scandir($key)) <= 2)
                { // der ordner ist bis auf . und .. leer
                    // echo $key . " _ _ _ _Leerer Ordner wurde �bernommen</br>";
                    $zip->addEmptyDir(substr($key, -1*strlen($key),strlen($key)-1));
                    $dc++;
                    
                }
                elseif ((substr($key, -2) === '/.') || (substr($key, -2) === '\.'))
                { // ordner .
                    $dc++; // nur f�r den bericht am ende
                    
                }
                elseif ((substr($key, -3) === '/..') || (substr($key, -3) === '\..'))
                { // ordner ..
                    // tue nichts
                    
                }
                else
                { // zeige andere ausgelassene Ordner (sollte eigentlich nicht vorkommen)
                    echo $key . 'WARNING: The folder was not included in the archive.</br>';
                }
            }
        }
        
        echo "</pre>";
        
        // speichert die Zip-Datei
        $zip->close();
        
        // bericht
        echo 'The ZIP archive was created successfully ('. $dc .' folders, '.$fc . ' files).';
        echo '<br>';
    }
    else                                // Webspace: restore
    {
        if ($zipfileExists)
        {
            $zip = new ZipArchive();
            $res = $zip->open('.'. DIRECTORY_SEPARATOR . $pluginFolder . DIRECTORY_SEPARATOR .$zipFile);
            if ($res === TRUE)
            {
                
                foreach ($whitelistFile as $file)
                {
                    unlink($file);
                }
                
                foreach ($whitelistDir as $folder)
                {
                    rrmdir($folder);
                }
                // Extract file
                $zip->extractTo('./');
                $zip->close();
                
                echo 'The ZIP archive was restored successfully.';
                echo '<br>';
                
            }
            else
            {
                echo 'Error opening ZIP archive.';
                echo '<br>';
            }
        }
        else 
        {
            echo 'Error: ZIP archive does not exist.';
            echo '<br>';
        }
        
    }
    chdir($pluginFolder.DIRECTORY_SEPARATOR);
}

if($getSource === 'sql' || $getSource === 'all')                                   // // Source sql or, if all is selected (webspace and sql)
{

    include_once(__DIR__ . '/../adm_my_files/config.php');              //die Admidio Konfigurationsdatei einlesen
    include_once(__DIR__ . '/mysqldump-php-2.10/src/Ifsnop/Mysqldump/Mysqldump.php');
    
    if($getMode === 'backup')           // SQL: backup
    {
        $dump = new Ifsnop\Mysqldump\Mysqldump("mysql:host=$g_adm_srv;dbname=$g_adm_db", $g_adm_usr, $g_adm_pw);
        $dump->start($dumpfile);
        
        echo 'The SQL file was created successfully.';
    }
    else                                // SQL: restore
    {
        if ($dumpFileExists)
        {
            error_reporting(E_ALL);
            
            // Connect to MySQL server
            $mysqli = new mysqli($g_adm_srv, $g_adm_usr, $g_adm_pw, $g_adm_db);
            
            if ($mysqli->connect_error)
            {
                die('Error connecting to MySQL server: (' . $mysqli->connect_errno . ')'
                    . $mysqli->connect_error);
            }
            
            $sql = 'SELECT table_name
                      FROM information_schema.tables
                     WHERE table_schema = \''.$g_adm_db.'\'
                       AND table_name LIKE \''.$g_tbl_praefix.'_%\' ';
            $result = $mysqli->query($sql);
            
            $tables = array();
            $tables_string = '';
            
            while($cRow = $result->fetch_array())
            {
                $tables[] = $cRow[0];
                $tables_string .= $cRow[0].', ';
            }
            
            $tables_string = substr($tables_string, 0, strlen($tables_string)-2);
            
            $sql = 'SET FOREIGN_KEY_CHECKS=0' ;
            $mysqli->query($sql);
            
            $sql = 'DROP TABLE '.$tables_string ;
            $mysqli->query($sql);
            
            $sql = 'SET FOREIGN_KEY_CHECKS=1' ;
            $mysqli->query($sql);
            
            // Temporary variable, used to store current query
            $templine = '';
            $handle = fopen($dumpfile , 'r');
            if ($handle) {
                while (!feof($handle)) { // Loop through each line
                    $line = trim(fgets($handle));
                    // Skip it if it's a comment
                    if (substr($line, 0, 2) == '--' || $line == '') {
                        continue;
                    }
                    
                    // Add this line to the current segment
                    $templine .= $line;
                    
                    // If it has a semicolon at the end, it's the end of the query
                    if (substr(trim($line), -1, 1) == ';') {
                        // Perform the query
                        $mysqli->query($templine) or print('Error performing query "' . $templine . '":' . $mysqli->error . PHP_EOL);
                        // Reset temp variable to empty
                        $templine = '';
                    }
                }
                fclose($handle);
                echo 'The SQL file was imported successfully.';
            }
            else
            {
                echo 'Error opening SQL file.';
            }
            $mysqli->close();
        }
        else
        {
            echo 'Error: SQL file does not exist.';
        }
    }
}

//aus dem Internet
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = array_diff(scandir($dir), ['.', '..']);
        foreach ($objects as $object) {
            if (is_dir($dir . "/" . $object)) {
                rrmdir($dir . "/" . $object);
            }
            else {
                unlink($dir . "/" . $object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

//prüft die übergebenen Variablen
function checkVariableIsValid(array $array, $variableName, array $validValues)
{
    $value = '';
    
    if (array_key_exists($variableName, $array))
    {
        if (in_array($array[$variableName], $validValues))
        {
            $value = $array[$variableName];
        }
        else
        {
            $value = 'ERROR';
        }
    }
    return $value;
}
