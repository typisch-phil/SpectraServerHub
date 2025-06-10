<?php
// Mollie Payment API für SpectraHost
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Datenbankverbindung
try {
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $pdo = new PDO($dsn, "s9281_spectrahost", getenv('MYSQL_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create_payment':
            createMolliePayment($pdo, $user_id, $input);
            break;
            
        case 'check_payment':
            checkMolliePayment($pdo, $input['payment_id'] ?? '');
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Zahlungshistorie abrufen
    getPaymentHistory($pdo, $user_id);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

function createMolliePayment($pdo, $user_id, $input) {
    $amount = floatval($input['amount'] ?? 0);
    $description = $input['description'] ?? 'SpectraHost Guthaben aufladen';
    
    if ($amount < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Minimum amount is 1.00€']);
        return;
    }
    
    if ($amount > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Maximum amount is 1000.00€']);
        return;
    }
    
    $mollie_api_key = getenv('MOLLIE_API_KEY');
    if (!$mollie_api_key) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Payment system not configured']);
        return;
    }
    
    // Zahlung in Datenbank speichern
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, amount, description, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $amount, $description]);
        $payment_id = $pdo->lastInsertId();
        
        // Mollie Payment erstellen
        $payment_data = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($amount, 2, '.', '')
            ],
            'description' => $description,
            'redirectUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard?payment=success',
            'webhookUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/payments.php?webhook=mollie',
            'metadata' => [
                'user_id' => $user_id,
                'payment_id' => $payment_id
            ]
        ];
        
        $mollie_response = callMollieAPI('payments', 'POST', $payment_data, $mollie_api_key);
        
        if ($mollie_response && isset($mollie_response['id'])) {
            // Mollie Payment ID in Datenbank speichern
            $stmt = $pdo->prepare("UPDATE payments SET mollie_id = ? WHERE id = ?");
            $stmt->execute([$mollie_response['id'], $payment_id]);
            
            echo json_encode([
                'success' => true,
                'payment_id' => $payment_id,
                'mollie_id' => $mollie_response['id'],
                'checkout_url' => $mollie_response['_links']['checkout']['href']
            ]);
        } else {
            // Fehler bei Mollie
            $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
            $stmt->execute([$payment_id]);
            
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Payment creation failed']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function checkMolliePayment($pdo, $payment_id) {
    if (!$payment_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Payment ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $_SESSION['user_id']]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Payment not found']);
            return;
        }
        
        if ($payment['mollie_id'] && $payment['status'] === 'pending') {
            $mollie_api_key = getenv('MOLLIE_API_KEY');
            if ($mollie_api_key) {
                $mollie_payment = callMollieAPI('payments/' . $payment['mollie_id'], 'GET', null, $mollie_api_key);
                
                if ($mollie_payment && $mollie_payment['status'] === 'paid') {
                    // Zahlung als bezahlt markieren und Guthaben hinzufügen
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
                    $stmt->execute([$payment_id]);
                    
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$payment['amount'], $payment['user_id']]);
                    
                    $pdo->commit();
                    
                    echo json_encode(['success' => true, 'status' => 'completed']);
                    return;
                }
            }
        }
        
        echo json_encode(['success' => true, 'status' => $payment['status']]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getPaymentHistory($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, amount, description, status, created_at 
            FROM payments 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $payments]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function callMollieAPI($endpoint, $method, $data, $api_key) {
    $url = 'https://api.mollie.com/v2/' . $endpoint;
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    }
    
    error_log("Mollie API Error: HTTP $http_code - $response");
    return false;
}

// Webhook-Handler für Mollie
if (isset($_GET['webhook']) && $_GET['webhook'] === 'mollie') {
    $input = file_get_contents('php://input');
    $webhook_data = json_decode($input, true);
    
    if ($webhook_data && isset($webhook_data['id'])) {
        $mollie_api_key = getenv('MOLLIE_API_KEY');
        if ($mollie_api_key) {
            $mollie_payment = callMollieAPI('payments/' . $webhook_data['id'], 'GET', null, $mollie_api_key);
            
            if ($mollie_payment && $mollie_payment['status'] === 'paid') {
                $metadata = $mollie_payment['metadata'] ?? [];
                $payment_id = $metadata['payment_id'] ?? null;
                $user_id = $metadata['user_id'] ?? null;
                
                if ($payment_id && $user_id) {
                    try {
                        $pdo->beginTransaction();
                        
                        $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ? AND mollie_id = ?");
                        $stmt->execute([$payment_id, $webhook_data['id']]);
                        
                        $stmt = $pdo->prepare("SELECT amount FROM payments WHERE id = ?");
                        $stmt->execute([$payment_id]);
                        $payment = $stmt->fetch();
                        
                        if ($payment) {
                            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                            $stmt->execute([$payment['amount'], $user_id]);
                        }
                        
                        $pdo->commit();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        error_log("Webhook processing error: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    http_response_code(200);
    echo 'OK';
    exit;
}
?>