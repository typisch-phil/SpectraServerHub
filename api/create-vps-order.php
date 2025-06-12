<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Benutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$requiredFields = ['cpu_cores', 'ram', 'storage', 'operating_system', 'server_name', 'root_password'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

$db = Database::getInstance();

try {
    // Preisberechnung basierend auf Konfiguration
    $basePrice = 9.99;
    $totalPrice = $basePrice;
    
    // CPU-Aufpreis
    $cpuPrices = ['1' => 0, '2' => 10, '4' => 25, '8' => 50];
    $cpuCores = $input['cpu_cores'];
    $cpuPrice = $cpuPrices[$cpuCores] ?? 0;
    $totalPrice += $cpuPrice;
    
    // RAM-Aufpreis
    $ramPrices = ['2' => 0, '4' => 8, '8' => 20, '16' => 45];
    $ram = $input['ram'];
    $ramPrice = $ramPrices[$ram] ?? 0;
    $totalPrice += $ramPrice;
    
    // Storage-Aufpreis
    $storagePrices = ['25' => 0, '50' => 5, '100' => 12, '250' => 30];
    $storage = $input['storage'];
    $storagePrice = $storagePrices[$storage] ?? 0;
    $totalPrice += $storagePrice;
    
    // Windows-Lizenz
    $windowsPrice = 0;
    if (strpos($input['operating_system'], 'windows') !== false) {
        $windowsPrice = 15;
        $totalPrice += $windowsPrice;
    }
    
    // Zusätzliche Services
    $backupPrice = !empty($input['backup_service']) ? 5 : 0;
    $monitoringPrice = !empty($input['monitoring']) ? 3 : 0;
    $managedPrice = !empty($input['managed_support']) ? 15 : 0;
    
    $totalPrice += $backupPrice + $monitoringPrice + $managedPrice;
    
    // VPS-Konfiguration zusammenstellen
    $vpsConfig = [
        'cpu_cores' => (int)$cpuCores,
        'ram_gb' => (int)$ram,
        'storage_gb' => (int)$storage,
        'operating_system' => $input['operating_system'],
        'server_name' => trim($input['server_name']),
        'root_password' => $input['root_password'], // In Produktion hashen!
        'backup_service' => !empty($input['backup_service']),
        'monitoring' => !empty($input['monitoring']),
        'managed_support' => !empty($input['managed_support']),
        'base_price' => $basePrice,
        'cpu_price' => $cpuPrice,
        'ram_price' => $ramPrice,
        'storage_price' => $storagePrice,
        'windows_price' => $windowsPrice,
        'backup_price' => $backupPrice,
        'monitoring_price' => $monitoringPrice,
        'managed_price' => $managedPrice,
        'total_price' => $totalPrice
    ];
    
    // Validierung
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $input['server_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid server name. Only letters, numbers and hyphens allowed.']);
        exit;
    }
    
    if (strlen($input['root_password']) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters long.']);
        exit;
    }
    
    // Bestellung in Datenbank speichern
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, service_type_id, configuration, monthly_price, status, created_at) 
        VALUES (?, NULL, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        json_encode($vpsConfig),
        $totalPrice
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Service in services-Tabelle erstellen für Dashboard-Anzeige
    $serviceName = "VPS Custom ({$cpuCores} Cores, {$ram}GB RAM, {$storage}GB)";
    $stmt = $db->prepare("
        INSERT INTO services (user_id, name, service_type, status, monthly_price, configuration, created_at) 
        VALUES (?, ?, 'VPS', 'pending', ?, ?, NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $serviceName,
        $totalPrice,
        json_encode($vpsConfig)
    ]);
    
    // Benutzer-Balance überprüfen
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $response = [
        'success' => true,
        'order_id' => $orderId,
        'message' => 'VPS-Bestellung erfolgreich erstellt',
        'total_price' => $totalPrice,
        'config' => $vpsConfig
    ];
    
    // Wenn Guthaben vorhanden ist, sofort aktivieren
    if ($user && $user['balance'] >= $totalPrice) {
        // Balance abziehen
        $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$totalPrice, $_SESSION['user_id']]);
        
        // Bestellung aktivieren
        $stmt = $db->prepare("UPDATE orders SET status = 'active', activated_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Service aktivieren
        $stmt = $db->prepare("UPDATE services SET status = 'active' WHERE user_id = ? AND name = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id'], $serviceName]);
        
        $response['auto_activated'] = true;
        $response['message'] = 'VPS-Bestellung erfolgreich erstellt und aktiviert';
    } else {
        $response['payment_required'] = true;
        $response['message'] = 'VPS-Bestellung erstellt - Zahlung erforderlich';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Create VPS order error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>