<?php
/**
 ***********************************************************************************************
 * bar
 * 
 * (b)ackup (a)nd (r)estore
 * 
 * Bar is a program for Admidio, but not a classic plugin. It backs up and restores an Admidio SQL database and its associated web space.
 *
 * Version: 2.0
 *
 * Date: 27.12.2025
 *
 * Compatible with Admidio version 5.0
 * 
 * Bar must be in a subfolder of root (on the same level as e.g. adm_plugins). The folder name can be freely selected.
 * 
 * Usage:
 * 
 * The program is called via the command line with the following parameters:
 * 
 * mode=show or mode=backup or mode=restore
 * source=sql or source=web or source=sql_web or source=admidio
 *
 * When using mode=backup or mode=restore, a source must be specified via source=
 * The source source=admidio can only be used with mode=restore.
 * 
 * Example:
 *
 * ...bar.php?mode=show
 * or
 * ...bar.php?mode=backup&source=sql
 * or
 * ...bar.php?mode=restore&source=admidio
 * 
 * Backup files:
 * 
 * The created backup files are saved in the folder of bar. By default these are backup_web.zip and backup_db.sql
 * 
 * 
 * @inspired by @url http://andreknieriem.de
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
*/

include_once ('config.php');
include_once (__DIR__ . '/../adm_my_files/config.php'); // die Admidio Konfigurationsdatei einlesen
include_once (__DIR__ . '/mysqldump-php-2.10/src/Ifsnop/Mysqldump/Mysqldump.php');

ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');

$getMode = checkVariableIsValid($_GET, 'mode', array(
    'show',
    'backup',
    'restore'
));
$getSource = checkVariableIsValid($_GET, 'source', array(
    'sql',
    'web',
    'sql_web',
    'admidio'
));

echo '<h4>bar</h4>';
echo '(b)ackup (a)nd (r)estore - v2.0';
echo '<br><br>';
echo 'bar backs up and restores an Admidio SQL database and its associated web space';
echo '<br><br>';
echo '*****************************************************************';
echo '<br>';

if (($getMode === 'ERROR' || $getSource === 'ERROR' || $getMode === '' || ($getMode === 'show' && $getSource !== '') || ($getMode === 'restore' && $getSource === '') || ($getMode === 'backup' && ($getSource === '' || $getSource === 'admidio'))) && ! isset($_POST['SelectedBackupFile'])) {
    echo '<h4>Script aborted</h4>';
    echo 'No or incorrect parameters were passed!';
    echo '<br><br>';
    echo 'Possible parameters:';
    echo '<br>';
    echo '- mode=show or mode=backup or mode=restore';
    echo '<br>';
    echo '- source=sql or source=web or source=sql_web or source=admidio';
    echo '<br>';
    echo '(Attention: source=admidio restores a backup created with Admidio. It can only be used in conjunction with mode=restore)';
    echo '<br><br>';
    echo 'Examples:';
    echo '<br>';
    echo '.../bar.php?mode=show';
    echo '<br>';
    echo '.../bar.php?mode=backup&source=sql';
    echo '<br>';
    echo '.../bar.php?mode=backup&source=sql_web';
    echo '<br>';
    echo '.../bar.php?mode=restore&source=admidio';
    die();
}

// only needed for restore Admidio SQL-dump
// create a list with all valid files in the spezified folder
$existingBackupFiles = array();
$permittedExtensions = array(
    'sql',
    'gz',
    'gzip',
    'bz2'
);
$dirHandle = @opendir($admidioDumpFileDir);
if ($dirHandle) {
    while (($entry = readdir($dirHandle)) !== false) {
        if ($entry === '.' || $entry === '..' || ! in_array(substr($entry, strrpos($entry, '.') + 1), $permittedExtensions)) {
            continue;
        }
        $existingBackupFiles[$entry] = $entry;
    }
    closedir($dirHandle);
}
if ($getSource === 'admidio') {
    if (sizeof($existingBackupFiles) > 0) {
        echo '<h4>Select the backup file to restore:</h4>';
        echo '<form action="bar.php" method="post">
                <select name="SelectedBackupFile">';

        foreach ($existingBackupFiles as $value => $description) {
            echo '<option value="' . $value . ' ">' . $description . '</option>';
        }
        echo '  </select>
                <br>
                <br>
                <button type="submit">Restore</button>
            </form>';
    } else {
        echo '<br>';
        echo 'There are no Admidio database dumps in the directory ' . $admidioDumpFileDir . ' or the directory does not exist.';
        echo '<br>';
        echo '<h4>Script aborted</h4>';
    }
}

// read the plugin directory name
$folders = explode(DIRECTORY_SEPARATOR, __DIR__);
$pluginFolder = $folders[sizeof($folders) - 1];
unset($folders);

// construct the name of the ZIP file
$zipFile = $backupFileName . '_web.zip';

$zipfileExists = false;
if (file_exists($zipFile)) {
    $zipfileExists = true;
}

