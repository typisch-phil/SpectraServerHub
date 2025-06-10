<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $apiKey = $_ENV['MOLLIE_API_KEY'];
    
    if (!$apiKey) {
        echo json_encode([
            'success' => false,
            'message' => 'Mollie API-Schlüssel nicht konfiguriert'
        ]);
        exit;
    }
    
    // Test Mollie API connection
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.mollie.com/v2/profile",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($curl);
    $responseTime = round((microtime(true) - $startTime), 1) . 's';
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200 && $response) {
        $profile = json_decode($response, true);
        
        // Get payment methods
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.mollie.com/v2/methods",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$apiKey}",
                "Content-Type: application/json"
            ]
        ]);
        
        $methodsResponse = curl_exec($curl);
        $methodsCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $methods = [];
        if ($methodsCode === 200 && $methodsResponse) {
            $methodsData = json_decode($methodsResponse, true);
            if (isset($methodsData['_embedded']['methods'])) {
                foreach ($methodsData['_embedded']['methods'] as $method) {
                    $methods[] = $method['description'];
                }
            }
        }
        
        // Get recent payments count
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.mollie.com/v2/payments?limit=10",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$apiKey}",
                "Content-Type: application/json"
            ]
        ]);
        
        $paymentsResponse = curl_exec($curl);
        $paymentsCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $recentPayments = 0;
        if ($paymentsCode === 200 && $paymentsResponse) {
            $paymentsData = json_decode($paymentsResponse, true);
            if (isset($paymentsData['_embedded']['payments'])) {
                $recentPayments = count($paymentsData['_embedded']['payments']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Mollie API-Verbindung erfolgreich',
            'details' => [
                'profile_name' => $profile['name'] ?? 'Unbekannt',
                'profile_email' => $profile['email'] ?? 'Nicht verfügbar',
                'test_mode' => strpos($apiKey, 'test_') === 0,
                'available_methods' => $methods,
                'recent_payments' => $recentPayments,
                'response_time' => "< 1s"
            ]
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Mollie API-Verbindung fehlgeschlagen (HTTP ' . $httpCode . ')'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Mollie API-Fehler: ' . $e->getMessage()
    ]);
}
?>