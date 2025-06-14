<?php
// SpectraHost - Einfaches Routing-System

// Basis-Konfiguration
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

// Route ermitteln
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($path, '/');

// API-Requests weiterleiten
if (strpos($route, 'api/') === 0) {
    $apiEndpoint = substr($route, 4); // Remove 'api/' prefix
    $apiFile = __DIR__ . '/api/' . $apiEndpoint;
    
    // Add .php extension if not present
    if (!str_ends_with($apiFile, '.php')) {
        $apiFile .= '.php';
    }
    
    if (file_exists($apiFile)) {
        include $apiFile;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API endpoint not found: ' . $apiEndpoint]);
    }
    exit;
}

// Standard-Routen definieren
$routes = [
    '' => 'home',
    'home' => 'home',
    'login' => 'login',
    'register' => 'register',
    'products' => 'products',
    'services' => 'products',
    'webspace' => 'products/webspace',
    'gameserver' => 'products/gameserver',
    'domain' => 'products/domain',
    'domains' => 'products/domain',
    'products/webhosting' => 'products/webspace',
    'products/vps' => 'products/vps',
    'products/domain' => 'products/domain',
    'products/domains' => 'products/domain',
    'products/gameserver' => 'products/gameserver',
    'order' => 'order',
    'bestellen' => 'order',
    'order-vps' => 'order-vps',
    'order-webhosting' => 'order-webhosting',
    'order-gameserver' => 'order-gameserver',
    'order-domain' => 'order-domain',
    'vps-bestellen' => 'order-vps',
    'webhosting-bestellen' => 'order-webhosting',
    'gameserver-bestellen' => 'order-gameserver',
    'domain-bestellen' => 'order-domain',
    'contact' => 'contact',
    'impressum' => 'impressum'
];

// Admin-Routing - spezielle Behandlung für /admin/ Ordner
if (strpos($route, 'admin') === 0) {
    $adminParts = explode('/', $route);
    if (count($adminParts) == 1) {
        // /admin -> /admin/dashboard.php
        $page = 'admin/dashboard';
    } else {
        // Spezielle Admin-Routen
        $adminRoutes = [
            'dashboard' => 'admin/dashboard',
            'users' => 'admin/users',
            'services' => 'admin/services',
            'orders' => 'admin/orders',
            'tickets' => 'admin/tickets',
            'settings' => 'admin/settings',
            'ip-management' => 'admin/ip-management',
            'proxmox' => 'admin/proxmox',
            'logs' => 'admin/logs'
        ];
        
        if (isset($adminRoutes[$adminParts[1]])) {
            $page = $adminRoutes[$adminParts[1]];
        } else {
            // /admin/something -> /admin/something.php
            $page = 'admin/' . $adminParts[1];
        }
    }
}
// Dashboard-Routing - spezielle Behandlung für /dashboard/ Ordner
elseif (strpos($route, 'dashboard') === 0) {
    $dashboardParts = explode('/', $route);
    if (count($dashboardParts) == 1) {
        // /dashboard -> /dashboard/index.php
        $page = 'dashboard/index';
    } else {
        // Spezielle Dashboard-Routen
        $dashboardRoutes = [
            'ticket-view' => 'dashboard/ticket-view',
            'create-ticket' => 'dashboard/create-ticket',
            'update-ticket-status' => 'dashboard/update-ticket-status',
            'add-ticket-reply' => 'dashboard/add-ticket-reply'
        ];
        
        if (isset($dashboardRoutes[$dashboardParts[1]])) {
            $page = $dashboardRoutes[$dashboardParts[1]];
        } else {
            // /dashboard/something -> /dashboard/something.php
            $page = 'dashboard/' . $dashboardParts[1];
        }
    }
} else {
    // Normale Route bestimmen
    $page = $routes[$route] ?? null;
}

// 404 für unbekannte Routen
if (!$page) {
    http_response_code(404);
    $page = 'not-found';
}

// Seiten-Datei laden
$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($pageFile)) {
    include $pageFile;
} else {
    // Fallback 404
    renderHeader('Seite nicht gefunden - SpectraHost');
    echo '<div class="container mx-auto px-4 py-8 text-center">';
    echo '<h1 class="text-4xl font-bold text-gray-800 mb-4">404 - Seite nicht gefunden</h1>';
    echo '<p class="text-gray-600 mb-8">Die angeforderte Seite konnte nicht gefunden werden.</p>';
    echo '<a href="/" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">Zur Startseite</a>';
    echo '</div>';
    renderFooter();
}
?>