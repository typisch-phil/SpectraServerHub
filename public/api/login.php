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
    // Support both JSON and form data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email und Passwort sind erforderlich');
    }
    
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    $database = Database::getInstance();
    $stmt = $database->prepare("SELECT id, email, password, first_name, last_name, role, balance FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($input['password'], $user['password'])) {
        throw new Exception('Ungültige Anmeldedaten');
    }
    
    // Start session and store user data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role'] ?? 'user',
        'balance' => $user['balance'] ?? 0.00
    ];
    
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