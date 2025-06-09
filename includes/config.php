<?php
// Database configuration - Using PostgreSQL
define('DB_HOST', $_ENV['PGHOST'] ?? 'localhost');
define('DB_NAME', $_ENV['PGDATABASE'] ?? 'spectrahost');
define('DB_USER', $_ENV['PGUSER'] ?? 'postgres');
define('DB_PASS', $_ENV['PGPASSWORD'] ?? '');
define('DB_PORT', $_ENV['PGPORT'] ?? 5432);
define('DB_TYPE', 'pgsql');

// Mollie API configuration
define('MOLLIE_API_KEY', $_ENV['MOLLIE_API_KEY'] ?? '');
define('MOLLIE_TEST_MODE', $_ENV['MOLLIE_TEST_MODE'] ?? true);

// Proxmox API configuration
define('PROXMOX_HOST', $_ENV['PROXMOX_HOST'] ?? '');
define('PROXMOX_USER', $_ENV['PROXMOX_USER'] ?? '');
define('PROXMOX_PASS', $_ENV['PROXMOX_PASS'] ?? '');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 7200); // 2 hours

// Site configuration
define('SITE_NAME', 'SpectraHost');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://localhost');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@spectrahost.de');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>