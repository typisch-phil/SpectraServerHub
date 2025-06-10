<?php
// SpectraHost - Hauptrouting-System
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session sicher starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basis-Includes laden
try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/database.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/layout.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
}

// Globale Datenbankverbindung bereitstellen
$db = null;
try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

// Routing-Parameter ermitteln
$route = '';
if (isset($_GET['route'])) {
    $route = trim($_GET['route'], '/');
} else {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $route = trim($path, '/');
}

// Leere Route = Startseite
if (empty($route)) {
    $route = 'home';
}

// API-Routing
if (strpos($route, 'api/') === 0) {
    handleApiRoute($route);
    exit;
}

// Content-Type für HTML-Seiten
header('Content-Type: text/html; charset=utf-8');

// Haupt-Routing
switch ($route) {
    case 'home':
    case '':
        include __DIR__ . '/pages/home.php';
        break;
        
    case 'login':
        // Prüfe zuerst im Hauptverzeichnis, dann im pages-Ordner
        if (file_exists(__DIR__ . '/login.php')) {
            include __DIR__ . '/login.php';
        } else {
            include __DIR__ . '/pages/login.php';
        }
        break;
        
    case 'register':
        // Prüfe zuerst im Hauptverzeichnis, dann im pages-Ordner
        if (file_exists(__DIR__ . '/register.php')) {
            include __DIR__ . '/register.php';
        } else {
            include __DIR__ . '/pages/register.php';
        }
        break;
        
    case 'dashboard':
        include __DIR__ . '/pages/dashboard.php';
        break;
        
    case 'contact':
        include __DIR__ . '/pages/contact.php';
        break;
        
    case 'impressum':
        include __DIR__ . '/pages/impressum.php';
        break;
        
    case 'order':
        include __DIR__ . '/pages/order.php';
        break;
        
    // Produktseiten
    case 'webspace':
    case 'products/webhosting':
        include __DIR__ . '/pages/products/webhosting.php';
        break;
        
    case 'vserver':
    case 'products/vps':
        include __DIR__ . '/pages/products/vps.php';
        break;
        
    case 'gameserver':
    case 'products/gameserver':
        include __DIR__ . '/pages/products/gameserver.php';
        break;
        
    case 'domain':
    case 'products/domains':
        include __DIR__ . '/pages/products/domains.php';
        break;
        
    // Dashboard-Unterseiten
    case 'dashboard/services':
        include __DIR__ . '/pages/dashboard/services.php';
        break;
        
    case 'dashboard/billing':
        include __DIR__ . '/pages/dashboard/billing.php';
        break;
        
    case 'dashboard/support':
        include __DIR__ . '/pages/dashboard/support.php';
        break;
        
    // Admin-Panel
    case 'admin':
        include __DIR__ . '/pages/admin/dashboard.php';
        break;
        
    case 'admin/users':
        include __DIR__ . '/pages/admin/users.php';
        break;
        
    case 'admin/services':
        include __DIR__ . '/pages/admin/services.php';
        break;
        
    case 'admin/tickets':
        include __DIR__ . '/pages/admin/tickets.php';
        break;
        
    case 'admin/invoices':
        include __DIR__ . '/pages/admin/invoices.php';
        break;
        
    case 'admin/integrations':
        include __DIR__ . '/pages/admin/integrations.php';
        break;
        
    case 'admin/statistics':
        include __DIR__ . '/pages/admin/statistics.php';
        break;
        
    // Fehlerbehandlung
    case 'error':
        handleErrorPage();
        break;
        
    default:
        // 404 Seite
        http_response_code(404);
        include __DIR__ . '/pages/404.php';
        break;
}

// API-Routing-Handler
function handleApiRoute($route) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Preflight-Anfragen
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // API-Route extrahieren
    $api_path = str_replace('api/', '', $route);
    $parts = explode('/', $api_path);
    $endpoint = $parts[0] ?? '';
    
    // API-Datei einbinden mit globaler Datenbankverbindung
    global $db;
    $api_file = __DIR__ . '/api/' . $endpoint . '.php';
    
    if (file_exists($api_file)) {
        include $api_file;
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'API endpoint not found',
            'endpoint' => $endpoint,
            'path' => $api_file,
            'available' => ['login', 'register', 'services', 'user', 'order']
        ]);
    }
}

// Fehlerseiten-Handler
function handleErrorPage() {
    $code = $_GET['code'] ?? 404;
    $title = 'Fehler';
    $message = 'Ein Fehler ist aufgetreten.';
    
    switch ($code) {
        case 400:
            $title = 'Ungültige Anfrage';
            $message = 'Die Anfrage konnte nicht verarbeitet werden.';
            break;
        case 401:
            $title = 'Nicht autorisiert';
            $message = 'Sie sind nicht berechtigt, diese Seite aufzurufen.';
            break;
        case 403:
            $title = 'Zugriff verweigert';
            $message = 'Der Zugriff auf diese Ressource ist nicht erlaubt.';
            break;
        case 404:
            $title = 'Seite nicht gefunden';
            $message = 'Die angeforderte Seite existiert nicht.';
            break;
        case 500:
            $title = 'Server-Fehler';
            $message = 'Ein interner Server-Fehler ist aufgetreten.';
            break;
    }
    
    http_response_code($code);
    
    $pageTitle = $title . ' - SpectraHost';
    renderHeader($pageTitle);
    
    echo "<div class='min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900'>";
    echo "<div class='max-w-md mx-auto text-center bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg'>";
    echo "<h1 class='text-6xl font-bold text-red-600 mb-4'>$code</h1>";
    echo "<h2 class='text-2xl font-semibold text-gray-800 dark:text-white mb-4'>$title</h2>";
    echo "<p class='text-gray-600 dark:text-gray-400 mb-8'>$message</p>";
    echo "<a href='/' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>Zur Startseite</a>";
    echo "</div></div>";
    
    renderFooter();
}
?>