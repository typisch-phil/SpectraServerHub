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
        include '../pages/dashboard.php';
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
    case '/api/payment/webhook':
        include '../api/payment/webhook.php';
        break;
    default:
        http_response_code(404);
        include '../pages/404.php';
        break;
}
?>