<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $isLoggedIn = isLoggedIn();
    $user = null;
    
    if ($isLoggedIn) {
        $user = getCurrentUser();
    }
    
    echo json_encode([
        'isLoggedIn' => $isLoggedIn,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'isLoggedIn' => false,
        'user' => null,
        'error' => $e->getMessage()
    ]);
}
?>