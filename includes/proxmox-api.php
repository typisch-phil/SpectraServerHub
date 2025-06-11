<?php
/**
 * Proxmox VE API Client für SpectraHost
 * Erstellt und verwaltet VPS-Container über Proxmox VE
 */

class ProxmoxAPI {
    private $host;
    private $node;
    private $username;
    private $password;
    private $ticket;
    private $csrf_token;
    
    public function __construct() {
        $this->host = getenv('PROXMOX_HOST') ?: '';
        $this->node = getenv('PROXMOX_NODE') ?: 'pve';
        $this->username = getenv('PROXMOX_USERNAME') ?: '';
        $this->password = getenv('PROXMOX_PASSWORD') ?: '';
        
        if (empty($this->host) || empty($this->username) || empty($this->password)) {
            error_log("Proxmox credentials missing: HOST={$this->host}, USER={$this->username}, NODE={$this->node}");
        }
    }
    
    /**
     * Authentifizierung bei Proxmox VE
     */
    public function authenticate() {
        if (empty($this->host) || empty($this->username) || empty($this->password)) {
            error_log("Proxmox credentials not configured - authentication skipped");
            return false;
        }

        if ($this->ticket && $this->csrf_token) {
            return true;
        }
        
        $url = "https://{$this->host}:8006/api2/json/access/ticket";
        
        $data = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        try {
            $response = $this->makeRequest($url, 'POST', $data, false);
            
            if (isset($response['data']['ticket']) && isset($response['data']['CSRFPreventionToken'])) {
                $this->ticket = $response['data']['ticket'];
                $this->csrf_token = $response['data']['CSRFPreventionToken'];
                error_log("Proxmox authentication successful for {$this->username}");
                return true;
            }
            
            error_log("Proxmox authentication failed - invalid response");
            return false;
            
        } catch (Exception $e) {
            error_log("Proxmox authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Erstellt einen neuen LXC Container
     */
    public function createLXC($config) {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $vmid = $config['vmid'];
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc";
        
        $lxcConfig = [
            'vmid' => $vmid,
            'hostname' => $config['hostname'] ?? "ct{$vmid}",
            'memory' => $config['memory'] ?? 1024,
            'cores' => $config['cores'] ?? 1,
            'rootfs' => $config['rootfs'] ?? 'local-lvm:8',
            'ostemplate' => $config['ostemplate'] ?? 'local:vztmpl/ubuntu-22.04-standard_amd64.tar.xz',
            'net0' => $config['net0'] ?? 'name=eth0,bridge=vmbr0,ip=dhcp',
            'nameserver' => $config['nameserver'] ?? '8.8.8.8',
            'password' => $config['password'] ?? bin2hex(random_bytes(8)),
            'unprivileged' => 1,
            'start' => 1
        ];
        
        try {
            $response = $this->makeRequest($url, 'POST', $lxcConfig);
            
            if (isset($response['data'])) {
                error_log("LXC Container {$vmid} creation initiated. UPID: " . ($response['data'] ?? 'N/A'));
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("LXC creation failed for VMID {$vmid}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Startet einen Container oder VM
     */
    public function startVM($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}/status/start";
        
        try {
            $response = $this->makeRequest($url, 'POST');
            return isset($response['data']) && $response['data'] !== null;
        } catch (Exception $e) {
            error_log("Start VM {$vmid} failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Stoppt einen Container oder VM
     */
    public function stopVM($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}/status/stop";
        
        try {
            $response = $this->makeRequest($url, 'POST');
            return isset($response['data']) && $response['data'] !== null;
        } catch (Exception $e) {
            error_log("Stop VM {$vmid} failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Neustart eines Containers oder VM
     */
    public function restartVM($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            return false;
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}/status/reboot";
        
        $response = $this->makeRequest($url, 'POST');
        return $response !== false;
    }
    
    /**
     * Löscht einen Container oder VM
     */
    public function deleteVM($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}";
        
        $response = $this->makeRequest($url, 'DELETE');
        return $response !== false;
    }
    
    /**
     * Holt den Status eines VMs
     */
    public function getVMStatus($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}/status/current";
        
        $response = $this->makeRequest($url, 'GET');
        error_log("VPS {$vmid} status retrieved: " . ($response['data']['status'] ?? 'unknown'));
        return $response['data']['status'] ?? 'unknown';
    }
    
    /**
     * Holt die VM-Konfiguration
     */
    public function getVMConfig($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}/config";
        
        $response = $this->makeRequest($url, 'GET');
        return $response['data'] ?? null;
    }
    
    /**
     * Holt VM-Statistiken für Auslastung
     */
    public function getVMStats($vmid, $type = 'lxc') {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $endpoint = $type === 'lxc' ? 'lxc' : 'qemu';
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/{$endpoint}/{$vmid}/status/current";
        
        $response = $this->makeRequest($url, 'GET');
        error_log("VPS {$vmid} status retrieved: " . ($response['data']['status'] ?? 'unknown'));
        
        if (isset($response['data'])) {
            return $response['data'];
        }
        
        return null;
    }
    
    /**
     * Generiert eine neue VMID
     */
    public function getNextVMID() {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $url = "https://{$this->host}:8006/api2/json/cluster/nextid";
        $response = $this->makeRequest($url, 'GET');
        
        return $response['data'] ?? null;
    }
    
    /**
     * Passwort zurücksetzen
     */
    public function resetPassword($vmid, $password, $user = 'root') {
        if (!$this->authenticate()) {
            return false;
        }
        
        if (!$password) {
            $password = bin2hex(random_bytes(8));
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc/{$vmid}/config";
        
        $data = [
            'password' => $password
        ];
        
        $response = $this->makeRequest($url, 'PUT', $data);
        return $response !== false ? $password : false;
    }
    
    /**
     * Betriebssystem neu installieren
     */
    public function reinstallOS($vmid, $ostemplate) {
        if (!$this->authenticate()) {
            return false;
        }
        
        $this->stopVM($vmid);
        sleep(5);
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc/{$vmid}/config";
        
        $data = [
            'ostemplate' => $ostemplate,
            'force' => 1
        ];
        
        $response = $this->makeRequest($url, 'PUT', $data);
        
        if ($response) {
            sleep(3);
            $this->startVM($vmid);
            return true;
        }
        
        return false;
    }
    
    /**
     * Macht HTTP-Requests an die Proxmox API
     */
    private function makeRequest($url, $method = 'GET', $data = null, $auth = true) {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Connection: close',
            'Transfer-Encoding:'
        ];
        
        if ($auth && $this->ticket && $this->csrf_token) {
            $headers[] = "Cookie: PVEAuthCookie={$this->ticket}";
            $headers[] = "CSRFPreventionToken: {$this->csrf_token}";
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'SpectraHost-Proxmox-Client/1.0',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true
        ]);
        
        error_log("Proxmox API Request: {$method} {$url}" . ($data ? " with data: " . json_encode($data) : ""));
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    $postData = http_build_query($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    $headers[] = 'Content-Length: ' . strlen($postData);
                } else {
                    $headers[] = 'Content-Length: 0';
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($data) {
                    $postData = http_build_query($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    $headers[] = 'Content-Length: ' . strlen($postData);
                } else {
                    $headers[] = 'Content-Length: 0';
                }
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    $postData = http_build_query($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    $headers[] = 'Content-Length: ' . strlen($postData);
                } else {
                    $headers[] = 'Content-Length: 0';
                }
                break;
                
            case 'GET':
                if ($data) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("Proxmox cURL error: {$error}");
            throw new Exception("cURL error: {$error}");
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        error_log("Proxmox API Response ({$httpCode}): " . substr($response, 0, 500));
        
        if ($httpCode >= 400) {
            $errorMsg = isset($decoded['errors']) ? json_encode($decoded['errors']) : 
                       (isset($decoded['message']) ? $decoded['message'] : "HTTP error {$httpCode}");
            error_log("Proxmox API Error: {$errorMsg}");
            throw new Exception("HTTP error {$httpCode}: {$errorMsg}");
        }
        
        return $decoded;
    }
}
?>