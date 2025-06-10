<?php
require_once 'includes/config.php';

echo "Proxmox VE Connection Test\n";
echo "==========================\n";

// Test multiple Proxmox configurations
$testConfigs = [
    [
        'name' => 'Local Demo',
        'host' => '192.168.1.100',
        'username' => 'root@pam',
        'password' => 'proxmox123',
        'node' => 'pve'
    ],
    [
        'name' => 'Alternative Demo',
        'host' => '10.0.0.100',
        'username' => 'admin@pve',
        'password' => 'admin123',
        'node' => 'node1'
    ]
];

function testProxmoxConnection($config) {
    echo "\nTesting: " . $config['name'] . "\n";
    echo "Host: " . $config['host'] . "\n";
    echo "User: " . $config['username'] . "\n";
    
    $authUrl = "https://{$config['host']}:8006/api2/json/access/ticket";
    echo "Auth URL: $authUrl\n";
    
    // Step 1: Test basic connectivity
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    $testResponse = curl_exec($ch);
    $testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $connectTime = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
    curl_close($ch);
    
    echo "Connectivity Test:\n";
    echo "  HTTP Code: $testHttpCode\n";
    echo "  Connect Time: {$connectTime}s\n";
    echo "  cURL Error: " . ($curlError ?: 'None') . "\n";
    
    if ($curlError) {
        echo "  Result: FAILED - Connection error\n";
        return false;
    }
    
    if ($testHttpCode === 401) {
        echo "  Result: SUCCESS - Server responding (401 expected for unauthenticated request)\n";
    } elseif ($testHttpCode === 200) {
        echo "  Result: SUCCESS - Server responding\n";
    } else {
        echo "  Result: UNKNOWN - Server returned HTTP $testHttpCode\n";
    }
    
    // Step 2: Test authentication
    echo "\nAuthentication Test:\n";
    $authData = [
        'username' => $config['username'],
        'password' => $config['password']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $authResponse = curl_exec($ch);
    $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $authCurlError = curl_error($ch);
    curl_close($ch);
    
    echo "  HTTP Code: $authHttpCode\n";
    echo "  cURL Error: " . ($authCurlError ?: 'None') . "\n";
    
    if ($authCurlError) {
        echo "  Result: FAILED - Authentication request error\n";
        return false;
    }
    
    if ($authHttpCode === 200) {
        $authData = json_decode($authResponse, true);
        if (isset($authData['data']['ticket'])) {
            echo "  Result: SUCCESS - Authentication successful\n";
            echo "  Ticket: " . substr($authData['data']['ticket'], 0, 20) . "...\n";
            return true;
        } else {
            echo "  Result: FAILED - No ticket in response\n";
            echo "  Response: " . substr($authResponse, 0, 200) . "\n";
        }
    } else {
        echo "  Result: FAILED - HTTP $authHttpCode\n";
        $errorData = json_decode($authResponse, true);
        if (isset($errorData['errors'])) {
            echo "  Error: " . json_encode($errorData['errors']) . "\n";
        } else {
            echo "  Response: " . substr($authResponse, 0, 200) . "\n";
        }
    }
    
    return false;
}

// Test all configurations
foreach ($testConfigs as $config) {
    $result = testProxmoxConnection($config);
    if ($result) {
        echo "\n✓ Found working Proxmox configuration: " . $config['name'] . "\n";
        
        // Save working config to database
        try {
            $stmt = $db->prepare("
                INSERT INTO integrations (name, config, status, updated_at) 
                VALUES (?, ?, 'configured', NOW()) 
                ON DUPLICATE KEY UPDATE 
                config = VALUES(config), 
                status = VALUES(status), 
                updated_at = VALUES(updated_at)
            ");
            $configJson = json_encode($config);
            $stmt->execute(['proxmox', $configJson]);
            echo "✓ Configuration saved to database\n";
        } catch (Exception $e) {
            echo "✗ Failed to save configuration: " . $e->getMessage() . "\n";
        }
        
        break;
    }
    echo "\n" . str_repeat("-", 50) . "\n";
}

echo "\nProxmox VE Connection Test Complete\n";
?>