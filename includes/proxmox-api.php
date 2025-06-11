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
        $this->host = $_ENV['PROXMOX_HOST'] ?? '';
        $this->node = $_ENV['PROXMOX_NODE'] ?? '';
        $this->username = $_ENV['PROXMOX_USERNAME'] ?? '';
        $this->password = $_ENV['PROXMOX_PASSWORD'] ?? '';
        
        if (empty($this->host) || empty($this->username) || empty($this->password)) {
            throw new Exception('Proxmox credentials not configured');
        }
    }
    
    /**
     * Authentifizierung bei Proxmox VE
     */
    public function authenticate() {
        $url = "https://{$this->host}:8006/api2/json/access/ticket";
        
        $data = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        $response = $this->makeRequest($url, 'POST', $data, false);
        
        if ($response && isset($response['data'])) {
            $this->ticket = $response['data']['ticket'];
            $this->csrf_token = $response['data']['CSRFPreventionToken'];
            return true;
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
            'net0' => 'name=eth0,bridge=vmbr0,firewall=1,hwaddr=auto,ip=dhcp,type=veth',
            'ostype' => 'ubuntu',
            'unprivileged' => 1,
            'start' => 1
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
        
        return $this->makeRequest($url, 'POST');
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
        
        return $this->makeRequest($url, 'POST');
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
        
        return $this->makeRequest($url, 'DELETE');
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
        
        return $this->makeRequest($url, 'GET');
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
            CURLOPT_TIMEOUT => 30
        ]);
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: {$error}");
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = isset($decoded['message']) ? $decoded['message'] : "HTTP error {$httpCode}";
            throw new Exception("HTTP error {$httpCode}: " . json_encode($decoded));
        }
        
        return $decoded;
    }
}
?>