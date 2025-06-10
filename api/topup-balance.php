<?php
header('Content-Type: application/json');

// Session-Konfiguration für konsistente Handhabung
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Development
ini_set('session.cookie_samesite', 'Lax');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/mollie.php';

// Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

// Nur POST-Requests akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Input validieren
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = floatval($input['amount'] ?? 0);
    
    if ($amount < 5 || $amount > 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Betrag muss zwischen €5 und €1000 liegen']);
        exit;
    }
    
    // Base URL für Redirects bestimmen
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    
    // Guthaben-Aufladung starten
    $balanceTopup = new BalanceTopup();
    $result = $balanceTopup->startTopup($_SESSION['user_id'], $amount, $baseUrl);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Topup Balance Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>