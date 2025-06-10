<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Nicht angemeldet'
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Get fresh user data from database
    $stmt = $connection->prepare("SELECT id, email, first_name, last_name, role, balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Benutzer nicht gefunden'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'balance' => (float)$user['balance']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server-Fehler'
    ]);
}
?>