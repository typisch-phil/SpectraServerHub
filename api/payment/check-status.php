<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payment ID fehlt']);
    exit;
}

try {
    $db = Database::getInstance();
    // Get payment from database
    $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Zahlung nicht gefunden']);
        exit;
    }
    
    // If payment is already processed, return status
    if ($payment['status'] === 'paid') {
        echo json_encode([
            'success' => true,
            'status' => 'paid',
            'message' => 'Zahlung bereits verarbeitet'
        ]);
        exit;
    }
    
    // Get Mollie API key
    $mollie_api_key = $_ENV['MOLLIE_API_KEY'] ?? null;
    
    if (!$mollie_api_key || !$payment['mollie_payment_id']) {
        echo json_encode([
            'success' => true,
            'status' => $payment['status'],
            'message' => 'Status kann nicht überprüft werden'
        ]);
        exit;
    }
    
    // Check status with Mollie
    $mollie_payment = callMollieAPI('payments/' . $payment['mollie_payment_id'], 'GET', null, $mollie_api_key);
    
    if (!$mollie_payment) {
        echo json_encode([
            'success' => true,
            'status' => $payment['status'],
            'message' => 'Mollie-Status nicht verfügbar'
        ]);
        exit;
    }
    
    $new_status = $mollie_payment['status'];
    
    // Update payment status if changed
    if ($new_status !== $payment['status']) {
        $stmt = $db->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $payment['id']]);
        
        // If payment is now paid, add balance to user account
        if ($new_status === 'paid' && $payment['status'] !== 'paid') {
            // Add balance to user
            $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$payment['amount'], $payment['user_id']]);
            
            // Log balance transaction
            $stmt = $db->prepare("
                INSERT INTO balance_transactions (user_id, amount, type, description, payment_id, created_at) 
                VALUES (?, ?, 'credit', ?, ?, NOW())
            ");
            $stmt->execute([
                $payment['user_id'], 
                $payment['amount'], 
                'Guthaben aufgeladen via ' . $payment['payment_method'],
                $payment['id']
            ]);
            
            echo json_encode([
                'success' => true,
                'status' => 'paid',
                'message' => 'Zahlung erfolgreich! Guthaben wurde gutgeschrieben.',
                'amount' => $payment['amount']
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'status' => $new_status,
        'message' => getStatusMessage($new_status)
    ]);
    
} catch (Exception $e) {
    error_log('Payment status check error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Überprüfen des Zahlungsstatus'
    ]);
}

function getStatusMessage($status) {
    $messages = [
        'open' => 'Zahlung wurde erstellt',
        'pending' => 'Zahlung wird verarbeitet',
        'paid' => 'Zahlung erfolgreich abgeschlossen',
        'canceled' => 'Zahlung wurde abgebrochen',
        'failed' => 'Zahlung fehlgeschlagen',
        'expired' => 'Zahlung ist abgelaufen'
    ];
    
    return $messages[$status] ?? 'Unbekannter Status';
}

function callMollieAPI($endpoint, $method = 'GET', $data = null, $api_key = null) {
    $url = 'https://api.mollie.com/v2/' . $endpoint;
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: SpectraHost/1.0'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Mollie API cURL error: ' . $error);
        return false;
    }
    
    if ($http_code >= 400) {
        error_log('Mollie API HTTP error: ' . $http_code . ' - ' . $response);
        return false;
    }
    
    return json_decode($response, true);
}
?>