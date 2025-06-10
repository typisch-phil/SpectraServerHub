<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email']) || !isset($input['password']) || 
        !isset($input['firstName']) || !isset($input['lastName'])) {
        throw new Exception('Alle Felder sind erforderlich');
    }
    
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        throw new Exception('Ungültiger CSRF-Token');
    }
    
    // Validate input
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    if (strlen($input['password']) < 8) {
        throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
    }
    
    $firstName = trim($input['firstName']);
    $lastName = trim($input['lastName']);
    
    if (empty($firstName) || empty($lastName)) {
        throw new Exception('Vor- und Nachname sind erforderlich');
    }
    
    $userId = $auth->register($email, $input['password'], $firstName, $lastName);
    
    // Auto-login after registration
    $user = $auth->login($email, $input['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registrierung erfolgreich',
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