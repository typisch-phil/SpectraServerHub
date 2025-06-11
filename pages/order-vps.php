<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/proxmox-api.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle = 'VPS Server bestellen - SpectraHost';
$pageDescription = 'Bestellen Sie Ihren VPS Server und lassen Sie ihn automatisch auf unserem Proxmox-Cluster erstellen.';

// Get VPS packages from database
$vpsPackages = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'vserver' ORDER BY id ASC");
    $stmt->execute();
    $vpsPackages = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in order-vps.php: " . $e->getMessage());
    $vpsPackages = [];
}

// Handle form submission
$orderSuccess = false;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $packageId = intval($_POST['package_id']);
        $hostname = trim($_POST['hostname']);
        $osTemplate = $_POST['os_template'];
        $rootPassword = $_POST['root_password'];
        
        // Validate input
        if (empty($hostname) || !preg_match('/^[a-z0-9-]+$/', $hostname)) {
            throw new Exception('Hostname ist ungültig. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.');
        }
        
        if (strlen($rootPassword) < 8) {
            throw new Exception('Root-Passwort muss mindestens 8 Zeichen lang sein.');
        }
        
        // Get package details
        $stmt = $db->prepare("SELECT * FROM service_types WHERE id = ? AND category = 'vserver'");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            throw new Exception('Gewähltes Paket nicht gefunden.');
        }
        
        // Parse package specs
        $specs = json_decode($package['specifications'] ?? '{}', true);
        $memory = $specs['memory'] ?? 2048;
        $cores = $specs['cores'] ?? 2;
        $disk = $specs['disk'] ?? 25;
        $monthlyPrice = $package['monthly_price'] ?? 9.99;
        
        // Initialize Proxmox API
        $proxmox = new ProxmoxAPI();
        
        // Get next available VMID
        $vmid = $proxmox->getNextVMID();
        if (!$vmid) {
            throw new Exception('Fehler beim Generieren der VM-ID.');
        }
        
        // Create order in database first
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, service_id, total_amount, billing_period, status, notes, created_at) 
            VALUES (?, ?, ?, 'monthly', 'pending', ?, NOW())
        ");
        $orderNotes = json_encode([
            'service_type' => 'vps',
            'vmid' => $vmid,
            'hostname' => $hostname,
            'memory' => $memory,
            'cores' => $cores,
            'disk' => $disk,
            'os_template' => $osTemplate
        ]);
        $stmt->execute([$_SESSION['user_id'], $packageId, $monthlyPrice, $orderNotes]);
        $orderId = $db->lastInsertId();
        
        // Create container in Proxmox
        $containerConfig = [
            'hostname' => $hostname,
            'memory' => $memory,
            'cores' => $cores,
            'disk' => $disk,
            'template' => $osTemplate,
            'password' => $rootPassword
        ];
        
        $result = $proxmox->createContainer($vmid, $containerConfig);
        
        if ($result) {
            // Update order status to paid (VPS is active)
            $stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
            $stmt->execute([$orderId]);
            
            // Create service entry
            $stmt = $db->prepare("
                INSERT INTO services (user_id, name, service_type_id, monthly_price, server_ip, type, description, price, cpu_cores, memory_gb, storage_gb, status, created_at) 
                VALUES (?, ?, ?, ?, NULL, 'vps', ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $hostname, 
                $packageId, 
                $monthlyPrice, 
                "VPS Server: {$hostname}",
                $monthlyPrice,
                $cores,
                ($memory / 1024), // Convert MB to GB
                $disk
            ]);
            
            $orderSuccess = true;
            $_SESSION['order_vmid'] = $vmid;
            $_SESSION['order_hostname'] = $hostname;
        } else {
            throw new Exception('Fehler beim Erstellen des VPS auf dem Proxmox-Cluster.');
        }
        
    } catch (Exception $e) {
        $orderError = $e->getMessage();
        error_log("VPS Order Error: " . $e->getMessage());
        
        // Update order status to failed if order was created
        if (isset($orderId)) {
            $stmt = $db->prepare("UPDATE orders SET status = 'failed', notes = ? WHERE id = ?");
            $errorNotes = json_encode(['error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
            $stmt->execute([$errorNotes, $orderId]);
        }
    }
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                VPS Server bestellen
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Wählen Sie Ihr VPS-Paket und lassen Sie Ihren Server automatisch auf unserem Proxmox-Cluster erstellen.
            </p>
        </div>
    </div>
</div>

<?php if ($orderSuccess): ?>
<!-- Success Message -->
<div class="py-16 bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-green-800 border border-green-600 rounded-lg p-8 text-center">
            <i class="fas fa-check-circle text-green-400 text-6xl mb-6"></i>
            <h2 class="text-2xl font-bold text-white mb-4">VPS erfolgreich bestellt!</h2>
            <p class="text-green-200 mb-6">
                Ihr VPS wurde erfolgreich auf unserem Proxmox-Cluster erstellt und ist bereit für die Nutzung.
            </p>
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div>
                        <span class="text-gray-400">VM-ID:</span>
                        <span class="text-white font-mono"><?php echo htmlspecialchars($_SESSION['order_vmid']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Hostname:</span>
                        <span class="text-white font-mono"><?php echo htmlspecialchars($_SESSION['order_hostname']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Status:</span>
                        <span class="text-green-400">Aktiv</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Erstellt:</span>
                        <span class="text-white"><?php echo date('d.m.Y H:i'); ?></span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard/services" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-server mr-2"></i>Zu meinen Services
                </a>
                <a href="/dashboard" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Order Form -->
<div class="py-16 bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($orderError): ?>
        <div class="bg-red-800 border border-red-600 rounded-lg p-4 mb-8">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                <span class="text-red-200"><?php echo htmlspecialchars($orderError); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <!-- Package Selection -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">1. VPS-Paket wählen</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if (!empty($vpsPackages)): ?>
                        <?php foreach ($vpsPackages as $package): ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="package_id" value="<?php echo $package['id']; ?>" class="sr-only peer" required>
                            <div class="border-2 border-gray-600 peer-checked:border-purple-500 rounded-lg p-6 hover:border-gray-500 transition-colors bg-gray-700">
                                <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($package['name']); ?></h3>
                                <p class="text-gray-300 text-sm mt-1"><?php echo htmlspecialchars($package['description'] ?? ''); ?></p>
                                <div class="mt-4">
                                    <span class="text-2xl font-bold text-white">€<?php echo number_format(floatval($package['monthly_price'] ?? 0), 2); ?></span>
                                    <span class="text-gray-300">/Monat</span>
                                </div>
                                <?php if (!empty($package['features'])): ?>
                                <ul class="mt-4 space-y-2 text-sm text-gray-300">
                                    <?php 
                                    $features = is_string($package['features']) ? json_decode($package['features'], true) : $package['features'];
                                    if (is_array($features)):
                                        foreach ($features as $feature): ?>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; endif; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback packages -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="package_id" value="1" class="sr-only peer" required>
                            <div class="border-2 border-gray-600 peer-checked:border-purple-500 rounded-lg p-6 hover:border-gray-500 transition-colors bg-gray-700">
                                <h3 class="text-lg font-semibold text-white">VPS Starter</h3>
                                <p class="text-gray-300 text-sm mt-1">Ideal für Einsteiger</p>
                                <div class="mt-4">
                                    <span class="text-2xl font-bold text-white">€9,99</span>
                                    <span class="text-gray-300">/Monat</span>
                                </div>
                                <ul class="mt-4 space-y-2 text-sm text-gray-300">
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>2 GB RAM</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>2 vCPU Cores</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>25 GB SSD</li>
                                </ul>
                            </div>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Server Configuration -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">2. Server-Konfiguration</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="hostname" class="block text-sm font-medium text-gray-300 mb-2">
                            Hostname <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="hostname" 
                               name="hostname" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                               placeholder="mein-vps"
                               pattern="[a-z0-9-]+"
                               title="Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt"
                               required>
                        <p class="mt-1 text-xs text-gray-400">Nur Kleinbuchstaben, Zahlen und Bindestriche</p>
                    </div>
                    
                    <div>
                        <label for="os_template" class="block text-sm font-medium text-gray-300 mb-2">
                            Betriebssystem <span class="text-red-400">*</span>
                        </label>
                        <select id="os_template" 
                                name="os_template" 
                                class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                required>
                            <option value="">Bitte wählen...</option>
                            <option value="local:vztmpl/ubuntu-22.04-standard_22.04-1_amd64.tar.zst">Ubuntu 22.04 LTS</option>
                            <option value="local:vztmpl/ubuntu-20.04-standard_20.04-1_amd64.tar.zst">Ubuntu 20.04 LTS</option>
                            <option value="local:vztmpl/debian-12-standard_12.2-1_amd64.tar.zst">Debian 12</option>
                            <option value="local:vztmpl/debian-11-standard_11.7-1_amd64.tar.zst">Debian 11</option>
                            <option value="local:vztmpl/centos-8-default_20201210_amd64.tar.xz">CentOS 8</option>
                            <option value="local:vztmpl/alpine-3.18-default_20230607_amd64.tar.xz">Alpine Linux 3.18</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6">
                    <label for="root_password" class="block text-sm font-medium text-gray-300 mb-2">
                        Root-Passwort <span class="text-red-400">*</span>
                    </label>
                    <input type="password" 
                           id="root_password" 
                           name="root_password" 
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                           placeholder="Sicheres Passwort eingeben"
                           minlength="8"
                           required>
                    <p class="mt-1 text-xs text-gray-400">Mindestens 8 Zeichen, empfohlen: Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen</p>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">3. Bestellung abschließen</h2>
                
                <div class="bg-gray-700 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Bestellübersicht</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-300">Gewähltes Paket:</span>
                            <span class="text-white" id="selected-package">Bitte wählen Sie ein Paket</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Einrichtung:</span>
                            <span class="text-green-400">Kostenlos</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Bereitstellung:</span>
                            <span class="text-blue-400">Sofort</span>
                        </div>
                        <hr class="border-gray-600">
                        <div class="flex justify-between text-lg font-semibold">
                            <span class="text-white">Gesamt monatlich:</span>
                            <span class="text-white" id="total-price">€0,00</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-900 border border-blue-700 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                        <div class="text-blue-200 text-sm">
                            <p class="font-semibold mb-1">Automatische Bereitstellung</p>
                            <p>Ihr VPS wird automatisch auf unserem Proxmox-Cluster erstellt und ist innerhalb weniger Minuten einsatzbereit. Sie erhalten sofort Zugang zu Ihrem Server.</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        name="place_order"
                        class="w-full bg-purple-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                    <i class="fas fa-rocket mr-2"></i>VPS jetzt bestellen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Package selection handling
document.addEventListener('DOMContentLoaded', function() {
    const packageRadios = document.querySelectorAll('input[name="package_id"]');
    const selectedPackageSpan = document.getElementById('selected-package');
    const totalPriceSpan = document.getElementById('total-price');
    
    packageRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const label = this.closest('label');
            const packageName = label.querySelector('h3').textContent;
            const priceElement = label.querySelector('.text-2xl');
            const price = priceElement ? priceElement.textContent : '€0,00';
            
            selectedPackageSpan.textContent = packageName;
            totalPriceSpan.textContent = price;
        });
    });
    
    // Hostname validation
    const hostnameInput = document.getElementById('hostname');
    hostnameInput.addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    });
});
</script>
<?php endif; ?>

<?php renderFooter(); ?>