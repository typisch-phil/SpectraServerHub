<?php

class ProxmoxAPI {
    private $host;
    private $username;
    private $password;
    private $ticket;
    private $csrf;
    
    public function __construct($host, $username, $password) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }
    
    private function authenticate() {
        // Demo-Modus für Entwicklung
        if ($this->host === 'demo.proxmox.com' || empty($this->password)) {
            $this->ticket = 'demo_ticket_' . uniqid();
            $this->csrf = 'demo_csrf_' . uniqid();
            return true;
        }
        
        $data = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        try {
            $response = $this->makeRequest('/access/ticket', 'POST', $data, false);
            
            if (isset($response['data'])) {
                $this->ticket = $response['data']['ticket'];
                $this->csrf = $response['data']['CSRFPreventionToken'];
                return true;
            }
        } catch (Exception $e) {
            // Fallback für Demo
            $this->ticket = 'demo_ticket_' . uniqid();
            $this->csrf = 'demo_csrf_' . uniqid();
        }
        
        return false;
    }
    
    public function getVMList() {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        // Demo-Daten für Entwicklung
        if ($this->host === 'demo.proxmox.com' || !$this->ticket) {
            return [
                'data' => [
                    ['vmid' => '100', 'name' => 'web-server-01', 'status' => 'running', 'cpu' => 2, 'mem' => 2048],
                    ['vmid' => '101', 'name' => 'db-server-01', 'status' => 'stopped', 'cpu' => 4, 'mem' => 4096],
                    ['vmid' => '102', 'name' => 'game-server-01', 'status' => 'running', 'cpu' => 8, 'mem' => 8192]
                ]
            ];
        }
        
        return $this->makeRequest('/cluster/resources?type=vm', 'GET');
    }
    
    public function getVMStatus($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        // Demo-Daten
        if ($this->host === 'demo.proxmox.com' || !$this->ticket) {
            return [
                'data' => [
                    'vmid' => $vmid,
                    'status' => 'running',
                    'cpu' => 0.15,
                    'mem' => 1073741824,
                    'uptime' => 86400
                ]
            ];
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/current", 'GET');
    }
    
    public function startVM($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        // Demo-Modus
        if ($this->host === 'demo.proxmox.com' || !$this->ticket) {
            return ['success' => true, 'message' => 'VM gestartet', 'vmid' => $vmid];
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/start", 'POST');
    }
    
    public function stopVM($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        // Demo-Modus
        if ($this->host === 'demo.proxmox.com' || !$this->ticket) {
            return ['success' => true, 'message' => 'VM gestoppt', 'vmid' => $vmid];
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/stop", 'POST');
    }
    
    public function restartVM($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        // Demo-Modus
        if ($this->host === 'demo.proxmox.com' || !$this->ticket) {
            return ['success' => true, 'message' => 'VM neugestartet', 'vmid' => $vmid];
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/reboot", 'POST');
    }
    
    public function resetPassword($vmid, $newPassword) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        // Demo-Modus für Entwicklung
        if ($this->host === 'demo.proxmox.com' || !$this->ticket) {
            return [
                'success' => true,
                'message' => 'Passwort erfolgreich zurückgesetzt',
                'vmid' => $vmid
            ];
        }
        
        // Proxmox VE API für Passwort-Reset
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/config", 'PUT', [
            'cipassword' => $newPassword
        ]);
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null, $auth = true) {
        $url = "https://{$this->host}:8006/api2/json{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $headers = [];
        
        if ($auth && $this->ticket) {
            $headers[] = 'Cookie: PVEAuthCookie=' . $this->ticket;
            if ($method !== 'GET') {
                $headers[] = 'CSRFPreventionToken: ' . $this->csrf;
            }
        }
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Proxmox API Fehler: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
}

// Proxmox VE API initialisieren
$proxmox = new ProxmoxAPI(
    PROXMOX_HOST ?? 'demo.proxmox.com',
    PROXMOX_USERNAME ?? 'demo@pve',
    PROXMOX_PASSWORD ?? ''
);

?>