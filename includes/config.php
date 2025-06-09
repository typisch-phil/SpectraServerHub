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
?>