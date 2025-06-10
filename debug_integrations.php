<?php
require_once 'includes/session.php';
require_once 'includes/config.php';

echo "Debug Integration API\n";
echo "====================\n";

// Check session
if (isset($_SESSION['user'])) {
    echo "User logged in: " . $_SESSION['user']['email'] . "\n";
    echo "User role: " . $_SESSION['user']['role'] . "\n";
} else {
    echo "No user session found\n";
}

// Check database connection
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM integrations");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Integrations table exists, rows: $count\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// Test Mollie configuration save
echo "\nTesting Mollie config save...\n";
try {
    $config = ['api_key' => 'test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM', 'webhook_url' => 'https://test.com/webhook'];
    $configJson = json_encode($config);
    
    $stmt = $db->prepare("
        INSERT INTO integrations (name, config, status, updated_at) 
        VALUES (?, ?, 'configured', NOW()) 
        ON DUPLICATE KEY UPDATE 
        config = VALUES(config), 
        status = VALUES(status), 
        updated_at = VALUES(updated_at)
    ");
    $result = $stmt->execute(['mollie', $configJson]);
    
    if ($result) {
        echo "Mollie config saved successfully\n";
        
        // Test retrieval
        $stmt = $db->prepare("SELECT config FROM integrations WHERE name = 'mollie'");
        $stmt->execute();
        $saved = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Retrieved config: " . $saved['config'] . "\n";
        
    } else {
        echo "Failed to save Mollie config\n";
    }
} catch (Exception $e) {
    echo "Mollie config error: " . $e->getMessage() . "\n";
}

// Test Mollie API connection
echo "\nTesting Mollie API...\n";
try {
    $apiKey = $_ENV['MOLLIE_API_KEY'] ?? 'test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/methods');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $methods = count($data['_embedded']['methods'] ?? []);
        echo "Mollie API working, $methods payment methods available\n";
    } else {
        echo "Mollie API failed\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
} catch (Exception $e) {
    echo "Mollie API error: " . $e->getMessage() . "\n";
}

echo "\nDebug complete.\n";
?>