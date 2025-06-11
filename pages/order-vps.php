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
$ramOptions = [];
for ($i = 1; $i <= 32; $i++) {
    $ramOptions[$i] = ['gb' => $i, 'price' => $i * 0.70];
}

$cpuOptions = [];
for ($i = 1; $i <= 12; $i++) {
    $cpuOptions[$i] = ['cores' => $i, 'price' => $i * 1.20];
}

$storageOptions = [];
for ($i = 10; $i <= 100; $i += 10) {
    $storageOptions[$i] = ['gb' => $i, 'price' => ($i / 10) * 0.70];
}

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
        $selectedCpu = intval($_POST['cpu'] ?? 2);
        $selectedStorage = intval($_POST['storage'] ?? 10);
        $selectedOs = $_POST['os_template'] ?? 'ubuntu-22.04';
        $serverName = trim($_POST['server_name'] ?? '');
        $serverPassword = trim($_POST['server_password'] ?? '');
        
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
        
        if (empty($serverPassword) || strlen($serverPassword) < 8) {
            throw new Exception('Das Passwort muss mindestens 8 Zeichen lang sein.');
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
                ip_address, service_id, price, billing_cycle, status
            ) VALUES (?, 'vserver', ?, ?, ?, ?, ?, ?, ?, 1, ?, 'monthly', 'pending')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'], 
            $serverName,
            $selectedRam,
            $selectedCpu, 
            $selectedStorage,
            $selectedOs,
            $totalPrice,
            $availableIp['ip_address'],
            $totalPrice
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
                'rootfs' => "local:{$selectedStorage}",
                'ostemplate' => "local:vztmpl/{$selectedOs}-standard_amd64.tar.xz",
                'net0' => "name=eth0,bridge=vmbr0,ip={$availableIp['ip_address']}/24,gw={$availableIp['gateway']}",
                'nameserver' => '8.8.8.8 8.8.4.4',
                'password' => $serverPassword
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
                    $serverPassword,
                    $availableIp['ip_address']
                ]);
                
                // Bestellung als active markieren
                $stmt = $db->prepare("UPDATE user_orders SET status = 'active', proxmox_vmid = ? WHERE id = ?");
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
                    
                    <!-- Server-Passwort -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">Root-Passwort</h3>
                        <input type="password" name="server_password" id="server_password" required minlength="8"
                               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none"
                               placeholder="Starkes Passwort eingeben"
                               title="Mindestens 8 Zeichen">
                        <p class="text-gray-400 text-sm mt-2">Mindestens 8 Zeichen, wird für Root-Login verwendet</p>
                        <button type="button" onclick="generatePassword()" 
                                class="mt-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                            Sicheres Passwort generieren
                        </button>
                    </div>
                    
                    <!-- RAM Auswahl -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">Arbeitsspeicher (RAM)</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-300">1 GB</span>
                                <span id="ram-display" class="text-white font-semibold text-lg">1 GB</span>
                                <span class="text-gray-300">32 GB</span>
                            </div>
                            <div class="relative">
                                <input type="range" name="ram-slider" id="ram-slider" 
                                       min="1" max="32" value="1" step="1"
                                       class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
                                <input type="hidden" name="ram" id="ram-value" value="1">
                            </div>
                            <div class="text-center">
                                <span class="text-purple-400 font-semibold" id="ram-price-display">€0.70/Monat</span>
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
                                <span class="text-gray-300">12 Cores</span>
                            </div>
                            <div class="relative">
                                <input type="range" name="cpu-slider" id="cpu-slider" 
                                       min="1" max="12" value="2" step="1"
                                       class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
                                <input type="hidden" name="cpu" id="cpu-value" value="2">
                            </div>
                            <div class="text-center">
                                <span class="text-purple-400 font-semibold" id="cpu-price-display">€2.40/Monat</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Storage Auswahl -->
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <h3 class="text-xl font-semibold text-white mb-4">SSD Speicher</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-300">10 GB</span>
                                <span id="storage-display" class="text-white font-semibold text-lg">10 GB</span>
                                <span class="text-gray-300">100 GB</span>
                            </div>
                            <div class="relative">
                                <input type="range" name="storage-slider" id="storage-slider" 
                                       min="10" max="100" value="10" step="10"
                                       class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
                                <input type="hidden" name="storage" id="storage-value" value="10">
                            </div>
                            <div class="text-center">
                                <span class="text-purple-400 font-semibold" id="storage-price-display">€0.70/Monat</span>
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
    const ramValue = parseInt(document.getElementById('ram-value').value);
    const cpuValue = parseInt(document.getElementById('cpu-value').value);
    const storageValue = parseInt(document.getElementById('storage-value').value);
    
    const ramPrice = ramValue * 0.70;
    const cpuPrice = cpuValue * 1.20;
    const storagePrice = (storageValue / 10) * 0.70;
    
    document.getElementById('ram-price').textContent = '€' + ramPrice.toFixed(2);
    document.getElementById('cpu-price').textContent = '€' + cpuPrice.toFixed(2);
    document.getElementById('storage-price').textContent = '€' + storagePrice.toFixed(2);
    document.getElementById('total-price').textContent = '€' + (ramPrice + cpuPrice + storagePrice).toFixed(2);
}

