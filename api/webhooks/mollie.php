<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: application/json');

// Log webhook for debugging
$input = file_get_contents('php://input');
error_log("Mollie Webhook received: " . $input);

try {
    $db = Database::getInstance();
    
    // Verify webhook request
    $body = json_decode($input, true);
    
    if (!$body || !isset($body['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook payload']);
        exit;
    }
    
    // Get payment ID from webhook
    $paymentId = $body['id'];
    
    // Initialize Mollie API
    $apiKey = $_ENV['MOLLIE_API_KEY'];
    if (!$apiKey) {
        throw new Exception('Mollie API key not configured');
    }
    
    // Fetch payment details from Mollie
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.mollie.com/v2/payments/{$paymentId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to fetch payment from Mollie API');
    }
    
    $payment = json_decode($response, true);
    
    if (!$payment) {
        throw new Exception('Invalid payment response from Mollie');
    }
    
    // Update payment status in database
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = ?, 
            mollie_payment_id = ?,
            updated_at = NOW()
        WHERE mollie_payment_id = ? OR reference = ?
    ");
    
    $mollieStatus = $payment['status'];
    $reference = $payment['metadata']['order_id'] ?? null;
    
    $stmt->execute([
        $mollieStatus,
        $paymentId,
        $paymentId,
        $reference
    ]);
    
    // Handle specific payment statuses
    switch ($mollieStatus) {
        case 'paid':
            // Payment successful - add balance or activate service
            if ($reference) {
                $stmt = $db->prepare("
                    SELECT * FROM payments 
                    WHERE mollie_payment_id = ? OR reference = ?
                ");
                $stmt->execute([$paymentId, $reference]);
                $dbPayment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dbPayment && $dbPayment['type'] === 'balance_topup') {
                    // Add balance to user account
                    $amount = floatval($payment['amount']['value']);
                    $userId = $dbPayment['user_id'];
                    
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET balance = balance + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$amount, $userId]);
                    
                    error_log("Added balance {$amount} to user {$userId}");
                }
            }
            break;
            
        case 'failed':
        case 'canceled':
        case 'expired':
            // Payment failed - log for admin review
            error_log("Payment {$paymentId} failed with status: {$mollieStatus}");
            break;
    }
    
    // Log webhook processing
    $stmt = $db->prepare("
        INSERT INTO webhook_logs (type, payment_id, status, data, created_at)
        VALUES ('mollie', ?, ?, ?, NOW())
    ");
    $stmt->execute([$paymentId, $mollieStatus, $input]);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'status' => $mollieStatus]);
    
} catch (Exception $e) {
    error_log("Mollie webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>