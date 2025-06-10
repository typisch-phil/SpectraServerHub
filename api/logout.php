<?php
require_once __DIR__ . '/../includes/config.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Clear all session data
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // For GET requests (direct browser access), redirect to home
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: /');
        exit;
    }
    
    // For POST requests (AJAX), return JSON
    echo json_encode([
        'success' => true,
        'message' => 'Erfolgreich abgemeldet'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>