// RAM Slider
document.getElementById('ram-slider').addEventListener('input', function() {
    const value = parseInt(this.value);
    const price = value * 0.70;
    
    document.getElementById('ram-display').textContent = value + ' GB';
    document.getElementById('ram-price-display').textContent = '€' + price.toFixed(2) + '/Monat';
    document.getElementById('ram-value').value = value;
    
    const progress = ((value - 1) / (32 - 1)) * 100;
    updateSliderProgress(this, progress);
    updatePricing();
});

// CPU Slider
document.getElementById('cpu-slider').addEventListener('input', function() {
    const value = parseInt(this.value);
    const price = value * 1.20;
    
    document.getElementById('cpu-display').textContent = value + (value > 1 ? ' Cores' : ' Core');
    document.getElementById('cpu-price-display').textContent = '€' + price.toFixed(2) + '/Monat';
    document.getElementById('cpu-value').value = value;
    
    const progress = ((value - 1) / (12 - 1)) * 100;
    updateSliderProgress(this, progress);
    updatePricing();
});

// Storage Slider
document.getElementById('storage-slider').addEventListener('input', function() {
    const value = parseInt(this.value);
    const price = (value / 10) * 0.70;
    
    document.getElementById('storage-display').textContent = value + ' GB';
    document.getElementById('storage-price-display').textContent = '€' + price.toFixed(2) + '/Monat';
    document.getElementById('storage-value').value = value;
    
    const progress = ((value - 10) / (100 - 10)) * 100;
    updateSliderProgress(this, progress);
    updatePricing();
});

// Initialize sliders
document.addEventListener('DOMContentLoaded', function() {
    // Initialize RAM slider (Standardwert: 1 GB)
    const ramSlider = document.getElementById('ram-slider');
    const ramValue = parseInt(ramSlider.value);
    const ramProgress = ((ramValue - 1) / (32 - 1)) * 100;
    updateSliderProgress(ramSlider, ramProgress);
    
    // Initialize CPU slider (Standardwert: 2 Cores)
    const cpuSlider = document.getElementById('cpu-slider');
    const cpuValue = parseInt(cpuSlider.value);
    const cpuProgress = ((cpuValue - 1) / (12 - 1)) * 100;
    updateSliderProgress(cpuSlider, cpuProgress);
    
    // Initialize Storage slider (Standardwert: 10 GB)
    const storageSlider = document.getElementById('storage-slider');
    const storageValue = parseInt(storageSlider.value);
    const storageProgress = ((storageValue - 10) / (100 - 10)) * 100;
    updateSliderProgress(storageSlider, storageProgress);
    
    updatePricing();
});

// Passwort-Generator Funktion
function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    // Mindestens ein Zeichen aus jeder Kategorie
    password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // lowercase
    password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // uppercase
    password += "0123456789"[Math.floor(Math.random() * 10)]; // number
    password += "!@#$%^&*"[Math.floor(Math.random() * 8)]; // special
    
    // Restliche Zeichen zufällig
    for (let i = 4; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // Passwort mischen
    password = password.split('').sort(() => Math.random() - 0.5).join('');
    
    document.getElementById('server_password').value = password;
}
</script>

<?php
renderFooter();
?>