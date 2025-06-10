<?php
require_once 'includes/session.php';
require_once 'includes/config.php';

// Direct Proxmox API test with production credentials
function testDirectProxmoxConnection() {
    $host = $_ENV['PROXMOX_HOST'];
    $username = $_ENV['PROXMOX_USERNAME'];
    $password = $_ENV['PROXMOX_PASSWORD'];
    $node = $_ENV['PROXMOX_NODE'];
    
    echo "Testing Proxmox Connection\n";
    echo "Host: $host\n";
    echo "Username: $username\n";
    echo "Node: $node\n";
    echo "================================\n";
    
    // Step 1: Authentication
    $authData = [
        'username' => $username,
        'password' => $password
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://{$host}:8006/api2/json/access/ticket");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $authResponse = curl_exec($ch);
    $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $connectTime = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
    curl_close($ch);
    
    echo "Auth HTTP Code: $authHttpCode\n";
    echo "Connect Time: {$connectTime}s\n";
    
    if ($authHttpCode === 200) {
        $authData = json_decode($authResponse, true);
        $ticket = $authData['data']['ticket'];
        $csrfToken = $authData['data']['CSRFPreventionToken'];
        
        echo "✓ Authentication successful\n";
        echo "Ticket: " . substr($ticket, 0, 50) . "...\n";
        
        // Step 2: Get version info
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$host}:8006/api2/json/version");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Cookie: PVEAuthCookie={$ticket}",
            "CSRFPreventionToken: {$csrfToken}"
        ]);
        
        $versionResponse = curl_exec($ch);
        $versionHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($versionHttpCode === 200) {
            $versionData = json_decode($versionResponse, true);
            $version = $versionData['data']['version'];
            $release = $versionData['data']['release'];
            
            echo "✓ Version API working\n";
            echo "Version: $version\n";
            echo "Release: $release\n";
            
            // Step 3: Get nodes
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://{$host}:8006/api2/json/nodes");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Cookie: PVEAuthCookie={$ticket}",
                "CSRFPreventionToken: {$csrfToken}"
            ]);
            
            $nodesResponse = curl_exec($ch);
            $nodesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($nodesHttpCode === 200) {
                $nodesData = json_decode($nodesResponse, true);
                $nodeCount = count($nodesData['data'] ?? []);
                
                echo "✓ Nodes API working\n";
                echo "Available nodes: $nodeCount\n";
                
                foreach ($nodesData['data'] as $nodeInfo) {
                    echo "  - " . $nodeInfo['node'] . " (Status: " . $nodeInfo['status'] . ")\n";
                }
                
                // Update database with working configuration
                global $db;
                $config = [
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'node' => $node,
                    'ssl_verify' => false,
                    'demo_mode' => false,
                    'tested' => true,
                    'last_test' => date('Y-m-d H:i:s')
                ];
                
                $stmt = $db->prepare("
                    INSERT INTO integrations (name, config, status, updated_at) 
                    VALUES (?, ?, 'active', NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    config = VALUES(config), 
                    status = VALUES(status), 
                    updated_at = VALUES(updated_at)
                ");
                $stmt->execute(['proxmox', json_encode($config)]);
                
                echo "✓ Configuration saved to database\n";
                return true;
            } else {
                echo "✗ Nodes API failed: HTTP $nodesHttpCode\n";
            }
        } else {
            echo "✗ Version API failed: HTTP $versionHttpCode\n";
        }
    } else {
        echo "✗ Authentication failed: HTTP $authHttpCode\n";
        echo "Response: " . substr($authResponse, 0, 200) . "\n";
    }
    
    return false;
}

// Run the test
$result = testDirectProxmoxConnection();
echo "\n" . ($result ? "SUCCESS: Proxmox integration working" : "FAILED: Proxmox integration not working") . "\n";
?>