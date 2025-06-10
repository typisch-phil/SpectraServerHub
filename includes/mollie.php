<?php
class MolliePayment {
    private $apiKey;
    private $baseUrl = 'https://api.mollie.com/v2/';
    
    public function __construct() {
        $this->apiKey = $_ENV['MOLLIE_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            throw new Exception('Mollie API Key ist nicht konfiguriert');
        }
    }
    
    /**
     * Erstellt eine neue Zahlung bei Mollie
     */
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
    
    /**
     * Ruft Zahlungsdetails ab
     */
    public function getPayment($paymentId) {
        return $this->makeRequest("payments/{$paymentId}", 'GET');
    }
    
    /**
     * Verarbeitet Webhook-Benachrichtigungen
     */
    public function processWebhook($paymentId) {
        $payment = $this->getPayment($paymentId);
        
        if (!$payment || !isset($payment['status'])) {
            return false;
        }
        
        return $payment;
    }
    
    /**
     * Führt HTTP-Anfragen an die Mollie API aus
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = isset($decoded['detail']) ? $decoded['detail'] : 'API Error';
            throw new Exception("Mollie API Error ({$httpCode}): {$errorMessage}");
        }
        
        return $decoded;
    }
}

/**
 * Guthaben-Aufladung Klasse
 */
class BalanceTopup {
    private $db;
    private $mollie;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mollie = new MolliePayment();
    }
    
    /**
     * Startet eine Guthaben-Aufladung
     */
    public function startTopup($userId, $amount, $baseUrl) {
        // Validierung
        if ($amount < 5 || $amount > 1000) {
            throw new Exception('Betrag muss zwischen €5 und €1000 liegen');
        }
        
        // Aufladung in Datenbank erstellen
        $stmt = $this->db->prepare("
            INSERT INTO balance_topups (user_id, amount, status, created_at) 
            VALUES (?, ?, 'pending', NOW())
        ");
        $stmt->execute([$userId, $amount]);
        $topupId = $this->db->lastInsertId();
        
        if (!$topupId) {
            throw new Exception('Fehler beim Erstellen der Aufladung');
        }
        
        // Mollie-Zahlung erstellen
        try {
            $payment = $this->mollie->createPayment(
                $amount,
                "Guthaben-Aufladung €{$amount}",
                $baseUrl . "/dashboard/billing/topup-success?topup_id={$topupId}",
                $baseUrl . "/api/mollie-webhook",
                ['topup_id' => $topupId, 'user_id' => $userId]
            );
            
            // Payment ID in Datenbank speichern
            $stmt = $this->db->prepare("
                UPDATE balance_topups 
                SET mollie_payment_id = ?, payment_url = ? 
                WHERE id = ?
            ");
            $stmt->execute([$payment['id'], $payment['_links']['checkout']['href'], $topupId]);
            
            return [
                'success' => true,
                'topup_id' => $topupId,
                'payment_url' => $payment['_links']['checkout']['href'],
                'payment_id' => $payment['id']
            ];
            
        } catch (Exception $e) {
            // Aufladung als fehlgeschlagen markieren
            $stmt = $this->db->prepare("UPDATE balance_topups SET status = 'failed' WHERE id = ?");
            $stmt->execute([$topupId]);
            
            throw $e;
        }
    }
    
    /**
     * Verarbeitet erfolgreiche Zahlung
     */
    public function processSuccessfulPayment($paymentId) {
        // Aufladung anhand Payment ID finden
        $stmt = $this->db->prepare("
            SELECT * FROM balance_topups 
            WHERE mollie_payment_id = ? AND status = 'pending'
        ");
        $stmt->execute([$paymentId]);
        $topup = $stmt->fetch();
        
        if (!$topup) {
            return false;
        }
        
        // Zahlung bei Mollie überprüfen
        $payment = $this->mollie->getPayment($paymentId);
        
        if ($payment['status'] === 'paid') {
            // Guthaben zum Benutzer hinzufügen
            $stmt = $this->db->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$topup['amount'], $topup['user_id']]);
            
            // Aufladung als erfolgreich markieren
            $stmt = $this->db->prepare("
                UPDATE balance_topups 
                SET status = 'completed', completed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$topup['id']]);
            
            // Protokollierung
            $stmt = $this->db->prepare("
                INSERT INTO balance_transactions (user_id, type, amount, description, created_at) 
                VALUES (?, 'credit', ?, ?, NOW())
            ");
            $stmt->execute([
                $topup['user_id'], 
                $topup['amount'], 
                "Guthaben-Aufladung via Mollie (#{$topup['id']})"
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Ruft Aufladungs-Historie für einen Benutzer ab
     */
    public function getTopupHistory($userId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM balance_topups 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
?>