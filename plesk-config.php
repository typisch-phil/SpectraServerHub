<?php
// Plesk-spezifische Konfiguration für SpectraHost
// Diese Datei sollte mit Ihren echten Plesk-Datenbankdaten angepasst werden

// Fehlerberichterstattung für Debugging (in Produktion ausschalten)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Plesk-spezifische MySQL-Konfiguration
// WICHTIG: Ersetzen Sie diese Werte mit Ihren echten Plesk-Datenbankdaten
define('PLESK_DB_HOST', 'localhost'); // Oft 'localhost' oder '127.0.0.1' in Plesk
define('PLESK_DB_NAME', 'your_database_name'); // Ihr Plesk-Datenbankname
define('PLESK_DB_USER', 'your_database_user'); // Ihr Plesk-Datenbankbenutzer
define('PLESK_DB_PASS', 'your_database_password'); // Ihr Plesk-Datenbankpasswort
define('PLESK_DB_PORT', '3306'); // Standard MySQL-Port

// Plesk-spezifische Pfade
define('PLESK_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
define('PLESK_LOG_PATH', PLESK_DOCUMENT_ROOT . '/logs/');
define('PLESK_UPLOAD_PATH', PLESK_DOCUMENT_ROOT . '/uploads/');

// Session-Konfiguration für Plesk
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

// Timezone für Deutschland
date_default_timezone_set('Europe/Berlin');

// Sichere Verzeichnisse erstellen
$directories = [
    PLESK_LOG_PATH,
    PLESK_UPLOAD_PATH,
    PLESK_DOCUMENT_ROOT . '/cache/',
    PLESK_DOCUMENT_ROOT . '/tmp/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Log-Funktion für Debugging
function plesk_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    if (defined('PLESK_LOG_PATH') && is_writable(PLESK_LOG_PATH)) {
        file_put_contents(PLESK_LOG_PATH . 'spectrahost.log', $log_message, FILE_APPEND | LOCK_EX);
    } else {
        error_log($log_message);
    }
}

// Datenbankverbindung für Plesk testen
function test_plesk_database_connection() {
    try {
        $dsn = "mysql:host=" . PLESK_DB_HOST . ";port=" . PLESK_DB_PORT . ";dbname=" . PLESK_DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, PLESK_DB_USER, PLESK_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        $pdo->query("SELECT 1");
        plesk_log("Database connection successful");
        return true;
        
    } catch (PDOException $e) {
        plesk_log("Database connection failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// PHP-Konfiguration für Plesk optimieren
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60);
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');

plesk_log("Plesk configuration loaded successfully");
?>