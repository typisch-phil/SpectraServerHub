<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role'] ?? 'user'
            ]
        ]);
    } else {
        echo json_encode([
            'authenticated' => false
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'authenticated' => false,
        'error' => $e->getMessage()
    ]);
}
?>