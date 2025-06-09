<?php

class MollieAPI {
    private $apiKey;
    private $baseUrl = 'https://api.mollie.com/v2/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function createPayment($amount, $description, $redirectUrl, $webhookUrl, $metadata = []) {
        // Demo-Modus für Entwicklung
        if (strpos($this->apiKey, 'test_') === 0 || $this->apiKey === 'demo_key') {
            return [
                'id' => 'tr_demo_' . uniqid(),
                'status' => 'open',
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($amount, 2, '.', '')
                ],
                'description' => $description,
                '_links' => [
                    'checkout' => [
                        'href' => '/demo/payment?amount=' . urlencode($amount) . '&description=' . urlencode($description)
                    ]
                ],
                'metadata' => $metadata
            ];
        }
        
        $data = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($amount, 2, '.', '')
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $webhookUrl,
            'metadata' => $metadata
        ];
        
        return $this->makeRequest('payments', 'POST', $data);
    }
    
    public function getPayment($paymentId) {
        // Demo-Modus für Entwicklung
        if (strpos($this->apiKey, 'test_') === 0 || $this->apiKey === 'demo_key' || strpos($paymentId, 'tr_demo_') === 0) {
            return [
                'id' => $paymentId,
                'status' => 'paid',
                'amount' => [
                    'currency' => 'EUR',
                    'value' => '10.00'
                ],
                'paidAt' => date('c'),
                'metadata' => []
            ];
        }
        
        return $this->makeRequest("payments/{$paymentId}", 'GET');
    }
    
    public function getPaymentMethods() {
        // Demo-Methoden für Entwicklung
        if (strpos($this->apiKey, 'test_') === 0 || $this->apiKey === 'demo_key') {
            return [
                'count' => 3,
                '_embedded' => [
                    'methods' => [
                        ['id' => 'ideal', 'description' => 'iDEAL'],
                        ['id' => 'creditcard', 'description' => 'Kreditkarte'],
                        ['id' => 'paypal', 'description' => 'PayPal']
                    ]
                ]
            ];
        }
        
        return $this->makeRequest('methods', 'GET');
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception("Mollie API Error: " . $response);
        }
    }
}

// Initialize Mollie API with test key
$mollieApiKey = 'test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM';
$mollie = new MollieAPI($mollieApiKey);

?>