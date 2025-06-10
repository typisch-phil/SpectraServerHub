<?php
/**
 * SpectraHost Deployment Script
 * Run this script after uploading files to your webserver
 */

echo "=== SpectraHost Deployment Script ===\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die("ERROR: PHP 8.1.0 or higher is required. Current version: " . PHP_VERSION . "\n");
}
echo "✓ PHP Version: " . PHP_VERSION . "\n";

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'openssl', 'session'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("ERROR: Required PHP extension '$ext' is not loaded.\n");
    }
    echo "✓ Extension: $ext\n";
}

// Load environment variables
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
    echo "✓ Environment variables loaded\n";
} else {
    echo "! Warning: .env file not found. Using default configuration.\n";
}

// Test database connection
try {
    $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
    $dbname = $_ENV['MYSQL_DATABASE'] ?? 'spectrahost';
    $username = $_ENV['MYSQL_USER'] ?? 'root';
    $password = $_ENV['MYSQL_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful\n";
    
    // Check if tables exist
    $tables = ['users', 'services', 'user_services', 'payments', 'integrations'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table exists: $table\n";
        } else {
            echo "! Warning: Table missing: $table\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .env file.\n";
}

// Check write permissions
$write_dirs = ['logs', 'uploads'];
foreach ($write_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (is_writable($dir)) {
        echo "✓ Directory writable: $dir\n";
    } else {
        echo "✗ Directory not writable: $dir (chmod 755 required)\n";
    }
}

// Check .htaccess
if (file_exists('.htaccess')) {
    echo "✓ .htaccess file exists\n";
} else {
    echo "! Warning: .htaccess file missing. URL rewriting may not work.\n";
}

// Security checks
if (file_exists('.env') && is_readable('.env')) {
    echo "! Security Warning: .env file is readable. Ensure it's protected by .htaccess\n";
}

// Test API endpoints
echo "\nTesting API endpoints...\n";
$base_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'spectrahost.de');

$endpoints = [
    '/api/services.php' => 'Services API',
    '/api/login.php' => 'Login API (POST)'
];

foreach ($endpoints as $endpoint => $name) {
    $url = $base_url . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($endpoint === '/api/login.php') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test', 'password' => 'test']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 || $http_code === 400) {
        echo "✓ $name accessible (HTTP $http_code)\n";
    } else {
        echo "✗ $name failed (HTTP $http_code)\n";
    }
}

echo "\n=== Deployment Complete ===\n";
echo "Domain: " . ($_SERVER['HTTP_HOST'] ?? 'spectrahost.de') . "\n";
echo "Next steps:\n";
echo "1. Configure your .env file with production values\n";
echo "2. Import database schema if not already done\n";
echo "3. Test login with admin@spectrahost.de / admin123\n";
echo "4. Configure Mollie and Proxmox integrations in admin panel\n";
echo "5. Change default admin password\n\n";
?>