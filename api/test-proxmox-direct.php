<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // Get stored Proxmox configuration
    $stmt = $db->prepare("SELECT config FROM integrations WHERE name = 'proxmox'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !$result['config']) {
        echo json_encode([
            'success' => false,
            'message' => 'Proxmox-Konfiguration nicht gefunden'
        ]);
        exit;
    }
    
    $config = json_decode($result['config'], true);
    $host = $config['host'];
    $username = $config['username'];
    $password = $config['password'];
    $node = $config['node'];
    
    // Test Proxmox connection
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
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo json_encode([
            'success' => false,
            'message' => "Proxmox-Verbindungsfehler: $curlError"
        ]);
        exit;
    }
    
    if ($authHttpCode !== 200) {
        echo json_encode([
            'success' => false,
            'message' => "Proxmox-Authentifizierung fehlgeschlagen: HTTP $authHttpCode"
        ]);
        exit;
    }
    
    $authDataResponse = json_decode($authResponse, true);
    if (!$authDataResponse || !isset($authDataResponse['data']['ticket'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Proxmox-Authentifizierung ungültig'
        ]);
        exit;
    }
    
    $ticket = $authDataResponse['data']['ticket'];
    $csrfToken = $authDataResponse['data']['CSRFPreventionToken'];
    
    // Get version info
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
    
    if ($versionHttpCode !== 200) {
        echo json_encode([
            'success' => false,
            'message' => 'Proxmox-Version API fehlgeschlagen'
        ]);
        exit;
    }
    
    $versionData = json_decode($versionResponse, true);
    $version = $versionData['data']['version'];
    $release = $versionData['data']['release'];
    
    // Get nodes info
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
    
    $nodeCount = 0;
    if ($nodesHttpCode === 200) {
        $nodesData = json_decode($nodesResponse, true);
        $nodeCount = count($nodesData['data'] ?? []);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Proxmox-Verbindung erfolgreich',
        'details' => [
            'version' => $version,
            'release' => $release,
            'nodes' => $nodeCount,
            'host' => $host,
            'node' => $node,
            'ssl_verified' => false,
            'demo_mode' => false,
            'response_time' => round($connectTime, 2) . 's'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Proxmox-Verbindungsfehler: ' . $e->getMessage()
    ]);
}
?>