<?php
// Debug-Script für Plesk-Server-Fehler
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>SpectraHost Plesk Debug</h1>";

// PHP Version und Module prüfen
echo "<h2>PHP-Umgebung</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Verfügbare PHP-Module
echo "<h3>Kritische PHP-Module</h3>";
$required_modules = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring', 'openssl'];
foreach ($required_modules as $module) {
    $status = extension_loaded($module) ? 'VERFÜGBAR' : 'FEHLT';
    $color = extension_loaded($module) ? 'green' : 'red';
    echo "<span style='color:$color'>$module: $status</span><br>";
}

// Dateirechte prüfen
echo "<h3>Dateirechte</h3>";
$files_to_check = [
    '.' => 'Hauptverzeichnis',
    'includes' => 'Includes-Verzeichnis',
    'includes/config.php' => 'Konfigurationsdatei',
    'includes/database.php' => 'Datenbankdatei',
    'index.php' => 'Hauptdatei'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file) ? 'JA' : 'NEIN';
        echo "$description ($file): Rechte $perms, Lesbar: $readable<br>";
    } else {
        echo "<span style='color:red'>$description ($file): NICHT GEFUNDEN</span><br>";
    }
}

// Datenbankverbindung testen
echo "<h3>Datenbankverbindung</h3>";
try {
    // Direkte PDO-Verbindung testen
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $username = "s9281_spectrahost";
    $password = getenv('MYSQL_PASSWORD') ?: '';
    
    echo "Verbindungsstring: $dsn<br>";
    echo "Benutzername: $username<br>";
    echo "Passwort gesetzt: " . ($password ? 'JA' : 'NEIN') . "<br>";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    echo "<span style='color:green'>Datenbankverbindung erfolgreich!</span><br>";
    
    // Tabellen prüfen
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Verfügbare Tabellen: " . implode(", ", $tables) . "<br>";
    
} catch (Exception $e) {
    echo "<span style='color:red'>Datenbankfehler: " . $e->getMessage() . "</span><br>";
}

// Includes testen
echo "<h3>Include-Dateien</h3>";
$includes = [
    'includes/config.php',
    'includes/database.php',
    'includes/plesk-config.php'
];

foreach ($includes as $include) {
    try {
        if (file_exists($include)) {
            ob_start();
            include_once $include;
            ob_end_clean();
            echo "<span style='color:green'>$include: OK</span><br>";
        } else {
            echo "<span style='color:red'>$include: NICHT GEFUNDEN</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>$include: FEHLER - " . $e->getMessage() . "</span><br>";
    }
}

// .htaccess prüfen
echo "<h3>.htaccess-Konfiguration</h3>";
if (file_exists('.htaccess')) {
    echo "Datei gefunden, Größe: " . filesize('.htaccess') . " Bytes<br>";
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $rewrite = in_array('mod_rewrite', $modules) ? 'AKTIV' : 'INAKTIV';
        echo "mod_rewrite: $rewrite<br>";
    } else {
        echo "Apache-Module können nicht geprüft werden (normale Plesk-Umgebung)<br>";
    }
} else {
    echo "<span style='color:red'>.htaccess nicht gefunden</span><br>";
}

// Memory und PHP-Limits
echo "<h3>PHP-Limits</h3>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";

// Umgebungsvariablen
echo "<h3>Umgebungsvariablen</h3>";
$env_vars = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    $status = $value ? 'GESETZT' : 'NICHT GESETZT';
    echo "$var: $status<br>";
}

// Test der index.php
echo "<h3>Index.php Test</h3>";
try {
    ob_start();
    $_GET['debug'] = '1';
    include 'index.php';
    $content = ob_get_contents();
    ob_end_clean();
    
    if (strlen($content) > 0) {
        echo "<span style='color:green'>Index.php lädt erfolgreich</span><br>";
    } else {
        echo "<span style='color:orange'>Index.php lädt, aber ohne Ausgabe</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Index.php Fehler: " . $e->getMessage() . "</span><br>";
}

echo "<br><hr><br>";
echo "<h2>Empfohlene Lösungsschritte</h2>";
echo "<ol>";
echo "<li>Stellen Sie sicher, dass alle PHP-Module verfügbar sind</li>";
echo "<li>Konfigurieren Sie die Umgebungsvariablen in Plesk</li>";
echo "<li>Prüfen Sie die Dateirechte (755 für Verzeichnisse, 644 für Dateien)</li>";
echo "<li>Aktivieren Sie mod_rewrite falls verfügbar</li>";
echo "<li>Prüfen Sie die PHP-Fehlerprotokolle in Plesk</li>";
echo "</ol>";

echo "<p><a href='/' style='background:#007cba;color:white;padding:10px;text-decoration:none;'>Zurück zur Hauptseite</a></p>";
?>