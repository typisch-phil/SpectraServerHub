<?php
// Load error handler first for better debugging
require_once __DIR__ . '/error-handler.php';

// Check if we're running on Plesk and load appropriate config
if (file_exists(__DIR__ . '/plesk-config.php')) {
    require_once __DIR__ . '/plesk-config.php';
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

// Ensure database connection is established
try {
    require_once __DIR__ . '/includes/database.php';
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

// Get the requested path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

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