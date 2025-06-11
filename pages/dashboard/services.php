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
            <div class="bg-gradient-to-r from-purple-900 to-blue-900 rounded-xl p-8 border border-purple-700 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-3">Service Management</h1>
                        <p class="text-gray-200 text-lg">Verwalten Sie Ihre VPS Server, Webhosting, Gameserver und Domains</p>
                    </div>
                    <div class="hidden md:block">
                        <div class="bg-black/20 rounded-lg p-4">
                            <i class="fas fa-server text-white text-4xl"></i>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex gap-4">
                    <a href="/order" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Neue Services bestellen
                    </a>
                    <button onclick="location.reload()" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i>Aktualisieren
                    </button>
                </div>
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
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 gap-8">
            <?php foreach ($services as $service): ?>
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 overflow-hidden hover:border-purple-500 transition-all duration-300 shadow-lg hover:shadow-xl">
                <!-- Service Header -->
                <div class="p-8 border-b border-gray-700">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-center">
                            <?php if ($service['category'] === 'vserver'): ?>
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mr-6 shadow-lg">
                                    <i class="fas fa-server text-white text-2xl"></i>
                                </div>
                            <?php elseif ($service['category'] === 'webhosting'): ?>
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center mr-6 shadow-lg">
                                    <i class="fas fa-globe text-white text-2xl"></i>
                                </div>
                            <?php elseif ($service['category'] === 'gameserver'): ?>
                                <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center mr-6 shadow-lg">
                                    <i class="fas fa-gamepad text-white text-2xl"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl flex items-center justify-center mr-6 shadow-lg">
                                    <i class="fas fa-link text-white text-2xl"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h3 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                <p class="text-gray-300 text-lg font-medium"><?php echo htmlspecialchars($service['server_name']); ?></p>
                                <p class="text-gray-500 text-sm uppercase tracking-wide"><?php echo ucfirst($service['category']); ?></p>
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
                        <div class="flex items-center">
                            <?php if ($statusColor === 'green'): ?>
                                <div class="w-3 h-3 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                            <?php elseif ($statusColor === 'red'): ?>
                                <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
                            <?php else: ?>
                                <div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div>
                            <?php endif; ?>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold bg-<?php echo $statusColor; ?>-900/50 text-<?php echo $statusColor; ?>-200 border border-<?php echo $statusColor; ?>-600">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="p-8">
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <?php if ($service['category'] === 'vserver' && $service['proxmox_vmid']): ?>
                        <div class="bg-gray-700/50 rounded-lg p-4">
                            <div class="text-gray-400 text-sm font-medium">VM-ID</div>
                            <div class="text-white font-mono text-lg"><?php echo $service['proxmox_vmid']; ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bg-gray-700/50 rounded-lg p-4">
                            <div class="text-gray-400 text-sm font-medium">Läuft bis</div>
                            <div class="text-white font-semibold"><?php echo date('d.m.Y', strtotime($service['expires_at'])); ?></div>
                        </div>
                        
                        <div class="bg-gray-700/50 rounded-lg p-4">
                            <div class="text-gray-400 text-sm font-medium">Erstellt</div>
                            <div class="text-white font-semibold"><?php echo date('d.m.Y', strtotime($service['created_at'])); ?></div>
                        </div>
                        
                        <div class="bg-gray-700/50 rounded-lg p-4">
                            <div class="text-gray-400 text-sm font-medium">Service-ID</div>
                            <div class="text-white font-mono">#<?php echo str_pad($service['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </div>

                    <!-- Service Actions -->
                    <?php if ($service['status'] !== 'terminated'): ?>
                    <div class="space-y-6">
                        <?php if ($service['category'] === 'vserver' && $service['proxmox_vmid']): ?>
                        
                        <!-- VPS Power Controls -->
                        <div class="bg-gray-700/30 rounded-xl p-6 border border-gray-600">
                            <h4 class="text-white font-semibold mb-4 flex items-center">
                                <i class="fas fa-power-off mr-2"></i>Server-Steuerung
                            </h4>
                            <div class="grid grid-cols-3 gap-3">
                                <?php if (isset($vpsStatuses[$service['proxmox_vmid']]) && $vpsStatuses[$service['proxmox_vmid']] === 'stopped'): ?>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="start">
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                                        <i class="fas fa-play mr-2"></i>Start
                                    </button>
                                </form>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-stop mr-2"></i>Stop
                                </button>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-redo mr-2"></i>Restart
                                </button>
                                <?php else: ?>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-play mr-2"></i>Start
                                </button>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="stop">
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                            onclick="return confirm('VPS wirklich stoppen?')">
                                        <i class="fas fa-stop mr-2"></i>Stop
                                    </button>
                                </form>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="restart">
                                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                            onclick="return confirm('VPS wirklich neustarten?')">
                                        <i class="fas fa-redo mr-2"></i>Restart
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Dangerous Actions -->
                        <details class="bg-red-900/20 rounded-xl border border-red-800">
                            <summary class="text-red-400 cursor-pointer p-4 hover:text-red-300 font-medium">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Erweiterte Aktionen
                            </summary>
                            <div class="p-4 border-t border-red-800">
                                <form method="POST" class="w-full" onsubmit="return confirm('WARNUNG: Diese Aktion löscht den VPS unwiderruflich! Sind Sie sicher?');">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="confirm_delete" value="yes">
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                                        <i class="fas fa-trash mr-2"></i>VPS permanent löschen
                                    </button>
                                </form>
                            </div>
                        </details>
                        <?php endif; ?>
                        
                        <!-- Service Details Link -->
                        <?php if ($service['category'] === 'vserver' && $service['proxmox_vmid']): ?>
                        <div class="bg-purple-900/30 rounded-xl p-6 border border-purple-600">
                            <h4 class="text-white font-semibold mb-4 flex items-center">
                                <i class="fas fa-chart-line mr-2"></i>Server-Details
                            </h4>
                            <a href="/dashboard/service-details?id=<?php echo $service['id']; ?>" 
                               class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                                <i class="fas fa-eye mr-2"></i>Detailansicht öffnen
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- General Service Actions -->
                        <div class="bg-gray-700/30 rounded-xl p-6 border border-gray-600">
                            <h4 class="text-white font-semibold mb-4 flex items-center">
                                <i class="fas fa-cog mr-2"></i>Service-Verwaltung
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <?php if ($service['status'] === 'active'): ?>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                                            onclick="return confirm('Service wirklich suspendieren?')">
                                        <i class="fas fa-pause mr-2"></i>Suspendieren
                                    </button>
                                </form>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-play mr-2"></i>Reaktivieren
                                </button>
                                <?php elseif ($service['status'] === 'suspended'): ?>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-pause mr-2"></i>Suspendieren
                                </button>
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="action" value="unsuspend">
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                                        <i class="fas fa-play mr-2"></i>Reaktivieren
                                    </button>
                                </form>
                                <?php else: ?>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-pause mr-2"></i>Suspendieren
                                </button>
                                <button disabled class="w-full bg-gray-600 text-gray-400 px-4 py-3 rounded-lg font-medium cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-play mr-2"></i>Reaktivieren
                                </button>
                                <?php endif; ?>
                            </div>
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