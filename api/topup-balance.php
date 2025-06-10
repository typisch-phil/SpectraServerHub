<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/mollie.php';

// Input lesen
$input = json_decode(file_get_contents('php://input'), true);

// Authentifizierung prüfen (Session oder direkte User-ID)
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (isset($input['user_id']) && is_numeric($input['user_id'])) {
    // Alternative Authentifizierung über direkte User-ID (für API-Aufrufe)
    $userId = (int)$input['user_id'];
    
    // Zusätzliche Sicherheitsprüfung: User existiert in DB
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        $userId = null;
    }
}

if (!$userId) {
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
    // Amount aus Input extrahieren
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
    $result = $balanceTopup->startTopup($userId, $amount, $baseUrl);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Topup Balance Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>