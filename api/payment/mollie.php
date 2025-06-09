<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

$user = getCurrentUser();
$user_id = $_SESSION['user_id'];

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create_payment':
        createMolliePayment();
        break;
    case 'webhook':
        handleWebhook();
        break;
    case 'return':
        handleReturn();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ungültige Aktion']);
}

function createMolliePayment() {
    global $db, $user_id;
    
    // Validate input
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'ideal';
    
    if ($amount < 10 || $amount > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Betrag muss zwischen €10 und €1000 liegen']);
        return;
    }
    
    // Get Mollie API key from environment
    $mollie_api_key = $_ENV['MOLLIE_API_KEY'] ?? null;
    
    if (!$mollie_api_key) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Mollie API key not configured']);
        return;
    }
    
    try {
        // Create payment record in database first
        $stmt = $db->prepare("
            INSERT INTO payments (user_id, amount, payment_method, status, type, currency, created_at) 
            VALUES (?, ?, ?, 'pending', 'balance_topup', 'EUR', NOW())
        ");
        $stmt->execute([$user_id, $amount, $payment_method]);
        $payment_id = $db->lastInsertId();
        
        // Create Mollie payment
        $description = "Guthaben aufladen - €" . number_format($amount, 2);
        $mollie_data = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($amount, 2)
            ],
            'description' => $description,
            'redirectUrl' => BASE_URL . '/api/payment/mollie.php?action=return&payment_id=' . $payment_id,
            'webhookUrl' => BASE_URL . '/api/payment/mollie.php?action=webhook',
            'metadata' => [
                'payment_id' => $payment_id,
                'user_id' => $user_id,
                'type' => 'balance_topup'
            ]
        ];
        
        if ($payment_method !== 'all') {
            $mollie_data['method'] = $payment_method;
        }
        
        $mollie_response = callMollieAPI('payments', 'POST', $mollie_data, $mollie_api_key);
        
        if (!$mollie_response || !isset($mollie_response['id'])) {
            throw new Exception('Mollie payment creation failed');
        }
        
        // Update payment with Mollie payment ID
        $stmt = $db->prepare("UPDATE payments SET mollie_payment_id = ? WHERE id = ?");
        $stmt->execute([$mollie_response['id'], $payment_id]);
        
        echo json_encode([
            'success' => true,
            'payment_url' => $mollie_response['_links']['checkout']['href'],
            'payment_id' => $payment_id,
            'mollie_id' => $mollie_response['id']
        ]);
        
    } catch (Exception $e) {
        error_log('Mollie payment creation error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Zahlung konnte nicht erstellt werden']);
    }
}

function handleWebhook() {
    global $db;
    
    $input = file_get_contents('php://input');
    $webhook_data = json_decode($input, true);
    
    if (!$webhook_data || !isset($webhook_data['id'])) {
        http_response_code(400);
        exit;
    }
    
    $mollie_payment_id = $webhook_data['id'];
    
    // Get payment from database
    $stmt = $db->prepare("SELECT * FROM payments WHERE mollie_payment_id = ?");
    $stmt->execute([$mollie_payment_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        http_response_code(404);
        exit;
    }
    
    // Get Mollie API key from environment
    $mollie_api_key = $_ENV['MOLLIE_API_KEY'] ?? null;
    
    if (!$mollie_api_key) {
        http_response_code(500);
        exit;
    }
    
    // Get payment status from Mollie
    $mollie_payment = callMollieAPI('payments/' . $mollie_payment_id, 'GET', null, $mollie_api_key);
    
    if (!$mollie_payment) {
        http_response_code(500);
        exit;
    }
    
    $new_status = $mollie_payment['status'];
    
    // Update payment status
    $stmt = $db->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $payment['id']]);
    
    // If payment is paid, add balance to user account
    if ($new_status === 'paid' && $payment['status'] !== 'paid') {
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
    }
    
    http_response_code(200);
    echo 'OK';
}

function handleReturn() {
    global $db;
    
    $payment_id = $_GET['payment_id'] ?? null;
    
    if (!$payment_id) {
        header('Location: /dashboard/billing?error=invalid_payment');
        exit;
    }
    
    // Get payment
    $stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        header('Location: /dashboard/billing?error=payment_not_found');
        exit;
    }
    
    if ($payment['status'] === 'paid') {
        header('Location: /dashboard/billing?success=payment_completed');
    } else {
        header('Location: /dashboard/billing?error=payment_failed');
    }
    exit;
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