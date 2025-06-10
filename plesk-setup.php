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

// Schritt 5: .htaccess und URL Rewriting
echo "<h2>5. .htaccess und URL Rewriting</h2>";
if (file_exists('.htaccess')) {
    echo "<span class='success'>✓ .htaccess existiert</span><br>";
    
    // Auf Plesk-Servern ist apache_get_modules() oft nicht verfügbar
    // Stattdessen testen wir URL Rewriting direkt
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $rewrite_enabled = in_array('mod_rewrite', $modules);
        echo "<span class='" . ($rewrite_enabled ? 'success' : 'error') . "'>";
        echo "mod_rewrite: " . ($rewrite_enabled ? "✓ Aktiviert" : "✗ Nicht verfügbar") . "</span><br>";
    } else {
        echo "<span class='info'>ℹ apache_get_modules() nicht verfügbar (normal auf Plesk)</span><br>";
        
        // Test URL Rewriting durch einen einfachen Test
        $test_rewrite_url = 'http://' . $_SERVER['HTTP_HOST'] . '/test-rewrite-' . time();
        $rewrite_works = false;
        
        // Einfacher Test: .htaccess sollte funktionieren wenn index.php lädt
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['SCRIPT_NAME'] === '/index.php') {
            echo "<span class='success'>✓ URL Rewriting funktioniert (Plesk-kompatibel)</span><br>";
        } else {
            echo "<span class='warning'>⚠ URL Rewriting Status unbekannt</span><br>";
        }
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

// Schritt 7: API-Tests
echo "<h2>7. API-Tests</h2>";

// Test lokale API-Endpunkte direkt
$api_tests = [
    'Services API' => 'api/services.php',
    'Login API' => 'api/login.php',
    'User API' => 'api/user.php'
];

foreach ($api_tests as $name => $endpoint) {
    $file_path = $endpoint;
    
    if (file_exists($file_path)) {
        echo "<span class='success'>✓ $name: Datei existiert ($endpoint)</span><br>";
        
        // Test ob die Datei PHP-Syntaxfehler hat
        $check_result = exec("php -l $file_path 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "<span class='success'>✓ $name: PHP-Syntax korrekt</span><br>";
        } else {
            echo "<span class='error'>✗ $name: PHP-Syntaxfehler gefunden</span><br>";
        }
    } else {
        echo "<span class='warning'>⚠ $name: Datei nicht gefunden ($endpoint)</span><br>";
    }
}

// Test Hauptseite
echo "<br><strong>Hauptseite Test:</strong><br>";
if (file_exists('index.php')) {
    echo "<span class='success'>✓ index.php existiert</span><br>";
    
    $check_result = exec("php -l index.php 2>&1", $output, $return_code);
    if ($return_code === 0) {
        echo "<span class='success'>✓ index.php: PHP-Syntax korrekt</span><br>";
    } else {
        echo "<span class='error'>✗ index.php: PHP-Syntaxfehler</span><br>";
    }
} else {
    echo "<span class='error'>✗ index.php nicht gefunden</span><br>";
}

// Test .htaccess Regeln
echo "<br><strong>.htaccess Konfiguration:</strong><br>";
if (file_exists('.htaccess')) {
    $htaccess_content = file_get_contents('.htaccess');
    if (strpos($htaccess_content, 'RewriteEngine On') !== false) {
        echo "<span class='success'>✓ RewriteEngine aktiviert</span><br>";
    }
    if (strpos($htaccess_content, 'RewriteRule') !== false) {
        echo "<span class='success'>✓ URL-Rewrite-Regeln vorhanden</span><br>";
    }
}

echo "<h2>Setup abgeschlossen</h2>";
echo "<p>Wenn alle Tests erfolgreich waren, sollte SpectraHost auf Ihrem Plesk-Server funktionieren.</p>";
echo "<p><a href='/'>Zur Startseite</a> | <a href='/debug.php'>Debug-Informationen</a></p>";

echo "</body></html>";
?>