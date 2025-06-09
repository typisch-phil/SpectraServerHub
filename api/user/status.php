<?php
session_start();
header('Content-Type: application/json');

try {
    $isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user']);
    
    if ($isLoggedIn) {
        echo json_encode([
            'isLoggedIn' => true,
            'user' => [
                'id' => $_SESSION['user']['id'],
                'email' => $_SESSION['user']['email'],
                'role' => $_SESSION['user']['role'] ?? 'user',
                'name' => ($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')
            ]
        ]);
    } else {
        echo json_encode([
            'isLoggedIn' => false,
            'user' => null
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'isLoggedIn' => false,
        'user' => null
    ]);
}
?>