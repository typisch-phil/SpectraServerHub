<?php
// Robuste Index-Datei für Plesk-Live-Umgebung
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session sicher starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection - direkt ohne komplexe Includes
try {
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $db_user = "s9281_spectrahost";
    $db_pass = getenv('MYSQL_PASSWORD') ?: '';
    
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    http_response_code(500);
    
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed'
        ]);
    } else {
        echo "<!DOCTYPE html><html><head><title>Database Error</title></head><body>";
        echo "<h1>Database Connection Error</h1>";
        echo "<p>The application cannot connect to the database. Please check your configuration.</p>";
        echo "</body></html>";
    }
    exit;
}

// Get the requested path - Plesk compatible routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Handle route parameter from .htaccess for Plesk
if (isset($_GET['route'])) {
    $path = $_GET['route'];
} else {
    $path = ltrim($path, '/');
}

// Remove any trailing slashes
$path = rtrim($path, '/');

// Handle subdirectory installations on Plesk
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
if ($script_dir !== '/') {
    $path = str_replace($script_dir, '', $path);
    $path = ltrim($path, '/');
}

// Route handling
switch ($path) {
    case '':
    case 'home':
        include __DIR__ . '/pages/home.php';
        break;
        
    case 'login':
        include __DIR__ . '/pages/login.php';
        break;
        
    case 'register':
        include __DIR__ . '/pages/register.php';
        break;
        
    case 'order':
        include __DIR__ . '/pages/order.php';
        break;
        
    case 'contact':
        include __DIR__ . '/pages/contact.php';
        break;
        
    case 'impressum':
        include __DIR__ . '/pages/impressum.php';
        break;
        
    case 'dashboard':
        include __DIR__ . '/pages/dashboard/index.php';
        break;
        
    case 'dashboard/billing':
        include __DIR__ . '/pages/dashboard/billing.php';
        break;
        
    case 'admin':
    case 'admin/dashboard':
        include __DIR__ . '/pages/admin/index.php';
        break;
        
    case 'admin/services':
        include __DIR__ . '/pages/admin/services.php';
        break;
        
    case 'admin/users':
        include __DIR__ . '/pages/admin/users.php';
        break;
        
    case 'admin/invoices':
        include __DIR__ . '/pages/admin/invoices.php';
        break;
        
    case 'admin/integrations':
        include __DIR__ . '/pages/admin/integrations.php';
        break;
        
    default:
        // Check if it's an API request
        if (strpos($path, 'api/') === 0) {
            // Let .htaccess handle API routes
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            exit;
        }
        
        // 404 page
        http_response_code(404);
        include __DIR__ . '/pages/not-found.php';
        break;
}
?>