<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Route handling
switch ($path) {
    case '/':
        include __DIR__ . '/../pages/home.php';
        break;
    case '/login':
        // Redirect logged in users to dashboard
        if (isset($_SESSION['user']) && $_SESSION['user']) {
            header('Location: /dashboard');
            exit;
        }
        include __DIR__ . '/../pages/login.php';
        break;
    case '/register':
        // Redirect logged in users to dashboard
        if (isset($_SESSION['user']) && $_SESSION['user']) {
            header('Location: /dashboard');
            exit;
        }
        include __DIR__ . '/../pages/register.php';
        break;
    case '/dashboard':
        include __DIR__ . '/../pages/dashboard/index.php';
        break;
    case '/dashboard/services':
        include __DIR__ . '/../pages/dashboard/services.php';
        break;
    case '/dashboard/billing':
        include __DIR__ . '/../pages/dashboard/billing.php';
        break;
    case '/dashboard/support':
        include __DIR__ . '/../pages/dashboard/support.php';
        break;
    case '/admin':
        include __DIR__ . '/../pages/admin/dashboard.php';
        break;
    case '/admin/users':
        include __DIR__ . '/../pages/admin/users.php';
        break;
    case '/admin/services':
        include __DIR__ . '/../pages/admin/services.php';
        break;
    case '/admin/tickets':
        include __DIR__ . '/../pages/admin/tickets.php';
        break;
    case '/admin/invoices':
        include __DIR__ . '/../pages/admin/invoices.php';
        break;
    case '/admin/statistics':
        include __DIR__ . '/../pages/admin/statistics.php';
        break;
    case '/admin/integrations':
        include __DIR__ . '/../pages/admin/integrations.php';
        break;
    case '/order':
        include __DIR__ . '/../pages/order.php';
        break;
    case '/contact':
        include __DIR__ . '/../pages/contact.php';
        break;
    case '/impressum':
        include __DIR__ . '/../pages/impressum.php';
        break;
    case '/api/login':
        include __DIR__ . '/../api/login.php';
        break;
    case '/api/register':
        include __DIR__ . '/../api/register.php';
        break;
    case '/api/logout':
        include __DIR__ . '/../api/logout.php';
        break;
    case '/api/order':
        include __DIR__ . '/../api/order.php';
        break;
    case '/api/services':
        include __DIR__ . '/../api/services.php';
        break;
    case '/api/user/services':
        include __DIR__ . '/../api/user/services.php';
        break;
    case '/api/user/status':
        include __DIR__ . '/../api/user/status.php';
        break;
    case '/api/user/add-balance':
        include __DIR__ . '/../api/user/add-balance.php';
        break;
    case '/api/payment/webhook':
        include __DIR__ . '/../api/payment/webhook.php';
        break;
    case '/api/admin/activity':
        include __DIR__ . '/../api/admin/activity.php';
        break;
    case '/api/admin/stats':
        include __DIR__ . '/../api/admin/stats.php';
        break;

    default:
        http_response_code(404);
        include __DIR__ . '/../pages/404.php';
        break;
}
?>