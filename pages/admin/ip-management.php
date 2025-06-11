<?php
session_start();
require_once '../../includes/database.php';
require_once '../../includes/layout.php';

// Admin-Check (vereinfacht - sollte in Produktion erweitert werden)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header('Location: /login');
    exit;
}

$pageTitle = 'IP-Adressen Verwaltung - SpectraHost Admin';
$pageDescription = 'Verwaltung der verfügbaren IP-Adressen für VPS-Services';

$db = Database::getInstance()->getConnection();

$message = '';
$messageType = '';

// IP-Adresse hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_ip':
                $ip = trim($_POST['ip_address']);
                $gateway = trim($_POST['gateway']);
                $subnet = trim($_POST['subnet_mask'] ?? '255.255.255.0');
                
                if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    throw new Exception('Ungültige IP-Adresse');
                }
                
                if (!filter_var($gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    throw new Exception('Ungültige Gateway-Adresse');
                }
                
                $stmt = $db->prepare("INSERT INTO ip_addresses (ip_address, gateway, subnet_mask) VALUES (?, ?, ?)");
                $stmt->execute([$ip, $gateway, $subnet]);
                
                $message = "IP-Adresse {$ip} erfolgreich hinzugefügt";
                $messageType = 'success';
                break;
                
            case 'toggle_availability':
                $ipId = intval($_POST['ip_id']);
                $stmt = $db->prepare("UPDATE ip_addresses SET is_available = NOT is_available WHERE id = ?");
                $stmt->execute([$ipId]);
                
                $message = "Verfügbarkeitsstatus geändert";
                $messageType = 'success';
                break;
                
            case 'delete_ip':
                $ipId = intval($_POST['ip_id']);
                
                // Prüfe ob IP in Verwendung
                $stmt = $db->prepare("SELECT assigned_service_id FROM ip_addresses WHERE id = ? AND assigned_service_id IS NOT NULL");
                $stmt->execute([$ipId]);
                if ($stmt->fetch()) {
                    throw new Exception('IP-Adresse ist einem Service zugewiesen und kann nicht gelöscht werden');
                }
                
                $stmt = $db->prepare("DELETE FROM ip_addresses WHERE id = ?");
                $stmt->execute([$ipId]);
                
                $message = "IP-Adresse gelöscht";
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Alle IP-Adressen abrufen
$stmt = $db->prepare("
    SELECT ip.*, us.server_name 
    FROM ip_addresses ip 
    LEFT JOIN user_services us ON ip.assigned_service_id = us.id 
    ORDER BY INET_ATON(ip.ip_address)
");
$stmt->execute();
$ipAddresses = $stmt->fetchAll();

// Statistiken
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(is_available) as available,
    COUNT(*) - SUM(is_available) as used
    FROM ip_addresses
");
$stmt->execute();
$stats = $stmt->fetch();

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-red-900 to-orange-900 rounded-xl p-8 border border-red-700 shadow-xl">
                <h1 class="text-4xl font-bold text-white mb-3">IP-Adressen Verwaltung</h1>
                <p class="text-gray-200 text-lg">Verwaltung der verfügbaren IP-Adressen für VPS-Services</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-900 border border-green-600 text-green-200' : 'bg-red-900 border border-red-600 text-red-200'; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Statistiken -->
            <div class="lg:col-span-1">
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Statistiken</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Gesamt:</span>
                            <span class="text-white font-semibold"><?php echo $stats['total']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-400">Verfügbar:</span>
                            <span class="text-green-400 font-semibold"><?php echo $stats['available']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-400">Verwendet:</span>
                            <span class="text-red-400 font-semibold"><?php echo $stats['used']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- IP hinzufügen -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-xl font-semibold text-white mb-4">IP hinzufügen</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_ip">
                        
                        <div>
                            <label class="block text-gray-400 text-sm mb-2">IP-Adresse</label>
                            <input type="text" name="ip_address" required
                                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                   placeholder="185.237.96.20">
                        </div>
                        
                        <div>
                            <label class="block text-gray-400 text-sm mb-2">Gateway</label>
                            <input type="text" name="gateway" required
                                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                   placeholder="185.237.96.1">
                        </div>
                        
                        <div>
                            <label class="block text-gray-400 text-sm mb-2">Subnetz</label>
                            <input type="text" name="subnet_mask"
                                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                   placeholder="255.255.255.0" value="255.255.255.0">
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm transition-colors">
                            <i class="fas fa-plus mr-2"></i>Hinzufügen
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- IP-Adressen Liste -->
            <div class="lg:col-span-3">
                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-gray-700">
                        <h3 class="text-xl font-semibold text-white">IP-Adressen</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">IP-Adresse</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Gateway</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Zugewiesen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($ipAddresses as $ip): ?>
                                <tr class="hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-white font-mono"><?php echo $ip['ip_address']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-gray-300 font-mono"><?php echo $ip['gateway']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($ip['is_available']): ?>
                                            <span class="px-2 py-1 text-xs font-semibold bg-green-900 text-green-200 rounded-full">
                                                Verfügbar
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold bg-red-900 text-red-200 rounded-full">
                                                Verwendet
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($ip['server_name']): ?>
                                            <span class="text-gray-300"><?php echo htmlspecialchars($ip['server_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                            <button type="submit" 
                                                    class="text-blue-400 hover:text-blue-300" 
                                                    title="<?php echo $ip['is_available'] ? 'Als verwendet markieren' : 'Als verfügbar markieren'; ?>">
                                                <i class="fas <?php echo $ip['is_available'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <?php if (!$ip['assigned_service_id']): ?>
                                        <form method="POST" class="inline-block" 
                                              onsubmit="return confirm('IP-Adresse wirklich löschen?')">
                                            <input type="hidden" name="action" value="delete_ip">
                                            <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-300" title="Löschen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
renderFooter();
?>