// construct the name of the SQL dump file
$dumpFile = $backupFileName . '_db.sql.gzip';

// if an Admidio dump file was selected, then overwrite the name
if (isset($_POST['SelectedBackupFile'])) {
    $dumpFile = $admidioDumpFileDir . trim($_POST['SelectedBackupFile']);
}

$sqlFileExists = false;
if (file_exists($dumpFile)) {
    $sqlFileExists = true;
}

if ($getMode === 'show') {
    echo '<h4>bar backup files:</h4>';

    if ($zipfileExists) {
        echo 'File ' . $zipFile . ' (' . date('d.m.Y - H:m', filemtime($zipFile)) . ') exists';
    } else {
        echo 'File ' . $zipFile . '  does not exist';
    }
    echo '<br>';

    if ($sqlFileExists) {
        echo 'File ' . $dumpFile . ' (' . date('d.m.Y - H:m', filemtime($dumpFile)) . ') exists';
    } else {
        echo 'File ' . $dumpFile . ' does not exist';
    }
    echo '<br>';

    echo '<h4>admidio database dumps:</h4>';
    if (sizeof($existingBackupFiles) > 0) {
        foreach ($existingBackupFiles as $value => $description) {
            echo $description;
            echo '<br>';
        }
    } else {
        echo 'There are no Admidio database dumps in the directory ' . $admidioDumpFileDir . ' or the directory does not exist.';
    }
}

// Backup or Restore Webspace
if ($getSource === 'web' || $getSource === 'sql_web') {
    // nach root wechseln (ZIP-Datei kann dadurch mit relativen Pfaden erzeugt werden)
    chdir('..' . DIRECTORY_SEPARATOR);

    if ($getMode === 'backup') {
        // diese Zeilen sind aus dem Netz

        // file und dir counter
        $fc = 0;
        $dc = 0;

        // Objekt erstellen und schauen, ob der Server zippen kann
        $zip = new ZipArchive();
        if ($zip->open('.' . DIRECTORY_SEPARATOR . $pluginFolder . DIRECTORY_SEPARATOR . $zipFile, (ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) !== TRUE) {
            die('The archive could not be created.');
        }

        echo "<pre>";

        foreach ($whitelistFile as $file) {
            $zip->addFile($file);
        }

        foreach ($whitelistDir as $folder) {

            $folder = $folder . DIRECTORY_SEPARATOR;

            // Gehe durch die Ordner und füge alles dem Archiv hinzu
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));

            foreach ($iterator as $key => $value) {
                // wenn es kein ordner sondern eine datei ist
                if (! is_dir($key)) {
                    // echo $key . " _ _ _ _Datei wurde übernommen</br>";
                    $zip->addFile(realpath($key), $key) or die('ERROR: Cannot attach file: ' . $key);
                    $fc ++;
                } elseif (count(scandir($key)) <= 2) {
                    // der ordner ist bis auf . und .. leer
                    // echo $key . " _ _ _ _Leerer Ordner wurde übernommen</br>";
                    $zip->addEmptyDir(substr($key, - 1 * strlen($key), strlen($key) - 1));
                    $dc ++;
                } elseif ((substr($key, - 2) === '/.') || (substr($key, - 2) === '\.')) {
                    // ordner .
                    $dc ++; // nur für den bericht am ende
                } elseif ((substr($key, - 3) === '/..') || (substr($key, - 3) === '\..')) {
                    // ordner ..
                    // tue nichts
                } else {
                    // zeige andere ausgelassene Ordner (sollte eigentlich nicht vorkommen)
                    echo $key . 'WARNING: The folder was not included in the archive.</br>';
                }
            }
        }

        echo "</pre>";

        // speichert die Zip-Datei
        $zip->close();

        // bericht
        echo 'The ZIP archive was created successfully (' . $dc . ' folders, ' . $fc . ' files).';
        echo '<br>';
    } else {
        // Webspace: restore

        if ($zipfileExists) {
            $zip = new ZipArchive();
            $res = $zip->open('.' . DIRECTORY_SEPARATOR . $pluginFolder . DIRECTORY_SEPARATOR . $zipFile);
            if ($res === TRUE) {

                foreach ($whitelistFile as $file) {
                    unlink($file);
                }

                foreach ($whitelistDir as $folder) {
                    rrmdir($folder);
                }
                // Extract file
                $zip->extractTo('./');
                $zip->close();

                echo 'The ZIP archive was restored successfully.';
                echo '<br>';
            } else {
                echo 'Error opening ZIP archive.';
                echo '<br>';
            }
        } else {
            echo 'Error: ZIP archive does not exist.';
            echo '<br>';
        }
    }
    chdir($pluginFolder . DIRECTORY_SEPARATOR);
}

