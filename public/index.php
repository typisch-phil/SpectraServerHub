<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Route handling
switch ($path) {
    case '/':
        include '../pages/home.php';
        break;
    case '/login':
        include '../pages/login.php';
        break;
    case '/register':
        include '../pages/register.php';
        break;
    case '/dashboard':
        include '../pages/dashboard/index.php';
        break;
    case '/dashboard/services':
        include '../pages/dashboard/services.php';
        break;
    case '/dashboard/billing':
        include '../pages/dashboard/billing.php';
        break;
    case '/dashboard/support':
        include '../pages/dashboard/support.php';
        break;
    case '/admin':
        include '../pages/admin/dashboard.php';
        break;
    case '/admin/users':
        include '../pages/admin/users.php';
        break;
    case '/admin/services':
        include '../pages/admin/services.php';
        break;
    case '/admin/tickets':
        include '../pages/admin/tickets.php';
        break;
    case '/admin/invoices':
        include '../pages/admin/invoices.php';
        break;
    case '/admin/statistics':
        include '../pages/admin/statistics.php';
        break;
    case '/admin/integrations':
        include '../pages/admin/integrations.php';
        break;
    case '/order':
        include '../pages/order.php';
        break;
    case '/contact':
        include '../pages/contact.php';
        break;
    case '/impressum':
        include '../pages/impressum.php';
        break;
    case '/api/login':
        include '../api/login.php';
        break;
    case '/api/register':
        include '../api/register.php';
        break;
    case '/api/logout':
        include '../api/logout.php';
        break;
    case '/api/order':
        include '../api/order.php';
        break;
    case '/api/services':
        include '../api/services.php';
        break;
    case '/api/user/services':
        include '../api/user/services.php';
        break;
    case '/api/user/add-balance':
        include '../api/user/add-balance.php';
        break;
    case '/api/payment/webhook':
        include '../api/payment/webhook.php';
        break;
    case '/api/admin/activity':
        include '../api/admin/activity.php';
        break;
    case '/api/admin/stats':
        include '../api/admin/stats.php';
        break;

    default:
        http_response_code(404);
        include '../pages/404.php';
        break;
}
?>