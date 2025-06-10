<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

try {
    $user_id = $_SESSION['user']['id'];
    
    // Get current balance from database
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn();
    
    if ($current_balance === false) {
        throw new Exception('User not found');
    }
    
    // Update session balance
    $_SESSION['user']['balance'] = $current_balance;
    
    echo json_encode([
        'success' => true,
        'balance' => number_format((float)$current_balance, 2)
    ]);
    
} catch (Exception $e) {
    error_log('Balance API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden des Guthabens'
    ]);
}
?>