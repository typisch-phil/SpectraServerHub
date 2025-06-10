<?php
// SpectraHost - Plesk-kompatible Hauptdatei
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basis-Konfiguration laden
try {
    if (file_exists(__DIR__ . '/includes/config.php')) {
        require_once __DIR__ . '/includes/config.php';
    }
    
    if (file_exists(__DIR__ . '/includes/database.php')) {
        require_once __DIR__ . '/includes/database.php';
    }
} catch (Exception $e) {
    error_log("Configuration error: " . $e->getMessage());
}

// Routing-System für Plesk
$request_uri = $_SERVER['REQUEST_URI'];
$route = '';

// Route aus .htaccess Parameter extrahieren
if (isset($_GET['route'])) {
    $route = trim($_GET['route'], '/');
} else {
    // Fallback: Route aus URI extrahieren
    $path = parse_url($request_uri, PHP_URL_PATH);
    $route = trim($path, '/');
}

// Leere Route = Startseite
if (empty($route)) {
    $route = 'home';
}

// API-Anfragen verarbeiten
if (strpos($route, 'api/') === 0) {
    handleApiRequest($route);
    exit;
}

// Content-Type für HTML setzen
header('Content-Type: text/html; charset=utf-8');

// Seiten-Routing
switch ($route) {
    case 'home':
    case '':
        loadPage('home');
        break;
        
    case 'services':
        loadPage('services');
        break;
        
    case 'webspace':
        loadPage('products/webspace');
        break;
        
    case 'vserver':
        loadPage('products/vserver');
        break;
        
    case 'gameserver':
        loadPage('products/gameserver');
        break;
        
    case 'domain':
        loadPage('products/domain');
        break;
        
    case 'login':
        loadPage('login');
        break;
        
    case 'register':
        loadPage('register');
        break;
        
    case 'dashboard':
        loadPage('dashboard');
        break;
        
    case 'order':
        loadPage('order');
        break;
        
    case 'contact':
        loadPage('contact');
        break;
        
    case 'impressum':
        loadPage('impressum');
        break;
        
    default:
        loadPage('404');
        break;
}

// Seite laden und ausgeben
function loadPage($page) {
    $page_file = __DIR__ . '/pages/' . $page . '.php';
    
    if (file_exists($page_file)) {
        // Globale Variablen für Seiten bereitstellen
        global $db;
        
        // Datenbankverbindung falls verfügbar
        try {
            if (class_exists('Database')) {
                $db = Database::getInstance();
            }
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $db = null;
        }
        
        // Seite einbinden
        include $page_file;
    } else {
        // 404 Seite falls nicht gefunden
        http_response_code(404);
        echo generateErrorPage(404, 'Seite nicht gefunden', 'Die angeforderte Seite existiert nicht.');
    }
}

// API-Anfragen verarbeiten
function handleApiRequest($route) {
    header('Content-Type: application/json');
    
    // API-Route extrahieren
    $api_path = str_replace('api/', '', $route);
    $parts = explode('/', $api_path);
    $endpoint = $parts[0] ?? '';
    
    // API-Datei suchen
    $api_file = __DIR__ . '/api/' . $endpoint . '.php';
    
    if (file_exists($api_file)) {
        include $api_file;
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'API endpoint not found',
            'endpoint' => $endpoint
        ]);
    }
}

// Fehlerseite generieren
function generateErrorPage($code, $title, $message) {
    return "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$title - SpectraHost</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center'>
    <div class='max-w-md mx-auto text-center bg-white p-8 rounded-lg shadow-lg'>
        <h1 class='text-6xl font-bold text-blue-600 mb-4'>$code</h1>
        <h2 class='text-2xl font-semibold text-gray-800 mb-4'>$title</h2>
        <p class='text-gray-600 mb-8'>$message</p>
        <a href='/' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>
            Zur Startseite
        </a>
    </div>
</body>
</html>";
}
?>