<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/proxmox-api.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$pageTitle = 'VPS Konfigurator - SpectraHost';
$pageDescription = 'Konfigurieren Sie Ihren individuellen VPS Server nach Ihren Anforderungen';

$db = Database::getInstance()->getConnection();

// VPS-Konfigurationsoptionen
$ramOptions = [
    1 => ['gb' => 1, 'price' => 5.99],
    2 => ['gb' => 2, 'price' => 9.99],
    4 => ['gb' => 4, 'price' => 15.99],
    8 => ['gb' => 8, 'price' => 25.99],
    16 => ['gb' => 16, 'price' => 45.99],
    32 => ['gb' => 32, 'price' => 85.99]
];

$cpuOptions = [
    1 => ['cores' => 1, 'price' => 3.99],
    2 => ['cores' => 2, 'price' => 7.99],
    4 => ['cores' => 4, 'price' => 14.99],
    6 => ['cores' => 6, 'price' => 21.99],
    8 => ['cores' => 8, 'price' => 28.99]
];

$storageOptions = [
    20 => ['gb' => 20, 'price' => 2.99],
    50 => ['gb' => 50, 'price' => 5.99],
    100 => ['gb' => 100, 'price' => 9.99],
    200 => ['gb' => 200, 'price' => 17.99],
    500 => ['gb' => 500, 'price' => 39.99],
    1000 => ['gb' => 1000, 'price' => 69.99]
];

$osTemplates = [
    'ubuntu-22.04' => 'Ubuntu 22.04 LTS',
    'ubuntu-20.04' => 'Ubuntu 20.04 LTS',
    'debian-11' => 'Debian 11',
    'debian-12' => 'Debian 12',
    'centos-9' => 'CentOS Stream 9',
    'rocky-9' => 'Rocky Linux 9',
    'alpine-3.18' => 'Alpine Linux 3.18'
];

$orderMessage = '';
$orderType = '';

// Bestellverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selectedRam = intval($_POST['ram'] ?? 1);
        $selectedCpu = intval($_POST['cpu'] ?? 1);
        $selectedStorage = intval($_POST['storage'] ?? 20);
        $selectedOs = $_POST['os_template'] ?? 'ubuntu-22.04';
        $serverName = trim($_POST['server_name'] ?? '');
        
        // Validierung
        if (!isset($ramOptions[$selectedRam]) || !isset($cpuOptions[$selectedCpu]) || !isset($storageOptions[$selectedStorage])) {
            throw new Exception('Ungültige Konfiguration ausgewählt');
        }
        
        if (!isset($osTemplates[$selectedOs])) {
            throw new Exception('Ungültiges Betriebssystem ausgewählt');
        }
        
        if (empty($serverName) || !preg_match('/^[a-zA-Z0-9\-]+$/', $serverName)) {
            throw new Exception('Ungültiger Servername. Nur Buchstaben, Zahlen und Bindestriche erlaubt.');
        }
        
        // Gesamtpreis berechnen
        $totalPrice = $ramOptions[$selectedRam]['price'] + 
                     $cpuOptions[$selectedCpu]['price'] + 
                     $storageOptions[$selectedStorage]['price'];
        
        // Prüfe verfügbare IP-Adresse
        $stmt = $db->prepare("SELECT ip_address, gateway, subnet_mask FROM ip_addresses WHERE is_available = 1 LIMIT 1");
        $stmt->execute();
        $availableIp = $stmt->fetch();
        
        if (!$availableIp) {
            throw new Exception('Derzeit sind keine IP-Adressen verfügbar. Bitte kontaktieren Sie den Support.');
        }
        
        // Bestellung in Datenbank speichern
        $stmt = $db->prepare("
            INSERT INTO user_orders (
                user_id, service_type, server_name, 
                ram_gb, cpu_cores, storage_gb, 
                os_template, monthly_price, 
                ip_address, status, created_at
            ) VALUES (?, 'vserver', ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'], 
            $serverName,
            $selectedRam,
            $selectedCpu, 
            $selectedStorage,
            $selectedOs,
            $totalPrice,
            $availableIp['ip_address']
        ]);
        
        $orderId = $db->lastInsertId();
        
        // IP als reserviert markieren
        $stmt = $db->prepare("UPDATE ip_addresses SET is_available = 0, assigned_service_id = ? WHERE ip_address = ?");
        $stmt->execute([$orderId, $availableIp['ip_address']]);
        
        // Proxmox VPS erstellen (asynchron)
        try {
            $proxmox = new ProxmoxAPI();
            $vmid = $proxmox->getNextVMID();
            
            $vmConfig = [
                'vmid' => $vmid,
                'hostname' => $serverName,
                'memory' => $selectedRam * 1024, // MB
                'cores' => $selectedCpu,
                'rootfs' => "local-lvm:{$selectedStorage}",
                'ostemplate' => "local:vztmpl/{$selectedOs}-standard_amd64.tar.xz",
                'net0' => "name=eth0,bridge=vmbr0,ip={$availableIp['ip_address']}/24,gw={$availableIp['gateway']}",
                'nameserver' => '8.8.8.8 8.8.4.4',
                'password' => bin2hex(random_bytes(8))
            ];
            
            $result = $proxmox->createLXC($vmConfig);
            
            if ($result) {
                // Service in user_services erstellen
                $stmt = $db->prepare("
                    INSERT INTO user_services (
                        user_id, service_id, server_name, 
                        proxmox_vmid, server_password, 
                        ip_address, status, expires_at, created_at
                    ) VALUES (?, 1, ?, ?, ?, ?, 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH), NOW())
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $serverName,
                    $vmid,
                    $vmConfig['password'],
                    $availableIp['ip_address']
                ]);
                
                // Bestellung als completed markieren
                $stmt = $db->prepare("UPDATE user_orders SET status = 'completed', proxmox_vmid = ? WHERE id = ?");
                $stmt->execute([$vmid, $orderId]);
                
                $orderMessage = "VPS erfolgreich erstellt! VMID: {$vmid}, IP: {$availableIp['ip_address']}";
                $orderType = 'success';
            } else {
                throw new Exception('VPS konnte nicht erstellt werden');
            }
            
        } catch (Exception $e) {
            // Bei Proxmox-Fehler: Bestellung als failed markieren, aber IP freigeben
            $stmt = $db->prepare("UPDATE user_orders SET status = 'failed', error_message = ? WHERE id = ?");
            $stmt->execute([$e->getMessage(), $orderId]);
            
            $stmt = $db->prepare("UPDATE ip_addresses SET is_available = 1, assigned_service_id = NULL WHERE ip_address = ?");
            $stmt->execute([$availableIp['ip_address']]);
            
            throw new Exception('VPS-Erstellung fehlgeschlagen: ' . $e->getMessage());
        }
        
    } catch (Exception $e) {
        $orderMessage = $e->getMessage();
        $orderType = 'error';
    }
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-purple-900 to-blue-900 rounded-xl p-8 border border-purple-700 shadow-xl">
                <h1 class="text-4xl font-bold text-white mb-3">VPS Konfigurator</h1>
                <p class="text-gray-200 text-lg">Konfigurieren Sie Ihren individuellen VPS Server nach Ihren Anforderungen</p>
            </div>
        </div>

        <?php if ($orderMessage): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $orderType === 'success' ? 'bg-green-900 border border-green-600 text-green-200' : 'bg-red-900 border border-red-600 text-red-200'; ?>">
            <i class="fas <?php echo $orderType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($orderMessage); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Hauptkonfiguration -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Server Name -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">Servername</h3>
                        <input type="text" name="server_name" required
                               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none"
                               placeholder="mein-vps" pattern="[a-zA-Z0-9\-]+" 
                               title="Nur Buchstaben, Zahlen und Bindestriche">
                        <p class="text-gray-400 text-sm mt-2">Nur Buchstaben, Zahlen und Bindestriche erlaubt</p>
                    </div>
                    
                    <!-- RAM Auswahl -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">Arbeitsspeicher (RAM)</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-300">1 GB</span>
                                <span id="ram-display" class="text-white font-semibold text-lg">2 GB</span>
                                <span class="text-gray-300">32 GB</span>
                            </div>
                            <div class="relative">
                                <input type="range" name="ram-slider" id="ram-slider" 
                                       min="0" max="5" value="1" step="1"
                                       class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
                                <input type="hidden" name="ram" id="ram-value" value="2">
                            </div>
                            <div class="text-center">
                                <span class="text-purple-400 font-semibold" id="ram-price-display">€9.99/Monat</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CPU Auswahl -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">CPU Kerne</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-300">1 Core</span>
                                <span id="cpu-display" class="text-white font-semibold text-lg">2 Cores</span>
                                <span class="text-gray-300">8 Cores</span>
                            </div>
                            <div class="relative">
                                <input type="range" name="cpu-slider" id="cpu-slider" 
                                       min="0" max="4" value="1" step="1"
                                       class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
                                <input type="hidden" name="cpu" id="cpu-value" value="2">
                            </div>
                            <div class="text-center">
                                <span class="text-purple-400 font-semibold" id="cpu-price-display">€7.99/Monat</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Storage Auswahl -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">SSD Speicher</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-300">20 GB</span>
                                <span id="storage-display" class="text-white font-semibold text-lg">50 GB</span>
                                <span class="text-gray-300">1000 GB</span>
                            </div>
                            <div class="relative">
                                <input type="range" name="storage-slider" id="storage-slider" 
                                       min="0" max="5" value="1" step="1"
                                       class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
                                <input type="hidden" name="storage" id="storage-value" value="50">
                            </div>
                            <div class="text-center">
                                <span class="text-purple-400 font-semibold" id="storage-price-display">€5.99/Monat</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Betriebssystem -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">Betriebssystem</h3>
                        <select name="os_template" required
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none">
                            <?php foreach ($osTemplates as $template => $name): ?>
                            <option value="<?php echo $template; ?>" <?php echo $template === 'ubuntu-22.04' ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Preisübersicht -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 sticky top-4">
                        <h3 class="text-xl font-semibold text-white mb-4">Preisübersicht</h3>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-300">
                                <span>RAM:</span>
                                <span id="ram-price">€<?php echo number_format($ramOptions[2]['price'], 2); ?></span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>CPU:</span>
                                <span id="cpu-price">€<?php echo number_format($cpuOptions[2]['price'], 2); ?></span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span>Storage:</span>
                                <span id="storage-price">€<?php echo number_format($storageOptions[50]['price'], 2); ?></span>
                            </div>
                            <hr class="border-gray-600">
                            <div class="flex justify-between text-white font-semibold text-lg">
                                <span>Gesamt/Monat:</span>
                                <span id="total-price">€<?php echo number_format($ramOptions[2]['price'] + $cpuOptions[2]['price'] + $storageOptions[50]['price'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="bg-gray-700 rounded-lg p-4">
                                <h4 class="text-white font-semibold mb-2">Inklusivleistungen</h4>
                                <ul class="text-gray-300 text-sm space-y-1">
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>Statische IP-Adresse</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>Root-Zugang</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>99.9% Uptime SLA</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>24/7 Support</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>DDoS-Schutz</li>
                                </ul>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                VPS jetzt bestellen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Custom Slider Styles */
.slider {
    -webkit-appearance: none;
    appearance: none;
    background: linear-gradient(to right, #7c3aed 0%, #7c3aed var(--slider-progress, 25%), #374151 var(--slider-progress, 25%), #374151 100%);
    outline: none;
    border-radius: 6px;
}

.slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    border: 2px solid #ffffff;
    transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}

.slider::-moz-range-thumb {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    border: 2px solid #ffffff;
    transition: all 0.2s ease;
}

.slider::-moz-range-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}
</style>

<script>
// Konfigurationsoptionen
const ramOptions = <?php echo json_encode(array_values($ramOptions)); ?>;
const cpuOptions = <?php echo json_encode(array_values($cpuOptions)); ?>;
const storageOptions = <?php echo json_encode(array_values($storageOptions)); ?>;

function updateSliderProgress(slider, progress) {
    slider.style.setProperty('--slider-progress', progress + '%');
}

function updatePricing() {
    const ramPrice = parseFloat(document.getElementById('ram-value').value);
    const cpuPrice = parseFloat(document.getElementById('cpu-value').value);
    const storagePrice = parseFloat(document.getElementById('storage-value').value);
    
    // Finde die aktuellen Preise basierend auf den Werten
    const currentRamOption = ramOptions.find(option => option.gb == document.getElementById('ram-value').value);
    const currentCpuOption = cpuOptions.find(option => option.cores == document.getElementById('cpu-value').value);
    const currentStorageOption = storageOptions.find(option => option.gb == document.getElementById('storage-value').value);
    
    const finalRamPrice = currentRamOption ? currentRamOption.price : 0;
    const finalCpuPrice = currentCpuOption ? currentCpuOption.price : 0;
    const finalStoragePrice = currentStorageOption ? currentStorageOption.price : 0;
    
    document.getElementById('ram-price').textContent = '€' + finalRamPrice.toFixed(2);
    document.getElementById('cpu-price').textContent = '€' + finalCpuPrice.toFixed(2);
    document.getElementById('storage-price').textContent = '€' + finalStoragePrice.toFixed(2);
    document.getElementById('total-price').textContent = '€' + (finalRamPrice + finalCpuPrice + finalStoragePrice).toFixed(2);
}

// RAM Slider
document.getElementById('ram-slider').addEventListener('input', function() {
    const index = parseInt(this.value);
    const option = ramOptions[index];
    
    document.getElementById('ram-display').textContent = option.gb + ' GB';
    document.getElementById('ram-price-display').textContent = '€' + option.price.toFixed(2) + '/Monat';
    document.getElementById('ram-value').value = option.gb;
    
    const progress = (index / (ramOptions.length - 1)) * 100;
    updateSliderProgress(this, progress);
    updatePricing();
});

// CPU Slider
document.getElementById('cpu-slider').addEventListener('input', function() {
    const index = parseInt(this.value);
    const option = cpuOptions[index];
    
    document.getElementById('cpu-display').textContent = option.cores + (option.cores > 1 ? ' Cores' : ' Core');
    document.getElementById('cpu-price-display').textContent = '€' + option.price.toFixed(2) + '/Monat';
    document.getElementById('cpu-value').value = option.cores;
    
    const progress = (index / (cpuOptions.length - 1)) * 100;
    updateSliderProgress(this, progress);
    updatePricing();
});

// Storage Slider
document.getElementById('storage-slider').addEventListener('input', function() {
    const index = parseInt(this.value);
    const option = storageOptions[index];
    
    document.getElementById('storage-display').textContent = option.gb + ' GB';
    document.getElementById('storage-price-display').textContent = '€' + option.price.toFixed(2) + '/Monat';
    document.getElementById('storage-value').value = option.gb;
    
    const progress = (index / (storageOptions.length - 1)) * 100;
    updateSliderProgress(this, progress);
    updatePricing();
});

// Initialize sliders
document.addEventListener('DOMContentLoaded', function() {
    // Initialize RAM slider
    const ramSlider = document.getElementById('ram-slider');
    const ramIndex = ramSlider.value;
    const ramProgress = (ramIndex / (ramOptions.length - 1)) * 100;
    updateSliderProgress(ramSlider, ramProgress);
    
    // Initialize CPU slider
    const cpuSlider = document.getElementById('cpu-slider');
    const cpuIndex = cpuSlider.value;
    const cpuProgress = (cpuIndex / (cpuOptions.length - 1)) * 100;
    updateSliderProgress(cpuSlider, cpuProgress);
    
    // Initialize Storage slider
    const storageSlider = document.getElementById('storage-slider');
    const storageIndex = storageSlider.value;
    const storageProgress = (storageIndex / (storageOptions.length - 1)) * 100;
    updateSliderProgress(storageSlider, storageProgress);
    
    updatePricing();
});
</script>

<?php
renderFooter();
?>