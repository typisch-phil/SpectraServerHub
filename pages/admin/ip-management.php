<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Admin-Authentifizierung überprüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Überprüfung ob Benutzer Admin-Rechte hat
$db = Database::getInstance();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

// IP-Adressen und Services laden
$ipAddresses = [];
$services = [];

try {
    // Alle IP-Adressen aus user_services laden
    $stmt = $db->query("
        SELECT us.*, CONCAT(u.first_name, ' ', u.last_name) as username, st.name as service_name
        FROM user_services us
        LEFT JOIN users u ON us.user_id = u.id
        LEFT JOIN service_types st ON us.service_id = st.id
        WHERE us.ip_address IS NOT NULL AND us.ip_address != ''
        ORDER BY INET_ATON(us.ip_address)
    ");
    $ipAddresses = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading IP addresses: " . $e->getMessage());
}

$pageTitle = "IP-Management - SpectraHost Admin";
$pageDescription = "Verwaltung von IP-Adressen und Zuweisungen";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 to-blue-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">IP-Management</h1>
                    <p class="text-gray-200">Verwaltung von IP-Adressen und Zuweisungen</p>
                </div>
                <div class="hidden md:block">
                    <div class="text-right">
                        <div class="text-gray-300 text-sm">Gesamt IP-Adressen</div>
                        <div class="text-white font-semibold text-2xl"><?php echo count($ipAddresses); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-8">
                <a href="/admin/dashboard" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Dashboard</a>
                <a href="/admin/users" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Benutzer</a>
                <a href="/admin/services" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Services</a>
                <a href="/admin/tickets" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Tickets</a>
                <a href="/admin/ip-management" class="text-white bg-purple-600 px-4 py-2 rounded-lg font-medium">IP-Management</a>
            </nav>
        </div>

        <!-- IP-Adressen Übersicht -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-network-wired mr-3"></i>IP-Adressen Übersicht
                </h2>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Neue IP hinzufügen
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">IP-Adresse</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Service</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Benutzer</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Status</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">VMID</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ipAddresses)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400">
                                Keine IP-Adressen vorhanden
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($ipAddresses as $ip): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-700/30">
                            <td class="py-4 px-4">
                                <div class="text-white font-medium"><?php echo htmlspecialchars($ip['ip_address']); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-300"><?php echo htmlspecialchars($ip['service_name'] ?? 'N/A'); ?></div>
                                <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($ip['server_name']); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-300"><?php echo htmlspecialchars($ip['username']); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($ip['status'] === 'active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-200">
                                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                        Aktiv
                                    </span>
                                <?php elseif ($ip['status'] === 'suspended'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900 text-red-200">
                                        <div class="w-2 h-2 bg-red-400 rounded-full mr-2"></div>
                                        Gesperrt
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900 text-yellow-200">
                                        <div class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></div>
                                        <?php echo ucfirst($ip['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-300"><?php echo $ip['proxmox_vmid'] ?? 'N/A'; ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-400 hover:text-blue-300 p-1" title="Bearbeiten">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="text-red-400 hover:text-red-300 p-1" title="Löschen">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- IP-Pool Statistiken -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-blue-800 to-blue-900 rounded-2xl p-6 border border-blue-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-server text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo count(array_filter($ipAddresses, fn($ip) => $ip['status'] === 'active')); ?></div>
                        <div class="text-blue-200 text-sm">Aktive IPs</div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-800 to-green-900 rounded-2xl p-6 border border-green-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo count($ipAddresses); ?></div>
                        <div class="text-green-200 text-sm">Gesamt zugewiesen</div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-800 to-orange-900 rounded-2xl p-6 border border-orange-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo count(array_filter($ipAddresses, fn($ip) => $ip['status'] !== 'active')); ?></div>
                        <div class="text-orange-200 text-sm">Problematisch</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
renderFooter();
?>