<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/mollie.php';

// Nur POST-Requests akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Log für Debugging
error_log('Mollie Webhook aufgerufen: ' . file_get_contents('php://input'));

try {
    // Payment ID aus POST-Daten extrahieren
    $input = file_get_contents('php://input');
    parse_str($input, $data);
    
    $paymentId = $data['id'] ?? null;
    
    if (!$paymentId) {
        http_response_code(400);
        exit('Payment ID fehlt');
    }
    
    // BalanceTopup-Instanz erstellen und Zahlung verarbeiten
    $balanceTopup = new BalanceTopup();
    $success = $balanceTopup->processSuccessfulPayment($paymentId);
    
    if ($success) {
        error_log("Mollie Webhook: Zahlung {$paymentId} erfolgreich verarbeitet");
        http_response_code(200);
        echo 'OK';
    } else {
        error_log("Mollie Webhook: Zahlung {$paymentId} nicht gefunden oder bereits verarbeitet");
        http_response_code(200);
        echo 'OK'; // Trotzdem OK zurückgeben um Retry-Loop zu vermeiden
    }
    
} catch (Exception $e) {
    error_log('Mollie Webhook Fehler: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal Server Error';
}
?>