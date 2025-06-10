<?php
// Plesk-spezifische Live-Konfiguration für SpectraHost

// Umgebungserkennung
define('IS_PLESK', true);
define('IS_PRODUCTION', true);
define('DOMAIN_NAME', $_SERVER['HTTP_HOST'] ?? 'spectrahost.de');

// URL-Konfiguration für Plesk
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
define('BASE_URL', $protocol . DOMAIN_NAME);
define('API_BASE_URL', BASE_URL . '/api');

// Pfad-Konfiguration
define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
define('INCLUDES_PATH', DOCUMENT_ROOT . '/includes');
define('UPLOADS_PATH', DOCUMENT_ROOT . '/uploads');
define('LOGS_PATH', DOCUMENT_ROOT . '/logs');

// Session-Konfiguration für Plesk
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_domain', '.' . DOMAIN_NAME);
ini_set('session.gc_maxlifetime', 7200);

// Fehlerbehandlung für Produktion
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// Zeitzone für Deutschland
date_default_timezone_set('Europe/Berlin');

// Sicherheitsheader für Plesk
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// URL-Helper-Funktionen für Plesk
function getBaseUrl() {
    return BASE_URL;
}

function getApiUrl($endpoint = '') {
    return API_BASE_URL . ($endpoint ? '/' . ltrim($endpoint, '/') : '');
}

function getAssetUrl($asset) {
    return BASE_URL . '/assets/' . ltrim($asset, '/');
}

function redirectTo($path) {
    $url = (strpos($path, 'http') === 0) ? $path : BASE_URL . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

// Verzeichnisse für Plesk erstellen
$directories = [
    UPLOADS_PATH,
    LOGS_PATH,
    DOCUMENT_ROOT . '/cache',
    DOCUMENT_ROOT . '/tmp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Log-Funktion für Plesk
function pleskLog($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    if (is_writable(LOGS_PATH)) {
        file_put_contents(LOGS_PATH . '/spectrahost.log', $log_message, FILE_APPEND | LOCK_EX);
    } else {
        error_log($log_message);
    }
}

// Cache-Konfiguration für Plesk
if (function_exists('opcache_reset')) {
    opcache_reset();
}

pleskLog("Plesk configuration loaded for domain: " . DOMAIN_NAME);
?>