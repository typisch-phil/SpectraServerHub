<?php
header('Content-Type: application/json');

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

// Nur GET-Requests akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $topup_id = $_GET['topup_id'] ?? null;
    
    if (!$topup_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Topup ID fehlt']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Topup-Details abrufen
    $topup = $db->fetchOne("
        SELECT * FROM balance_topups 
        WHERE id = ? AND user_id = ?
    ", [$topup_id, $_SESSION['user_id']]);
    
    if (!$topup) {
        http_response_code(404);
        echo json_encode(['error' => 'Aufladung nicht gefunden']);
        exit;
    }
    
    // Wenn noch pending und Mollie Payment ID vorhanden, Status bei Mollie prüfen
    if ($topup['status'] === 'pending' && $topup['mollie_payment_id']) {
        try {
            $balanceTopup = new BalanceTopup();
            $processed = $balanceTopup->processSuccessfulPayment($topup['mollie_payment_id']);
            
            if ($processed) {
                // Aktualisierte Daten abrufen
                $topup = $db->fetchOne("
                    SELECT * FROM balance_topups 
                    WHERE id = ? AND user_id = ?
                ", [$topup_id, $_SESSION['user_id']]);
            }
        } catch (Exception $e) {
            // Fehler beim Status-Check ignorieren, ursprüngliche Daten zurückgeben
            error_log('Payment status check error: ' . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'topup' => $topup
    ]);
    
} catch (Exception $e) {
    error_log('Check Payment Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Interner Server-Fehler']);
}
?>