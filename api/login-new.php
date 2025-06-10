<?php
/**
 * Neue Login API mit vollständiger Datenbankintegration
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception('E-Mail und Passwort sind erforderlich');
    }
    
    // Initialize auth system
    $auth = new AuthSystem();
    
    // Attempt login
    $result = $auth->login($input['email'], $input['password']);
    
    // Return response
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server-Fehler: ' . $e->getMessage()
    ]);
}
?>