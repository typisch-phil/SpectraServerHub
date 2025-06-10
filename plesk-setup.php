<?php
// Plesk-Setup und Konfigurationsprüfung
header('Content-Type: text/html; charset=utf-8');

// Fehlerberichterstattung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>SpectraHost Plesk Setup</title>";
echo "<style>body{font-family:Arial;margin:20px;}h1{color:#333;}.success{color:green;}.error{color:red;}.warning{color:orange;}.info{background:#f0f0f0;padding:10px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>SpectraHost Plesk Setup & Diagnose</h1>";

// Schritt 1: PHP-Konfiguration prüfen
echo "<h2>1. PHP-Konfiguration</h2>";
echo "<div class='info'>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "</div>";

// Erforderliche Erweiterungen prüfen
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'session', 'mbstring'];
echo "<h3>Erforderliche PHP-Erweiterungen:</h3>";
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<span class='" . ($loaded ? 'success' : 'error') . "'>";
    echo $ext . ": " . ($loaded ? "✓ Geladen" : "✗ Fehlt") . "</span><br>";
}

// Schritt 2: Dateiberechtigungen prüfen
echo "<h2>2. Dateiberechtigungen</h2>";
$critical_files = [
    'index.php',
    'includes/config.php',
    'includes/database.php',
    '.htaccess'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file);
        echo "<span class='" . ($readable ? 'success' : 'error') . "'>";
        echo "$file: $perms (" . ($readable ? "lesbar" : "nicht lesbar") . ")</span><br>";
    } else {
        echo "<span class='error'>$file: Datei nicht gefunden</span><br>";
    }
}

// Schritt 3: Verzeichnisse erstellen und prüfen
echo "<h2>3. Verzeichnisse</h2>";
$directories = [
    'uploads' => 0755,
    'cache' => 0755,
    'logs' => 0755,
    'tmp' => 0755
];

foreach ($directories as $dir => $perm) {
    if (!is_dir($dir)) {
        $created = mkdir($dir, $perm, true);
        echo "<span class='" . ($created ? 'success' : 'error') . "'>";
        echo "$dir: " . ($created ? "Erstellt" : "Fehler beim Erstellen") . "</span><br>";
    } else {
        $writable = is_writable($dir);
        echo "<span class='" . ($writable ? 'success' : 'warning') . "'>";
        echo "$dir: Existiert (" . ($writable ? "beschreibbar" : "nicht beschreibbar") . ")</span><br>";
    }
}

// Schritt 4: Datenbankverbindung über zentrale Database-Klasse
echo "<h2>4. Datenbankverbindung</h2>";

$connection_success = false;
try {
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Test the connection
    $connection->query("SELECT 1");
    echo "<span class='success'>✓ Datenbankverbindung erfolgreich über zentrale Database-Klasse</span><br>";
    
    // Tabellen prüfen
    $stmt = $connection->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabellen (" . count($tables) . "): " . implode(", ", array_slice($tables, 0, 5));
    if (count($tables) > 5) echo " ...";
    echo "<br><br>";
    
    $connection_success = true;
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Datenbankverbindung fehlgeschlagen: " . $e->getMessage() . "</span><br>";
}

if (!$connection_success) {
    echo "<div class='error'><h3>Keine Datenbankverbindung möglich!</h3>";
    echo "Bitte konfigurieren Sie die Datenbankdaten in einer der folgenden Dateien:<br>";
    echo "- .env Datei mit MYSQL_* Variablen<br>";
    echo "- includes/config.php mit DB_* Konstanten<br>";
    echo "- plesk-config.php mit PLESK_DB_* Konstanten</div>";
}

// Schritt 5: .htaccess testen
echo "<h2>5. .htaccess und URL Rewriting</h2>";
if (file_exists('.htaccess')) {
    echo "<span class='success'>✓ .htaccess existiert</span><br>";
    
    // Apache mod_rewrite prüfen
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $rewrite_enabled = in_array('mod_rewrite', $modules);
        echo "<span class='" . ($rewrite_enabled ? 'success' : 'error') . "'>";
        echo "mod_rewrite: " . ($rewrite_enabled ? "✓ Aktiviert" : "✗ Nicht verfügbar") . "</span><br>";
    } else {
        echo "<span class='warning'>⚠ apache_get_modules() nicht verfügbar</span><br>";
    }
} else {
    echo "<span class='error'>✗ .htaccess nicht gefunden</span><br>";
}

// Schritt 6: Session-Test
echo "<h2>6. Session-Funktionalität</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'plesk_test';
echo "<span class='success'>✓ Session gestartet: " . session_id() . "</span><br>";

// Schritt 7: Testanfragen
echo "<h2>7. API-Tests</h2>";
$test_urls = [
    '/api/services.php',
    '/api/login.php'
];

foreach ($test_urls as $url) {
    $full_url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($full_url, false, $context);
    $response_code = 200;
    
    // Check response headers from the context
    $response_headers = get_headers($full_url, 1);
    if ($response_headers && is_array($response_headers)) {
        $status_line = $response_headers[0];
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $status_line, $matches)) {
            $response_code = intval($matches[1]);
        }
    }
    
    $status_class = ($response_code === 200) ? 'success' : 'error';
    echo "<span class='$status_class'>$url: HTTP $response_code</span><br>";
}

echo "<h2>Setup abgeschlossen</h2>";
echo "<p>Wenn alle Tests erfolgreich waren, sollte SpectraHost auf Ihrem Plesk-Server funktionieren.</p>";
echo "<p><a href='/'>Zur Startseite</a> | <a href='/debug.php'>Debug-Informationen</a></p>";

echo "</body></html>";
?>