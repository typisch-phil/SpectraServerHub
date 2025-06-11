<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/proxmox-api.php';
require_once __DIR__ . '/../../includes/functions.php';

// Benutzer-Authentifizierung überprüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$userId = $_SESSION['user_id'];

// Service-ID aus URL Parameter
$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$serviceId) {
    header('Location: /dashboard/services');
    exit;
}

// Database connection
$db = Database::getInstance();

// Service-Details laden
$stmt = $db->prepare("
    SELECT us.*, st.name as service_name, st.category 
    FROM user_services us 
    JOIN service_types st ON us.service_id = st.id 
    WHERE us.id = ? AND us.user_id = ?
");
$stmt->execute([$serviceId, $userId]);
$service = $stmt->fetch();

// Sicherstellen, dass order_specifications existiert
if (!isset($service['order_specifications'])) {
    $service['order_specifications'] = null;
}

if (!$service) {
    header('Location: /dashboard/services');
    exit;
}

// Nur für VPS Services
if ($service['category'] !== 'vserver' || !$service['proxmox_vmid']) {
    header('Location: /dashboard/services');
    exit;
}

$actionMessage = '';
$actionType = '';

// POST-Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $proxmox = new ProxmoxAPI();
        
        switch ($action) {
            case 'reset_password':
                $newPassword = bin2hex(random_bytes(8));
                $result = $proxmox->resetPassword($service['proxmox_vmid'], 'root', $newPassword);
                if ($result) {
                    // Passwort in Datenbank speichern
                    $stmt = $db->prepare("UPDATE user_services SET server_password = ? WHERE id = ?");
                    $stmt->execute([$newPassword, $serviceId]);
                    
                    $actionMessage = "Passwort erfolgreich zurückgesetzt. Neues Passwort: $newPassword";
                    $actionType = 'success';
                    $service['server_password'] = $newPassword;
                }
                break;
                
            case 'reinstall_os':
                $osTemplate = $_POST['os_template'] ?? '';
                if (!$osTemplate) {
                    throw new Exception('Bitte wählen Sie ein Betriebssystem aus');
                }
                
                $result = $proxmox->reinstallOS($service['proxmox_vmid'], $osTemplate);
                if ($result) {
                    $actionMessage = "Betriebssystem wird neu installiert. Dies kann einige Minuten dauern.";
                    $actionType = 'success';
                }
                break;
                
            case 'start':
                $result = $proxmox->startVM($service['proxmox_vmid']);
                if ($result) {
                    $actionMessage = "VPS wird gestartet...";
                    $actionType = 'success';
                }
                break;
                
            case 'stop':
                $result = $proxmox->stopVM($service['proxmox_vmid']);
                if ($result) {
                    $actionMessage = "VPS wird gestoppt...";
                    $actionType = 'success';
                }
                break;
                
            case 'restart':
                $result = $proxmox->restartVM($service['proxmox_vmid']);
                if ($result) {
                    $actionMessage = "VPS wird neu gestartet...";
                    $actionType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $actionMessage = "Fehler: " . $e->getMessage();
        $actionType = 'error';
        error_log("Service Details Error: " . $e->getMessage());
    }
}

// VPS-Status und Details laden
$vpsStatus = 'unknown';
$vpsDetails = null;
$vmStats = null;
$serverConfig = null;

try {
    $proxmox = new ProxmoxAPI();
    $vpsStatus = $proxmox->getVMStatus($service['proxmox_vmid']);
    $vpsDetails = $proxmox->getVMConfig($service['proxmox_vmid']);
    $vmStats = $proxmox->getVMStats($service['proxmox_vmid']);
    
    // Server-Konfiguration aus den bestellten Spezifikationen laden
    if (isset($service['order_specifications']) && $service['order_specifications']) {
        $serverConfig = json_decode($service['order_specifications'], true);
    }
} catch (Exception $e) {
    error_log("Proxmox API Error: " . $e->getMessage());
}

// IP-Adresse aus Netzwerk-Konfiguration extrahieren
$serverIP = 'Nicht zugewiesen';
if ($vpsDetails && isset($vpsDetails['net0'])) {
    if (preg_match('/ip=([^,\s]+)/', $vpsDetails['net0'], $matches)) {
        $serverIP = $matches[1];
    }
}

// Formatierungsfunktion für Bytes (lokale Definition)
if (!function_exists('formatBytes')) {
    function formatBytes($size, $precision = 2) {
        if ($size <= 0) return '0 B';
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}



// Verfügbare OS-Templates
$osTemplates = [
    'ubuntu-22.04-standard_22.04-1_amd64.tar.zst' => 'Ubuntu 22.04 LTS',
    'ubuntu-20.04-standard_20.04-1_amd64.tar.zst' => 'Ubuntu 20.04 LTS',
    'debian-11-standard_11.7-1_amd64.tar.zst' => 'Debian 11',
    'debian-12-standard_12.2-1_amd64.tar.zst' => 'Debian 12',
    'centos-8-default_20201210_amd64.tar.xz' => 'CentOS 8',
    'alpine-3.18-default_20230607_amd64.tar.xz' => 'Alpine Linux 3.18'
];

$pageTitle = "Service Details - " . htmlspecialchars($service['service_name']);
$pageDescription = "Detaillierte Verwaltung für " . htmlspecialchars($service['service_name']);

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Navigation -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="/dashboard" class="text-gray-400 hover:text-white">Dashboard</a>
                <i class="fas fa-chevron-right text-gray-600"></i>
                <a href="/dashboard/services" class="text-gray-400 hover:text-white">Services</a>
                <i class="fas fa-chevron-right text-gray-600"></i>
                <span class="text-white"><?php echo htmlspecialchars($service['service_name']); ?></span>
            </nav>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-purple-900 to-blue-900 rounded-xl p-8 border border-purple-700 shadow-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mr-6 shadow-lg">
                            <i class="fas fa-server text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-2"><?php echo htmlspecialchars($service['service_name']); ?></h1>
                            <p class="text-gray-200 text-lg"><?php echo htmlspecialchars($service['server_name']); ?></p>
                            <div class="flex items-center mt-2">
                                <?php if ($vpsStatus === 'running'): ?>
                                    <div class="w-3 h-3 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                                    <span class="text-green-200 font-medium">Online</span>
                                <?php elseif ($vpsStatus === 'stopped'): ?>
                                    <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
                                    <span class="text-red-200 font-medium">Offline</span>
                                <?php else: ?>
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div>
                                    <span class="text-yellow-200 font-medium">Unbekannt</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="text-right">
                            <div class="text-gray-300 text-sm">Service-ID</div>
                            <div class="text-white font-mono text-lg">#<?php echo str_pad($service['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <div class="text-gray-300 text-sm mt-2">VM-ID</div>
                            <div class="text-white font-mono text-lg"><?php echo $service['proxmox_vmid']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Messages -->
        <?php if ($actionMessage): ?>
        <div class="mb-6 bg-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-800 border border-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-600 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-<?php echo $actionType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-3 text-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-200"></i>
                <span class="text-<?php echo $actionType === 'success' ? 'green' : 'red'; ?>-200"><?php echo htmlspecialchars($actionMessage); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Server Information -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Server Stats -->
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-8">
                    <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-chart-line mr-3"></i>Server-Auslastung
                    </h2>
                    
                    <?php if ($vmStats && $vpsStatus === 'running'): ?>
                    <div class="grid grid-cols-2 gap-6">
                        <!-- CPU Usage -->
                        <div class="bg-gray-700/50 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-white font-semibold">CPU</h3>
                                <span class="text-purple-400 font-bold"><?php echo number_format($vmStats['cpu'] * 100, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-600 rounded-full h-3">
                                <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-3 rounded-full transition-all duration-300" 
                                     style="width: <?php echo ($vmStats['cpu'] * 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Memory Usage -->
                        <div class="bg-gray-700/50 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-white font-semibold">RAM</h3>
                                <span class="text-blue-400 font-bold"><?php echo number_format(($vmStats['mem'] / $vmStats['maxmem']) * 100, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-600 rounded-full h-3">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300" 
                                     style="width: <?php echo (($vmStats['mem'] / $vmStats['maxmem']) * 100); ?>%"></div>
                            </div>
                            <div class="text-gray-400 text-xs mt-2">
                                <?php echo formatBytes($vmStats['mem']); ?> / <?php echo formatBytes($vmStats['maxmem']); ?>
                            </div>
                        </div>
                        
                        <!-- Disk Usage -->
                        <div class="bg-gray-700/50 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-white font-semibold">Festplatte</h3>
                                <span class="text-green-400 font-bold"><?php echo number_format(($vmStats['disk'] / $vmStats['maxdisk']) * 100, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-600 rounded-full h-3">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" 
                                     style="width: <?php echo (($vmStats['disk'] / $vmStats['maxdisk']) * 100); ?>%"></div>
                            </div>
                            <div class="text-gray-400 text-xs mt-2">
                                <?php echo formatBytes($vmStats['disk']); ?> / <?php echo formatBytes($vmStats['maxdisk']); ?>
                            </div>
                        </div>
                        
                        <!-- Network -->
                        <div class="bg-gray-700/50 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-white font-semibold">Netzwerk</h3>
                                <span class="text-orange-400 font-bold">
                                    <i class="fas fa-arrow-down mr-1"></i><?php echo formatBytes($vmStats['netin']); ?>
                                    <i class="fas fa-arrow-up ml-2 mr-1"></i><?php echo formatBytes($vmStats['netout']); ?>
                                </span>
                            </div>
                            <div class="text-gray-400 text-xs">
                                Eingehend: <?php echo formatBytes($vmStats['netin']); ?><br>
                                Ausgehend: <?php echo formatBytes($vmStats['netout']); ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-server text-gray-600 text-6xl mb-4"></i>
                        <p class="text-gray-400 text-lg">Server ist offline oder Daten nicht verfügbar</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Server Configuration -->
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-8">
                    <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-cog mr-3"></i>Server-Konfiguration
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- IP-Adresse -->
                        <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-globe text-blue-400 mr-3"></i>
                                <div class="text-gray-400 text-sm font-medium">IP-Adresse</div>
                            </div>
                            <div class="text-white font-mono text-lg"><?php echo htmlspecialchars($serverIP); ?></div>
                        </div>
                        
                        <!-- CPU Kerne -->
                        <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-microchip text-purple-400 mr-3"></i>
                                <div class="text-gray-400 text-sm font-medium">CPU Kerne</div>
                            </div>
                            <div class="text-white font-semibold text-lg">
                                <?php 
                                if ($vmStats && isset($vmStats['cpus'])) {
                                    echo $vmStats['cpus'] . ' Kerne';
                                } elseif ($serverConfig && isset($serverConfig['cpu'])) {
                                    echo $serverConfig['cpu'] . ' Kerne';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Arbeitsspeicher -->
                        <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-memory text-blue-400 mr-3"></i>
                                <div class="text-gray-400 text-sm font-medium">Arbeitsspeicher</div>
                            </div>
                            <div class="text-white font-semibold text-lg">
                                <?php 
                                if ($vmStats && isset($vmStats['maxmem'])) {
                                    echo formatBytes($vmStats['maxmem']);
                                } elseif ($serverConfig && isset($serverConfig['ram'])) {
                                    echo $serverConfig['ram'] . ' GB';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- SSD Speicher -->
                        <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-hdd text-green-400 mr-3"></i>
                                <div class="text-gray-400 text-sm font-medium">SSD Speicher</div>
                            </div>
                            <div class="text-white font-semibold text-lg">
                                <?php 
                                if ($vmStats && isset($vmStats['maxdisk'])) {
                                    echo formatBytes($vmStats['maxdisk']);
                                } elseif ($serverConfig && isset($serverConfig['storage'])) {
                                    echo $serverConfig['storage'] . ' GB';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Betriebssystem -->
                        <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600">
                            <div class="flex items-center mb-3">
                                <i class="fab fa-linux text-orange-400 mr-3"></i>
                                <div class="text-gray-400 text-sm font-medium">Betriebssystem</div>
                            </div>
                            <div class="text-white font-semibold text-lg">
                                <?php 
                                if ($vpsDetails && isset($vpsDetails['ostype'])) {
                                    echo ucfirst($vpsDetails['ostype']);
                                } else {
                                    echo 'Debian 12';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- VM-ID -->
                        <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-server text-gray-400 mr-3"></i>
                                <div class="text-gray-400 text-sm font-medium">VM-ID</div>
                            </div>
                            <div class="text-white font-mono text-lg"><?php echo $service['proxmox_vmid']; ?></div>
                        </div>
                </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="space-y-8">
                
                <!-- Server Access -->
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-key mr-3"></i>Server-Zugang
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-700/50 rounded-lg p-4">
                            <div class="text-gray-400 text-sm mb-1">Benutzername</div>
                            <div class="text-white font-mono">root</div>
                        </div>
                        
                        <div class="bg-gray-700/50 rounded-lg p-4">
                            <div class="text-gray-400 text-sm mb-1">Passwort</div>
                            <div class="flex items-center justify-between">
                                <span class="text-white font-mono" id="password-display">
                                    <?php echo isset($service['server_password']) && $service['server_password'] ? str_repeat('•', 12) : 'Nicht gesetzt'; ?>
                                </span>
                                <button onclick="togglePassword()" class="text-purple-400 hover:text-purple-300">
                                    <i class="fas fa-eye" id="password-toggle"></i>
                                </button>
                            </div>
                            <div class="hidden text-white font-mono mt-2" id="password-actual">
                                <?php echo htmlspecialchars($service['server_password'] ?? ''); ?>
                            </div>
                        </div>
                        
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="reset_password">
                            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                    onclick="return confirm('Neues Passwort generieren?')">
                                <i class="fas fa-sync-alt mr-2"></i>Passwort zurücksetzen
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Power Controls -->
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-power-off mr-3"></i>Server-Steuerung
                    </h3>
                    
                    <div class="space-y-3">
                        <?php if ($vpsStatus === 'stopped'): ?>
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="start">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                                <i class="fas fa-play mr-2"></i>Server starten
                            </button>
                        </form>
                        <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                            <i class="fas fa-stop mr-2"></i>Server stoppen
                        </button>
                        <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                            <i class="fas fa-redo mr-2"></i>Neustarten
                        </button>
                        <?php else: ?>
                        <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                            <i class="fas fa-play mr-2"></i>Server starten
                        </button>
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="stop">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                    onclick="return confirm('Server wirklich stoppen?')">
                                <i class="fas fa-stop mr-2"></i>Server stoppen
                            </button>
                        </form>
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="restart">
                            <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                    onclick="return confirm('Server wirklich neustarten?')">
                                <i class="fas fa-redo mr-2"></i>Neustarten
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- OS Reinstall -->
                <div class="bg-gradient-to-br from-red-900/30 to-red-800/30 rounded-2xl border-2 border-red-700 p-6">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-download mr-3"></i>Betriebssystem
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="reinstall_os">
                        
                        <div>
                            <label class="block text-white font-medium mb-2">Neues Betriebssystem wählen:</label>
                            <select name="os_template" required class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">-- Betriebssystem auswählen --</option>
                                <?php foreach ($osTemplates as $template => $name): ?>
                                <option value="<?php echo htmlspecialchars($template); ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="bg-red-900/20 border border-red-700 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-red-400 mt-1 mr-3"></i>
                                <div class="text-red-200 text-sm">
                                    <strong>Warnung:</strong> Diese Aktion löscht alle Daten auf dem Server unwiderruflich! 
                                    Stellen Sie sicher, dass Sie ein Backup haben.
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                onclick="return confirm('WARNUNG: Alle Daten werden gelöscht! Sind Sie sicher?')">
                            <i class="fas fa-download mr-2"></i>OS neu installieren
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const display = document.getElementById('password-display');
    const actual = document.getElementById('password-actual');
    const toggle = document.getElementById('password-toggle');
    
    if (actual.classList.contains('hidden')) {
        display.classList.add('hidden');
        actual.classList.remove('hidden');
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        display.classList.remove('hidden');
        actual.classList.add('hidden');
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// Auto-refresh stats every 30 seconds
<?php if ($vpsStatus === 'running'): ?>
setInterval(function() {
    location.reload();
}, 30000);
<?php endif; ?>
</script>

<?php
renderFooter();
?>