// Backup oder Restore SQL-Datenbank
if ($getSource === 'sql' || $getSource === 'sql_web' || isset($_POST['SelectedBackupFile'])) {
    if ($getMode === 'backup') {

        $dumpSettings = array(
            'compress' => Ifsnop\Mysqldump\Mysqldump::GZIP
        );
        $dump = new Ifsnop\Mysqldump\Mysqldump("mysql:host=$g_adm_srv;dbname=$g_adm_db", $g_adm_usr, $g_adm_pw, $dumpSettings);

        $dump->start($dumpFile);

        echo 'The SQL dump file was created successfully.';
    } else {
        // SQL: restore
        if ($sqlFileExists) {
            error_reporting(E_ALL);

            // Connect to MySQL server
            $mysqli = new mysqli($g_adm_srv, $g_adm_usr, $g_adm_pw, $g_adm_db);

            if ($mysqli->connect_error) {
                die('Error connecting to MySQL server: (' . $mysqli->connect_errno . ')' . $mysqli->connect_error);
            }

            $sql = 'SELECT table_name
                      FROM information_schema.tables
                     WHERE table_schema = \'' . $g_adm_db . '\'
                       AND table_name LIKE \'' . $g_tbl_praefix . '_%\' ';
            $result = $mysqli->query($sql);

            $tables = array();
            $tables_string = '';

            while ($cRow = $result->fetch_array()) {
                $tables[] = $cRow[0];
                $tables_string .= $cRow[0] . ', ';
            }

            $tables_string = substr($tables_string, 0, strlen($tables_string) - 2);

            $sql = 'SET FOREIGN_KEY_CHECKS=0';
            $mysqli->query($sql);

            $sql = 'DROP TABLE ' . $tables_string;
            $mysqli->query($sql);

            $sql = 'SET FOREIGN_KEY_CHECKS=1';
            $mysqli->query($sql);

            $data = array();

            $fileextension = strrchr($dumpFile, '.');
            if ($fileextension == '.bz2') // OUTPUT_COMPRESSION_TYPE = 'bzip2'
            {
                $fp = bzopen($dumpFile, 'r');
                while ($data[] = fgets($fp));
            } elseif ($fileextension == '.gz' || $fileextension == '.gzip') // OUTPUT_COMPRESSION_TYPE = 'gzip'
            {
                // die Verwendung von $data = gzfile($backupAbsolutePath.$sqlFile);
                // führt zu einem Fehler beim anschließenden Ausführen von $mysqli->query
                // irgendwie können die einzelnen Segmente der dekomprimierten Datei
                // über den Befehl $templine .= $line; nicht richtig zusammengesetzt werden
                // über den Umweg einer temporären Datei funktioniert es
                uncompress($dumpFile, 'backup_tmp_sql.txt');
                $data = file('backup_tmp_sql.txt');
                unlink('backup_tmp_sql.txt');
            } else // OUTPUT_COMPRESSION_TYPE = none = pure sql
            {
                $data = file($dumpFile);
            }

            // Temporary variable, used to store current query
            $templine = '';
            $error = '';

            // Loop through each line
            foreach ($data as $line) {
                // Skip it if it's a comment
                if (substr($line, 0, 2) == '--' || $line == '') {
                    continue;
                }

                // Add this line to the current segment
                $templine .= $line;

                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), - 1, 1) == ';') {
                    // Perform the query
                    if (! $mysqli->query($templine)) {
                        $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $mysqli->error . PHP_EOL;
                    }

                    // Reset temp variable to empty
                    $templine = '';
                }
            }

            if ($error == '') {
                echo 'The SQL dump file was imported successfully.';
            } else {
                echo '<br />ERROR<br /><br />' . $error;
                exit();
            }
        } else {
            echo 'Error: SQL file does not exist.';
        }
    }
}

// aus dem Internet
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = array_diff(scandir($dir), [
            '.',
            '..'
        ]);
        foreach ($objects as $object) {
            if (is_dir($dir . "/" . $object)) {
                rrmdir($dir . "/" . $object);
            } else {
                unlink($dir . "/" . $object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

// prüft die übergebenen Variablen
function checkVariableIsValid(array $array, $variableName, array $validValues)
{
    $value = '';

    if (array_key_exists($variableName, $array)) {
        if (in_array($array[$variableName], $validValues)) {
            $value = $array[$variableName];
        } else {
            $value = 'ERROR';
        }
    }
    return $value;
}

// aus dem Internet
/*
 * function compress( $srcFileName, $dstFileName )
 * {
 * // getting file content
 * $fp = fopen( $srcFileName, "r" );
 * $data = fread ( $fp, filesize( $srcFileName ) );
 * fclose( $fp );
 *
 * // writing compressed file
 * $zp = gzopen( $dstFileName, "w9" );
 * gzwrite( $zp, $data , strlen($data));
 * gzclose( $zp );
 * }
 */

// aus dem Internet
function uncompress($srcFileName, $dstFileName)
{
    $sfp = gzopen($srcFileName, "rb");
    $fp = fopen($dstFileName, "w");

    while ($string = gzread($sfp, 4096)) {
        fwrite($fp, $string, strlen($string));
    }
    gzclose($sfp);
    fclose($fp);
}
