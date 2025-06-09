<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Admin access required']);
    exit;
}

$pdo = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all invoices/payments or specific invoice
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("
                    SELECT p.*, u.first_name, u.last_name, u.email, s.name as service_name
                    FROM payments p 
                    JOIN users u ON p.user_id = u.id 
                    LEFT JOIN services s ON p.service_id = s.id
                    WHERE p.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$invoice) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Invoice not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'invoice' => $invoice]);
            } else {
                // Get all invoices with optional filtering
                $status = $_GET['status'] ?? '';
                $method = $_GET['method'] ?? '';
                $startDate = $_GET['start_date'] ?? '';
                $endDate = $_GET['end_date'] ?? '';
                
                $query = "
                    SELECT p.*, u.first_name, u.last_name, u.email, s.name as service_name
                    FROM payments p 
                    JOIN users u ON p.user_id = u.id 
                    LEFT JOIN services s ON p.service_id = s.id
                    WHERE 1=1
                ";
                $params = [];
                
                if (!empty($status)) {
                    $query .= " AND p.status = ?";
                    $params[] = $status;
                }
                
                if (!empty($method)) {
                    $query .= " AND p.payment_method = ?";
                    $params[] = $method;
                }
                
                if (!empty($startDate)) {
                    $query .= " AND p.created_at >= ?";
                    $params[] = $startDate . ' 00:00:00';
                }
                
                if (!empty($endDate)) {
                    $query .= " AND p.created_at <= ?";
                    $params[] = $endDate . ' 23:59:59';
                }
                
                $query .= " ORDER BY p.created_at DESC";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate statistics
                $totalAmount = array_sum(array_column($invoices, 'amount'));
                $paidAmount = array_sum(array_column(array_filter($invoices, function($i) { 
                    return $i['status'] === 'paid'; 
                }), 'amount'));
                $pendingAmount = array_sum(array_column(array_filter($invoices, function($i) { 
                    return $i['status'] === 'pending'; 
                }), 'amount'));
                
                echo json_encode([
                    'success' => true, 
                    'invoices' => $invoices,
                    'statistics' => [
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'pending_amount' => $pendingAmount,
                        'total_count' => count($invoices)
                    ]
                ]);
            }
            break;
            
        case 'POST':
            // Create new invoice/payment
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['user_id']) || !isset($input['amount'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID and amount are required']);
                exit;
            }
            
            // Validate amount
            if (!is_numeric($input['amount']) || floatval($input['amount']) <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid amount']);
                exit;
            }
            
            // Validate user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$input['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            
            // Create payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, service_id, amount, currency, payment_method, payment_id, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['user_id'],
                $input['service_id'] ?? null,
                floatval($input['amount']),
                $input['currency'] ?? 'EUR',
                $input['payment_method'] ?? 'manual',
                $input['payment_id'] ?? 'MANUAL_' . uniqid(),
                $input['status'] ?? 'pending'
            ]);
            
            $invoiceId = $pdo->lastInsertId();
            
            // Get created invoice
            $stmt = $pdo->prepare("
                SELECT p.*, u.first_name, u.last_name, u.email, s.name as service_name
                FROM payments p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN services s ON p.service_id = s.id
                WHERE p.id = ?
            ");
            $stmt->execute([$invoiceId]);
            $newInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'invoice' => $newInvoice, 'message' => 'Invoice created successfully']);
            break;
            
        case 'PUT':
            // Update invoice/payment
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invoice ID is required']);
                exit;
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            if (isset($input['amount']) && is_numeric($input['amount']) && floatval($input['amount']) > 0) {
                $updateFields[] = "amount = ?";
                $params[] = floatval($input['amount']);
            }
            
            if (isset($input['currency'])) {
                $updateFields[] = "currency = ?";
                $params[] = $input['currency'];
            }
            
            if (isset($input['payment_method'])) {
                $updateFields[] = "payment_method = ?";
                $params[] = $input['payment_method'];
            }
            
            if (isset($input['status']) && in_array($input['status'], ['pending', 'paid', 'failed', 'cancelled'])) {
                $updateFields[] = "status = ?";
                $params[] = $input['status'];
            }
            
            if (isset($input['payment_id'])) {
                $updateFields[] = "payment_id = ?";
                $params[] = $input['payment_id'];
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $input['id'];
            
            $query = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Invoice not found']);
                exit;
            }
            
            // Get updated invoice
            $stmt = $pdo->prepare("
                SELECT p.*, u.first_name, u.last_name, u.email, s.name as service_name
                FROM payments p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN services s ON p.service_id = s.id
                WHERE p.id = ?
            ");
            $stmt->execute([$input['id']]);
            $updatedInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'invoice' => $updatedInvoice, 'message' => 'Invoice updated successfully']);
            break;
            
        case 'DELETE':
            // Cancel/void invoice
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invoice ID is required']);
                exit;
            }
            
            // Check if invoice can be cancelled
            $stmt = $pdo->prepare("SELECT status FROM payments WHERE id = ?");
            $stmt->execute([$input['id']]);
            $invoice = $stmt->fetch();
            
            if (!$invoice) {
                http_response_code(404);
                echo json_encode(['error' => 'Invoice not found']);
                exit;
            }
            
            if ($invoice['status'] === 'paid') {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot cancel paid invoice']);
                exit;
            }
            
            // Update status to cancelled
            $stmt = $pdo->prepare("UPDATE payments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Invoice cancelled successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in admin invoices API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>