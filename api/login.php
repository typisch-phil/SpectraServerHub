<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email und Passwort sind erforderlich');
    }
    
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        throw new Exception('Ungültiger CSRF-Token');
    }
    
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    $user = $auth->login($email, $input['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Anmeldung erfolgreich',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>