<?php
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/proxmox-api.php';

// Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$pageTitle = 'Meine Services - SpectraHost Dashboard';
$pageDescription = 'Verwalten Sie Ihre VPS Server, Webhosting, Gameserver und Domains.';

// Service-Aktionen verarbeiten
$actionMessage = '';
$actionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = Database::getInstance();
        $serviceId = intval($_POST['service_id']);
        $action = $_POST['action'];
        
        // Service-Details abrufen
        $stmt = $db->prepare("
            SELECT us.*, st.name as service_name, st.category, st.specifications 
            FROM user_services us 
            JOIN service_types st ON us.service_id = st.id 
            WHERE us.id = ? AND us.user_id = ?
        ");
        $stmt->execute([$serviceId, $_SESSION['user_id']]);
        $service = $stmt->fetch();
        
        if (!$service) {
            throw new Exception('Service nicht gefunden.');
        }
        
        // Proxmox-Aktionen für VPS
        if ($service['category'] === 'vserver' && $service['proxmox_vmid']) {
            $proxmox = new ProxmoxAPI();
            $vmid = $service['proxmox_vmid'];
            
            switch ($action) {
                case 'start':
                    $result = $proxmox->startVM($vmid, 'lxc');
                    if ($result) {
                        $actionMessage = "VPS {$service['server_name']} wurde gestartet.";
                    }
                    break;
                    
                case 'stop':
                    $result = $proxmox->stopVM($vmid, 'lxc');
                    if ($result) {
                        $actionMessage = "VPS {$service['server_name']} wurde gestoppt.";
                    }
                    break;
                    
                case 'restart':
                    $proxmox->stopVM($vmid, 'lxc');
                    sleep(3);
                    $result = $proxmox->startVM($vmid, 'lxc');
                    if ($result) {
                        $actionMessage = "VPS {$service['server_name']} wurde neugestartet.";
                    }
                    break;
                    
                case 'delete':
                    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
                        $result = $proxmox->deleteVM($vmid, 'lxc');
                        if ($result) {
                            // Service als terminated markieren
                            $stmt = $db->prepare("UPDATE user_services SET status = 'terminated' WHERE id = ?");
                            $stmt->execute([$serviceId]);
                            $actionMessage = "VPS {$service['server_name']} wurde gelöscht.";
                        }
                    } else {
                        $actionError = 'Löschen nicht bestätigt. Service wurde nicht gelöscht.';
                    }
                    break;
                    
                default:
                    throw new Exception('Unbekannte Aktion.');
            }
        } elseif ($action === 'suspend') {
            // Service suspendieren
            $stmt = $db->prepare("UPDATE user_services SET status = 'suspended' WHERE id = ?");
            $stmt->execute([$serviceId]);
            $actionMessage = "Service {$service['server_name']} wurde suspendiert.";
            
        } elseif ($action === 'unsuspend') {
            // Service reaktivieren
            $stmt = $db->prepare("UPDATE user_services SET status = 'active' WHERE id = ?");
            $stmt->execute([$serviceId]);
            $actionMessage = "Service {$service['server_name']} wurde reaktiviert.";
        }
        
    } catch (Exception $e) {
        $actionError = $e->getMessage();
        error_log("Service Management Error: " . $e->getMessage());
    }
}

// Services abrufen
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT us.*, st.name as service_name, st.category, st.specifications, st.monthly_price 
    FROM user_services us 
    JOIN service_types st ON us.service_id = st.id 
    WHERE us.user_id = ? 
    ORDER BY us.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$services = $stmt->fetchAll();

