<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/mollie.php';
require_once '../../includes/proxmox.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        exit;
    }
    
    $paymentId = $data['id'];
    
    // Get payment details from Mollie
    $mollie = new MolliePayment();
    $payment = $mollie->getPayment($paymentId);
    
    if (!$payment) {
        http_response_code(404);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Find order by payment ID
    $stmt = $db->prepare("SELECT * FROM orders WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        exit;
    }
    
    $newStatus = '';
    switch ($payment['status']) {
        case 'paid':
            $newStatus = 'paid';
            break;
        case 'failed':
        case 'canceled':
        case 'expired':
            $newStatus = 'failed';
            break;
        default:
            // Payment still pending or other status
            exit;
    }
    
    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $order['id']]);
    
    // If payment was successful, provision the service
    if ($newStatus === 'paid') {
        provisionService($order);
    }
    
    http_response_code(200);
    echo 'OK';
    
} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    http_response_code(500);
    exit;
}

function provisionService($order) {
    global $db;
    
    try {
        // Get service details
        $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$order['service_id']]);
        $service = $stmt->fetch();
        
        if (!$service) {
            throw new Exception('Service not found');
        }
        
        // Calculate expiration date based on billing period
        $expirationDate = calculateExpirationDate($order['billing_period']);
        
        // Generate server name if not provided
        $serverName = generateServerName($service['type'], $order['user_id']);
        
        // Create user service record
        $stmt = $db->prepare("
            INSERT INTO user_services 
            (user_id, service_id, server_name, status, expires_at, created_at) 
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([
            $order['user_id'],
            $order['service_id'],
            $serverName,
            $expirationDate
        ]);
        
        $userServiceId = $db->lastInsertId();
        
        // For VPS services, create VM in Proxmox
        if ($service['type'] === 'vps' || $service['type'] === 'gameserver') {
            $vmid = createProxmoxVM($service, $serverName);
            
            if ($vmid) {
                // Update user service with VM ID
                $stmt = $db->prepare("UPDATE user_services SET proxmox_vmid = ?, status = 'active' WHERE id = ?");
                $stmt->execute([$vmid, $userServiceId]);
            }
        } else {
            // For other services, just mark as active
            $stmt = $db->prepare("UPDATE user_services SET status = 'active' WHERE id = ?");
            $stmt->execute([$userServiceId]);
        }
        
        // Send confirmation email (TODO: Implement email service)
        
    } catch (Exception $e) {
        error_log('Service provisioning error: ' . $e->getMessage());
    }
}

function calculateExpirationDate($billingPeriod) {
    $interval = '';
    switch ($billingPeriod) {
        case 'monthly':
            $interval = '+1 month';
            break;
        case 'quarterly':
            $interval = '+3 months';
            break;
        case 'yearly':
            $interval = '+1 year';
            break;
        default:
            $interval = '+1 month';
    }
    
    return date('Y-m-d', strtotime($interval));
}

function generateServerName($serviceType, $userId) {
    $prefix = '';
    switch ($serviceType) {
        case 'vps':
            $prefix = 'vps';
            break;
        case 'gameserver':
            $prefix = 'game';
            break;
        case 'webhosting':
            $prefix = 'web';
            break;
        default:
            $prefix = 'srv';
    }
    
    return $prefix . '-' . $userId . '-' . time();
}

function createProxmoxVM($service, $serverName) {
    try {
        $proxmox = new ProxmoxAPI();
        
        if (!$proxmox->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        // Generate unique VM ID
        $vmid = 100 + rand(1, 99999);
        
        // Create VM with service specifications
        $result = $proxmox->createVM(
            $vmid,
            $serverName,
            $service['memory_gb'] * 1024, // Convert GB to MB
            $service['cpu_cores'],
            $service['storage_gb']
        );
        
        if ($result) {
            return $vmid;
        }
        
    } catch (Exception $e) {
        error_log('Proxmox VM creation error: ' . $e->getMessage());
    }
    
    return null;
}
?>