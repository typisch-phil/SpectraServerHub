<?php
/**
 * API für aktuellen Benutzerstatus
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth-system.php';

// Clean output buffer
if (ob_get_level()) {
    ob_clean();
}

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Initialize auth system
    $auth = new AuthSystem();
    
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'isLoggedIn' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['role'],
                    'balance' => floatval($user['balance'])
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'isLoggedIn' => false,
                'message' => 'Session abgelaufen'
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'isLoggedIn' => false,
            'message' => 'Nicht angemeldet'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server-Fehler: ' . $e->getMessage()
    ]);
}
?>