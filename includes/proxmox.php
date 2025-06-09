<?php
class ProxmoxAPI {
    private $host;
    private $username;
    private $password;
    private $ticket;
    private $csrf;
    
    public function __construct() {
        $this->host = PROXMOX_HOST;
        $this->username = PROXMOX_USER;
        $this->password = PROXMOX_PASS;
    }
    
    public function authenticate() {
        $data = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        $response = $this->makeRequest('/access/ticket', 'POST', $data, false);
        
        if (isset($response['data'])) {
            $this->ticket = $response['data']['ticket'];
            $this->csrf = $response['data']['CSRFPreventionToken'];
            return true;
        }
        
        return false;
    }
    
    public function createVM($vmid, $name, $memory = 2048, $cores = 2, $disk = 20) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $data = [
            'vmid' => $vmid,
            'name' => $name,
            'memory' => $memory,
            'cores' => $cores,
            'sockets' => 1,
            'ostype' => 'l26',
            'ide2' => 'local:iso/ubuntu-20.04-server.iso,media=cdrom',
            'scsi0' => "local-lvm:{$disk},format=qcow2",
            'scsihw' => 'virtio-scsi-pci',
            'bootdisk' => 'scsi0',
            'net0' => 'virtio,bridge=vmbr0',
            'start' => 1
        ];
        
        return $this->makeRequest("/nodes/pve/qemu", 'POST', $data);
    }
    
    public function getVMStatus($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/current", 'GET');
    }
    
    public function startVM($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/start", 'POST');
    }
    
    public function stopVM($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/stop", 'POST');
    }
    
    public function restartVM($vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/status/reboot", 'POST');
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
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
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
    
    public function resetPassword($vmid, $newPassword) {
        // Change VM password via Proxmox API
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $data = [
            'password' => $newPassword
        ];
        
        return $this->makeRequest("/nodes/pve/qemu/{$vmid}/config", 'PUT', $data);
    }
}
?>