// VPS-Status für Proxmox-Services abrufen
$vpsStatuses = [];
if (!empty($services)) {
    try {
        $proxmox = new ProxmoxAPI();
        foreach ($services as $service) {
            if ($service['category'] === 'vserver' && $service['proxmox_vmid']) {
                try {
                    $status = $proxmox->getVMStatus($service['proxmox_vmid'], 'lxc');
                    $vpsStatuses[$service['proxmox_vmid']] = $status['data']['status'] ?? 'unknown';
                } catch (Exception $e) {
                    $vpsStatuses[$service['proxmox_vmid']] = 'error';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Proxmox Status Error: " . $e->getMessage());
    }
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Dashboard Header -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-6 border border-gray-600">
                <h1 class="text-3xl font-bold text-white mb-2">Meine Services</h1>
                <p class="text-gray-300">Verwalten Sie Ihre VPS Server, Webhosting, Gameserver und Domains</p>
            </div>
        </div>

        <!-- Action Messages -->
        <?php if ($actionMessage): ?>
        <div class="mb-6 bg-green-800 border border-green-600 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-400 mr-3"></i>
                <span class="text-green-200"><?php echo htmlspecialchars($actionMessage); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($actionError): ?>
        <div class="mb-6 bg-red-800 border border-red-600 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                <span class="text-red-200"><?php echo htmlspecialchars($actionError); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Services Overview -->
        <?php if (empty($services)): ?>
        <div class="bg-gray-800 rounded-xl p-8 border border-gray-700 text-center">
            <i class="fas fa-server text-gray-500 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-white mb-4">Keine Services gefunden</h3>
            <p class="text-gray-300 mb-6">Sie haben noch keine Services bestellt. Entdecken Sie unsere Hosting-Angebote.</p>
            <a href="/order" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Services bestellen
            </a>
        </div>
        <?php else: ?>
        
        <!-- Services Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($services as $service): ?>
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden hover:border-gray-600 transition-colors">
                <!-- Service Header -->
                <div class="p-6 border-b border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <?php if ($service['category'] === 'vserver'): ?>
                                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-server text-white text-xl"></i>
                                </div>
                            <?php elseif ($service['category'] === 'webhosting'): ?>
                                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-globe text-white text-xl"></i>
                                </div>
                            <?php elseif ($service['category'] === 'gameserver'): ?>
                                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-gamepad text-white text-xl"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-link text-white text-xl"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($service['server_name']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <?php
                        $statusColor = 'gray';
                        $statusText = $service['status'];
                        
                        if ($service['category'] === 'vserver' && $service['proxmox_vmid'] && isset($vpsStatuses[$service['proxmox_vmid']])) {
                            $proxmoxStatus = $vpsStatuses[$service['proxmox_vmid']];
                            if ($proxmoxStatus === 'running') {
                                $statusColor = 'green';
                                $statusText = 'Online';
                            } elseif ($proxmoxStatus === 'stopped') {
                                $statusColor = 'red';
                                $statusText = 'Offline';
                            } else {
                                $statusColor = 'yellow';
                                $statusText = ucfirst($proxmoxStatus);
                            }
                        } elseif ($service['status'] === 'active') {
                            $statusColor = 'green';
                            $statusText = 'Aktiv';
                        } elseif ($service['status'] === 'suspended') {
                            $statusColor = 'red';
                            $statusText = 'Suspendiert';
                        } elseif ($service['status'] === 'terminated') {
                            $statusColor = 'gray';
                            $statusText = 'Beendet';
                        }
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-<?php echo $statusColor; ?>-800 text-<?php echo $statusColor; ?>-200 border border-<?php echo $statusColor; ?>-600">
                            <?php echo $statusText; ?>
                        </span>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <?php if ($service['category'] === 'vserver' && $service['proxmox_vmid']): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">VM-ID:</span>
                            <span class="text-white font-mono"><?php echo $service['proxmox_vmid']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Läuft bis:</span>
                            <span class="text-white"><?php echo date('d.m.Y', strtotime($service['expires_at'])); ?></span>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Erstellt:</span>
                            <span class="text-white"><?php echo date('d.m.Y', strtotime($service['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Service Actions -->
                    <?php if ($service['status'] !== 'terminated'): ?>
                    <div class="space-y-2">
                        <?php if ($service['category'] === 'vserver' && $service['proxmox_vmid']): ?>
                        <!-- VPS Controls -->
                        <div class="flex flex-wrap gap-2">
                            <?php if (isset($vpsStatuses[$service['proxmox_vmid']]) && $vpsStatuses[$service['proxmox_vmid']] === 'stopped'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="action" value="start">
                                <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                    <i class="fas fa-play mr-1"></i>Start
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="action" value="stop">
                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                    <i class="fas fa-stop mr-1"></i>Stop
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="inline">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="action" value="restart">
                                <button type="submit" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">
                                    <i class="fas fa-redo mr-1"></i>Restart
                                </button>
                            </form>
                        </div>
                        
                        <!-- Dangerous Actions -->
                        <details class="mt-4">
                            <summary class="text-red-400 cursor-pointer text-sm hover:text-red-300">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Erweiterte Aktionen
                            </summary>
                            <div class="mt-2 p-3 bg-red-900/20 border border-red-800 rounded">
                                <form method="POST" class="inline" onsubmit="return confirm('WARNUNG: Diese Aktion löscht den VPS unwiderruflich! Sind Sie sicher?');">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="confirm_delete" value="yes">
                                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                        <i class="fas fa-trash mr-1"></i>VPS löschen
                                    </button>
                                </form>
                            </div>
                        </details>
                        <?php endif; ?>
                        
                        <!-- General Service Actions -->
                        <div class="flex gap-2 mt-4">
                            <?php if ($service['status'] === 'active'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button type="submit" class="bg-orange-600 text-white px-3 py-1 rounded text-sm hover:bg-orange-700">
                                    <i class="fas fa-pause mr-1"></i>Suspendieren
                                </button>
                            </form>
                            <?php elseif ($service['status'] === 'suspended'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="action" value="unsuspend">
                                <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                    <i class="fas fa-play mr-1"></i>Reaktivieren
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds for VPS status updates
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php renderFooter(); ?>