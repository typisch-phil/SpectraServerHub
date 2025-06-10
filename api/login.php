<?php
// Plesk-kompatible Login API
require_once __DIR__ . '/../includes/plesk-config.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Clean any output buffer to prevent JSON corruption
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    $connection = $database->getConnection();
    $stmt = $connection->prepare("SELECT id, email, password, first_name, last_name, role, balance FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    

    
    if (!$user || !password_verify($input['password'], $user['password'])) {
        throw new Exception('Ungültige Anmeldedaten');
    }
    
    // Start session and store user data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role'] ?? 'user',
        'balance' => $user['balance'] ?? 0.00
    ];
    
    $response = [
        'success' => true,
        'message' => 'Anmeldung erfolgreich',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name']
        ]
    ];
    
    $jsonResponse = json_encode($response);
    
    // Ensure valid JSON before output
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo '{"success":false,"error":"JSON encoding error"}';
        exit;
    }
    
    echo $jsonResponse;
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    $jsonError = json_encode($errorResponse);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo '{"success":false,"error":"Server error"}';
        exit;
    }
    
    echo $jsonError;
    exit;
}
?>