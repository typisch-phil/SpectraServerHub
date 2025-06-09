<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/mollie.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Check authentication
    if (!$auth->isLoggedIn()) {
        throw new Exception('Anmeldung erforderlich');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['serviceId']) || !isset($input['billingPeriod'])) {
        throw new Exception('Service-ID und Abrechnungszeitraum sind erforderlich');
    }
    
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        throw new Exception('Ungültiger CSRF-Token');
    }
    
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    $serviceId = (int)$input['serviceId'];
    $billingPeriod = $input['billingPeriod'];
    
    // Validate billing period
    if (!in_array($billingPeriod, ['monthly', 'quarterly', 'yearly'])) {
        throw new Exception('Ungültiger Abrechnungszeitraum');
    }
    
    // Get service details
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active'");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        throw new Exception('Service nicht gefunden');
    }
    
    // Calculate total amount based on billing period
    $multiplier = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12][$billingPeriod];
    $discount = ['monthly' => 0, 'quarterly' => 0.05, 'yearly' => 0.15][$billingPeriod]; // 5% quarterly, 15% yearly discount
    $totalAmount = $service['price'] * $multiplier * (1 - $discount);
    
    // Create order
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, service_id, total_amount, billing_period, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $serviceId, $totalAmount, $billingPeriod]);
    $orderId = $db->lastInsertId();
    
    // Create Mollie payment
    if (MOLLIE_API_KEY) {
        $mollie = new MolliePayment();
        
        $description = "SpectraHost - {$service['name']} ({$billingPeriod})";
        $redirectUrl = SITE_URL . "/dashboard?payment=success&order=" . $orderId;
        $webhookUrl = SITE_URL . "/api/payment/webhook";
        
        $payment = $mollie->createPayment(
            $totalAmount,
            $description,
            $redirectUrl,
            $webhookUrl,
            ['orderId' => $orderId, 'userId' => $userId]
        );
        
        // Update order with payment ID
        $stmt = $db->prepare("UPDATE orders SET payment_id = ? WHERE id = ?");
        $stmt->execute([$payment['id'], $orderId]);
        
        echo json_encode([
            'success' => true,
            'orderId' => $orderId,
            'paymentUrl' => $payment['_links']['checkout']['href'],
            'amount' => $totalAmount
        ]);
    } else {
        // No payment processor configured - mark as paid for testing
        $stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        echo json_encode([
            'success' => true,
            'orderId' => $orderId,
            'message' => 'Bestellung erfolgreich erstellt (Test-Modus)',
            'amount' => $totalAmount
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>