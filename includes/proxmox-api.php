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
            // Nur warnen, nicht fehlschlagen lassen
        }
    }
    
    /**
     * Authentifizierung bei Proxmox VE
     */
    public function authenticate() {
        // Prüfe ob Credentials verfügbar sind
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
            
            if ($response && isset($response['data'])) {
                $this->ticket = $response['data']['ticket'];
                $this->csrf_token = $response['data']['CSRFPreventionToken'];
                error_log("Proxmox authentication successful for {$this->username}");
                return true;
            }
        } catch (Exception $e) {
            error_log("Proxmox authentication failed: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Erstellt einen LXC Container
     */
    public function createContainer($vmid, $config) {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc";
        
        // LXC Container-Konfiguration
        $vmConfig = [
            'vmid' => $vmid,
            'ostemplate' => $config['template'],
            'hostname' => $config['hostname'],
            'password' => $config['password'],
            'memory' => $config['memory'],
            'cores' => $config['cores'],
            'rootfs' => "local:{$config['disk']}",
            'swap' => 512,
            'net0' => 'name=eth0,bridge=vmbr0,firewall=1,ip=dhcp,type=veth',
            'ostype' => 'ubuntu',
            'unprivileged' => 1,
            'start' => 1,
            'onboot' => 1
        ];
        
        return $this->makeRequest($url, 'POST', $vmConfig);
    }
    
    /**
     * Erstellt eine KVM Virtual Machine
     */
    public function createVM($vmid, $config) {
        if (!$this->authenticate()) {
            throw new Exception('Proxmox authentication failed');
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/qemu";
        
        $vmConfig = [
            'vmid' => $vmid,
            'name' => $config['hostname'] ?? "vps{$vmid}",
            'memory' => $config['memory'] ?? 2048,
            'cores' => $config['cores'] ?? 2,
            'scsi0' => "local:{$config['disk']},format=qcow2",
            'net0' => 'virtio,bridge=vmbr0,firewall=1',
            'ostype' => $config['ostype'] ?? 'l26',
            'boot' => 'c',
            'bootdisk' => 'scsi0',
            'agent' => 1
        ];
        
        return $this->makeRequest($url, 'POST', $vmConfig);
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
        return $response['data']['status'] ?? 'unknown';
    }
    
    /**
     * VM neu starten
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
     * Macht HTTP-Requests an die Proxmox API
     */
    private function makeRequest($url, $method = 'GET', $data = null, $auth = true) {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
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
            CURLOPT_USERAGENT => 'SpectraHost-Proxmox-Client/1.0'
        ]);
        
        // Debug logging
        error_log("Proxmox API Request: {$method} {$url}" . ($data ? " with data: " . json_encode($data) : ""));
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
                
            case 'GET':
                if ($data) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
        }
        
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
        
        // Debug response
        error_log("Proxmox API Response ({$httpCode}): " . substr($response, 0, 500));
        
        if ($httpCode >= 400) {
            $errorMsg = isset($decoded['errors']) ? json_encode($decoded['errors']) : 
                       (isset($decoded['message']) ? $decoded['message'] : "HTTP error {$httpCode}");
            error_log("Proxmox API Error: {$errorMsg}");
            throw new Exception("HTTP error {$httpCode}: {$errorMsg}");
        }
        
        return $decoded;
    }
    
    /**
     * VM-Konfiguration abrufen
     */
    public function getVMConfig($vmid) {
        if (!$this->authenticate()) {
            return null;
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc/{$vmid}/config";
        
        $response = $this->makeRequest('GET', $url);
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        
        return null;
    }
    
    /**
     * VM-Statistiken abrufen
     */
    public function getVMStats($vmid) {
        if (!$this->authenticate()) {
            return null;
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc/{$vmid}/status/current";
        
        $response = $this->makeRequest('GET', $url);
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        
        return null;
    }
    
    /**
     * Passwort zurücksetzen
     */
    public function resetPassword($vmid, $user = 'root', $password = null) {
        if (!$this->authenticate()) {
            return false;
        }
        
        if (!$password) {
            $password = bin2hex(random_bytes(8));
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc/{$vmid}/config";
        
        $data = [
            'password' => $password,
            'user' => $user
        ];
        
        $response = $this->makeRequest('PUT', $url, $data);
        return $response !== false ? $password : false;
    }
    
    /**
     * Betriebssystem neu installieren
     */
    public function reinstallOS($vmid, $ostemplate) {
        if (!$this->authenticate()) {
            return false;
        }
        
        // Zuerst VM stoppen
        $this->stopVM($vmid);
        
        // Warten bis VM gestoppt ist
        sleep(5);
        
        // VM mit neuem Template neu erstellen
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/lxc/{$vmid}/config";
        
        $data = [
            'ostemplate' => $ostemplate,
            'force' => 1
        ];
        
        $response = $this->makeRequest('PUT', $url, $data);
        
        if ($response) {
            // VM nach Neuinstallation starten
            sleep(10);
            $this->startVM($vmid);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verfügbare OS-Templates abrufen
     */
    public function getOSTemplates() {
        if (!$this->authenticate()) {
            return [];
        }
        
        $url = "https://{$this->host}:8006/api2/json/nodes/{$this->node}/storage/local/content";
        
        $response = $this->makeRequest('GET', $url);
        if ($response && isset($response['data'])) {
            $templates = [];
            foreach ($response['data'] as $item) {
                if ($item['content'] === 'vztmpl') {
                    $templates[$item['volid']] = $item['volid'];
                }
            }
            return $templates;
        }
        
        return [];
    }
}
?>