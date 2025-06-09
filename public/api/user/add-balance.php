<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/mollie.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

$user = $_SESSION['user'];
$database = Database::getInstance();

try {
    $amount = (float)$_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    if ($amount < 5 || $amount > 1000) {
        throw new Exception('Betrag muss zwischen 5,00 € und 1.000,00 € liegen');
    }
    
    // Create Mollie payment
    global $mollie;
    
    $payment = $mollie->createPayment(
        $amount,
        'Guthaben aufladen - SpectraHost',
        SITE_URL . '/dashboard?payment=success',
        SITE_URL . '/api/payment/webhook'
    );
    
    if ($payment) {
        // Store payment in database
        $stmt = $database->prepare("
            INSERT INTO orders (user_id, service_id, total_amount, status, payment_id, payment_method, notes, created_at)
            VALUES (?, NULL, ?, 'pending', ?, ?, 'Guthaben aufladen', NOW())
        ");
        $stmt->execute([$user['id'], $amount, $payment['id'], $payment_method]);
        
        echo json_encode([
            'success' => true,
            'paymentUrl' => $payment['_links']['checkout']['href']
        ]);
    } else {
        throw new Exception('Fehler beim Erstellen der Zahlung');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>