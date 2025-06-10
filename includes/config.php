<?php
// SpectraHost Configuration

// Application Settings
define('APP_NAME', 'SpectraHost');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');

// Database Configuration (MySQL)
define('DB_TYPE', 'mysql');
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'spectrahost');
define('DB_USER', getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
define('DB_PORT', getenv('MYSQL_PORT') ?: '3306');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'SPECTRAHOST_SESSION');

// Security Settings
define('CSRF_TOKEN_NAME', '_token');
define('PASSWORD_MIN_LENGTH', 6);

// File Upload Settings
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx', 'zip']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Email Configuration (for future use)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@spectrahost.de');
define('SMTP_FROM_NAME', 'SpectraHost');

// Payment Configuration (Mollie - for future use)
define('MOLLIE_API_KEY', '');
define('MOLLIE_WEBHOOK_URL', '/api/payment/webhook');

// Proxmox Integration (for future use)
define('PROXMOX_HOST', '');
define('PROXMOX_USERNAME', '');
define('PROXMOX_PASSWORD', '');
define('PROXMOX_REALM', 'pam');

// Application URLs
define('BASE_URL', 'http://localhost:5000');
define('ADMIN_EMAIL', 'admin@spectrahost.de');

// Logging
define('LOG_LEVEL', 'DEBUG');
define('LOG_FILE', __DIR__ . '/../logs/app.log');

// Ticket System
define('TICKET_ATTACHMENTS_PATH', __DIR__ . '/../uploads/tickets/');
define('TICKET_MAX_ATTACHMENTS', 5);

// Ensure upload directories exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

if (!file_exists(TICKET_ATTACHMENTS_PATH)) {
    mkdir(TICKET_ATTACHMENTS_PATH, 0755, true);
}

if (!file_exists(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

// Database Connection
try {
    $dsn = DB_TYPE . ':host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    // For development, show error. In production, log it.
    if (APP_ENV === 'development') {
        die('Database connection failed: ' . $e->getMessage());
    } else {
        error_log('Database connection failed: ' . $e->getMessage());
        die('Database connection failed. Please contact support.');
    }
}

// Session Management
if (session_status() == PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>