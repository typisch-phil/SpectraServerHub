<?php
// Debug-Datei für Plesk-Fehlerdiagnose
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SpectraHost Debug Information</h1>";

// PHP-Version prüfen
echo "<h2>PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Erweiterungen prüfen
echo "<h2>Required Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'curl', 'json', 'session'];
foreach ($required as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✓ Loaded" : "✗ Missing") . "<br>";
}

// Umgebungsvariablen prüfen
echo "<h2>Environment Variables</h2>";
$env_vars = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo $var . ": " . ($value ? "Set" : "Not set") . "<br>";
}

// Dateiberechtigungen prüfen
echo "<h2>File Permissions</h2>";
$files = ['includes/config.php', 'includes/database.php', '.htaccess', 'index.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo $file . ": " . substr(sprintf('%o', fileperms($file)), -4) . " (" . (is_readable($file) ? "readable" : "not readable") . ")<br>";
    } else {
        echo $file . ": File not found<br>";
    }
}

// Datenbankverbindung testen über zentrale Database-Klasse
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "✓ Database connection successful<br>";
    
    // Tabellen prüfen
    $stmt = $connection->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "<br>";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

// .htaccess-Regeln testen
echo "<h2>Apache Configuration</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $required_modules = ['mod_rewrite', 'mod_headers'];
    foreach ($required_modules as $module) {
        echo $module . ": " . (in_array($module, $modules) ? "✓ Loaded" : "✗ Missing") . "<br>";
    }
} else {
    echo "apache_get_modules() not available<br>";
}

echo "<h2>Error Logs</h2>";
$error_log = ini_get('error_log');
echo "Error log location: " . ($error_log ?: "Not set") . "<br>";

if (file_exists('error.log')) {
    echo "<h3>Recent errors:</h3>";
    echo "<pre>" . htmlspecialchars(tail('error.log', 20)) . "</pre>";
}

function tail($filename, $lines = 10) {
    $file = file($filename);
    return implode("", array_slice($file, -$lines));
}
?>