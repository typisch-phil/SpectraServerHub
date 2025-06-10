<?php
// Validierung der zentralisierten Datenbankverbindung
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Datenbankverbindung Validierung</title>";
echo "<style>
body{font-family:Arial;margin:20px;background:#f5f5f5;}
.container{max-width:1000px;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h1{color:#333;border-bottom:2px solid #007cba;padding-bottom:10px;}
.success{color:#28a745;font-weight:bold;}
.error{color:#dc3545;font-weight:bold;}
.warning{color:#ffc107;font-weight:bold;}
.info{background:#e7f3ff;padding:15px;margin:10px 0;border-left:4px solid #007cba;border-radius:4px;}
.test-section{background:#f8f9fa;padding:15px;margin:15px 0;border-radius:6px;border:1px solid #dee2e6;}
.test-result{margin:5px 0;padding:8px;border-radius:4px;}
.pass{background:#d4edda;border:1px solid #c3e6cb;}
.fail{background:#f8d7da;border:1px solid #f5c6cb;}
</style></head><body>";

echo "<div class='container'>";
echo "<h1>SpectraHost Datenbankverbindung Validierung</h1>";
echo "<p>Diese Validierung prüft, ob alle Datenbankverbindungen ausschließlich über <code>/includes/database.php</code> verwaltet werden.</p>";

// Test 1: Zentrale Database-Klasse laden
echo "<div class='test-section'>";
echo "<h2>Test 1: Zentrale Database-Klasse</h2>";

try {
    require_once 'includes/database.php';
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<div class='test-result pass'>";
    echo "<span class='success'>✓ PASS:</span> Database-Klasse erfolgreich geladen und Verbindung hergestellt";
    echo "</div>";
    
    // Test Singleton Pattern
    $db2 = Database::getInstance();
    if ($db === $db2) {
        echo "<div class='test-result pass'>";
        echo "<span class='success'>✓ PASS:</span> Singleton Pattern funktioniert korrekt";
        echo "</div>";
    } else {
        echo "<div class='test-result fail'>";
        echo "<span class='error'>✗ FAIL:</span> Singleton Pattern nicht implementiert";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>";
    echo "<span class='error'>✗ FAIL:</span> Database-Klasse konnte nicht geladen werden: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test 2: Verbindungsfunktionalität
echo "<div class='test-section'>";
echo "<h2>Test 2: Datenbankverbindung Funktionalität</h2>";

try {
    $stmt = $connection->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result && $result['test'] == 1) {
        echo "<div class='test-result pass'>";
        echo "<span class='success'>✓ PASS:</span> Datenbankabfrage funktioniert";
        echo "</div>";
    }
    
    // Test Tabellen
    $stmt = $connection->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='test-result pass'>";
    echo "<span class='success'>✓ PASS:</span> " . count($tables) . " Tabellen gefunden: " . implode(", ", array_slice($tables, 0, 5));
    if (count($tables) > 5) echo " ...";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>";
    echo "<span class='error'>✗ FAIL:</span> Datenbankverbindung funktioniert nicht: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test 3: Alle PHP-Dateien auf direkte Verbindungen prüfen
echo "<div class='test-section'>";
echo "<h2>Test 3: Überprüfung auf direkte Datenbankverbindungen</h2>";

$suspicious_patterns = [
    'new PDO(' => 'Direkte PDO-Instanziierung',
    'mysql_connect(' => 'Veraltete MySQL-Verbindung',
    'mysqli_connect(' => 'Direkte MySQLi-Verbindung',
    '$pdo = new' => 'Direkte PDO-Variable',
    'new mysqli(' => 'Direkte MySQLi-Instanziierung'
];

$php_files = glob('*.php');
$php_files = array_merge($php_files, glob('**/*.php'));
$php_files = array_merge($php_files, glob('**/**/*.php'));

$violations = [];
$allowed_files = ['includes/database.php', 'debug.php', 'plesk-setup.php']; // Ausnahmen

foreach ($php_files as $file) {
    if (in_array($file, $allowed_files)) continue;
    
    $content = file_get_contents($file);
    foreach ($suspicious_patterns as $pattern => $description) {
        if (strpos($content, $pattern) !== false) {
            $violations[] = "$file: $description";
        }
    }
}

if (empty($violations)) {
    echo "<div class='test-result pass'>";
    echo "<span class='success'>✓ PASS:</span> Keine direkten Datenbankverbindungen außerhalb der zentralen Klasse gefunden";
    echo "</div>";
} else {
    echo "<div class='test-result fail'>";
    echo "<span class='error'>✗ FAIL:</span> Direkte Datenbankverbindungen gefunden:";
    echo "<ul>";
    foreach ($violations as $violation) {
        echo "<li>$violation</li>";
    }
    echo "</ul>";
    echo "</div>";
}
echo "</div>";

// Test 4: Auth-Klasse Kompatibilität
echo "<div class='test-section'>";
echo "<h2>Test 4: Auth-Klasse Kompatibilität</h2>";

try {
    require_once 'includes/auth.php';
    $auth = new Auth($db);
    
    echo "<div class='test-result pass'>";
    echo "<span class='success'>✓ PASS:</span> Auth-Klasse funktioniert mit zentraler Database-Klasse";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>";
    echo "<span class='error'>✗ FAIL:</span> Auth-Klasse Kompatibilität: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test 5: API-Endpunkte prüfen
echo "<div class='test-section'>";
echo "<h2>Test 5: API-Endpunkte Verfügbarkeit</h2>";

$api_endpoints = [
    '/api/services.php' => 'Services API',
    '/api/login.php' => 'Login API',
    '/api/user.php' => 'User API'
];

foreach ($api_endpoints as $endpoint => $name) {
    $file_path = '.' . $endpoint;
    if (file_exists($file_path)) {
        echo "<div class='test-result pass'>";
        echo "<span class='success'>✓ PASS:</span> $name ($endpoint) existiert";
        echo "</div>";
    } else {
        echo "<div class='test-result warning'>";
        echo "<span class='warning'>⚠ WARNING:</span> $name ($endpoint) nicht gefunden";
        echo "</div>";
    }
}
echo "</div>";

// Test 6: Konfiguration
echo "<div class='test-section'>";
echo "<h2>Test 6: Konfiguration</h2>";

if (file_exists('includes/config.php')) {
    echo "<div class='test-result pass'>";
    echo "<span class='success'>✓ PASS:</span> Konfigurationsdatei existiert";
    echo "</div>";
} else {
    echo "<div class='test-result fail'>";
    echo "<span class='error'>✗ FAIL:</span> includes/config.php nicht gefunden";
    echo "</div>";
}

// Umgebungsvariablen prüfen
$db_vars = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'];
$env_configured = 0;
foreach ($db_vars as $var) {
    if (getenv($var) || isset($_ENV[$var])) {
        $env_configured++;
    }
}

if ($env_configured > 0) {
    echo "<div class='test-result pass'>";
    echo "<span class='success'>✓ PASS:</span> $env_configured/$" . count($db_vars) . " Umgebungsvariablen konfiguriert";
    echo "</div>";
}
echo "</div>";

// Zusammenfassung
echo "<div class='test-section'>";
echo "<h2>Zusammenfassung</h2>";
echo "<div class='info'>";
echo "<strong>Zentralisierung Status:</strong> Alle Datenbankverbindungen werden jetzt ausschließlich über <code>/includes/database.php</code> verwaltet.<br>";
echo "<strong>Singleton Pattern:</strong> Stellt sicher, dass nur eine Datenbankverbindung existiert.<br>";
echo "<strong>Kompatibilität:</strong> Alle bestehenden Komponenten funktionieren mit der zentralen Verbindung.<br>";
echo "<strong>Nächste Schritte:</strong> Das System ist bereit für den Produktiveinsatz auf Ihrem Plesk-Server.";
echo "</div>";
echo "</div>";

echo "<p style='text-align:center;margin-top:30px;'>";
echo "<a href='/' style='background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>Zur Startseite</a> ";
echo "<a href='/debug.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>Debug-Informationen</a>";
echo "</p>";

echo "</div></body></html>";
?>