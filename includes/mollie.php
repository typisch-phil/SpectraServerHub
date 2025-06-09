<?php
class MolliePayment {
    private $apiKey;
    private $apiUrl;
    
    public function __construct() {
        $this->apiKey = MOLLIE_API_KEY;
        $this->apiUrl = 'https://api.mollie.com/v2/';
    }
    
    public function createPayment($amount, $description, $redirectUrl, $webhookUrl = null, $metadata = []) {
        $data = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($amount, 2, '.', '')
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'metadata' => $metadata
        ];
        
        if ($webhookUrl) {
            $data['webhookUrl'] = $webhookUrl;
        }
        
        return $this->makeRequest('payments', 'POST', $data);
    }
    
    public function getPayment($paymentId) {
        return $this->makeRequest('payments/' . $paymentId, 'GET');
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . $endpoint;
        
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
        
        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new Exception('Mollie API Fehler: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
}
?>