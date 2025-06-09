<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

try {
    // Get Mollie API key
    $stmt = $db->prepare("SELECT config_value FROM integration_configs WHERE integration_name = 'mollie' AND config_key = 'api_key'");
    $stmt->execute();
    $mollie_api_key = $stmt->fetchColumn();
    
    if (!$mollie_api_key) {
        echo json_encode(['success' => false, 'error' => 'Mollie nicht konfiguriert']);
        exit;
    }
    
    // Get available payment methods from Mollie
    $methods = callMollieAPI('methods?include=pricing', 'GET', null, $mollie_api_key);
    
    if (!$methods || !isset($methods['_embedded']['methods'])) {
        echo json_encode(['success' => false, 'error' => 'Zahlungsmethoden konnten nicht geladen werden']);
        exit;
    }
    
    $available_methods = [];
    
    foreach ($methods['_embedded']['methods'] as $method) {
        // Filter for supported methods and check if they're available
        if (in_array($method['id'], ['ideal', 'creditcard', 'banktransfer', 'paypal', 'sofort', 'giropay', 'eps', 'przelewy24']) && $method['status'] === 'activated') {
            $method_data = [
                'id' => $method['id'],
                'description' => $method['description'],
                'image' => $method['image']['size2x'] ?? $method['image']['size1x'] ?? null,
                'pricing' => null
            ];
            
            // Add pricing information if available
            if (isset($method['pricing'])) {
                foreach ($method['pricing'] as $pricing) {
                    if ($pricing['description'] === 'Netherlands' || $pricing['description'] === 'Germany' || $pricing['description'] === 'Europe') {
                        $method_data['pricing'] = [
                            'fixed' => $pricing['fixed']['value'] ?? '0.00',
                            'variable' => $pricing['variable'] ?? '0.00'
                        ];
                        break;
                    }
                }
            }
            
            $available_methods[] = $method_data;
        }
    }
    
    echo json_encode([
        'success' => true,
        'methods' => $available_methods
    ]);
    
} catch (Exception $e) {
    error_log('Mollie methods API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Fehler beim Laden der Zahlungsmethoden']);
}

function callMollieAPI($endpoint, $method = 'GET', $data = null, $api_key = null) {
    $url = 'https://api.mollie.com/v2/' . $endpoint;
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: SpectraHost/1.0'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Mollie API cURL error: ' . $error);
        return false;
    }
    
    if ($http_code >= 400) {
        error_log('Mollie API HTTP error: ' . $http_code . ' - ' . $response);
        return false;
    }
    
    return json_decode($response, true);
}
?>