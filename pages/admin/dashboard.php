<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/functions.php';

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

// Statistiken für Dashboard laden
$stats = [];

// Anzahl Benutzer
$stmt = $db->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $stmt->fetch()['count'] ?? 0;

// Anzahl Services
$stmt = $db->query("SELECT COUNT(*) as count FROM user_services");
$stats['services'] = $stmt->fetch()['count'] ?? 0;

// Anzahl Bestellungen
$stmt = $db->query("SELECT COUNT(*) as count FROM user_orders");
$stats['orders'] = $stmt->fetch()['count'] ?? 0;

// Anzahl offene Tickets
$stmt = $db->query("SELECT COUNT(*) as count FROM tickets WHERE status IN ('open', 'pending')");
$stats['tickets'] = $stmt->fetch()['count'] ?? 0;

// Aktuelle Services nach Status
$stmt = $db->query("
    SELECT status, COUNT(*) as count 
    FROM user_services 
    GROUP BY status
");
$serviceStats = [];
while ($row = $stmt->fetch()) {
    $serviceStats[$row['status']] = $row['count'];
}

// Neueste Bestellungen
$stmt = $db->query("
    SELECT uo.*, CONCAT(u.first_name, ' ', u.last_name) as username, st.name as service_name
    FROM user_orders uo
    LEFT JOIN users u ON uo.user_id = u.id
    LEFT JOIN service_types st ON uo.service_type_id = st.id
    ORDER BY uo.created_at DESC
    LIMIT 5
");
$recentOrders = $stmt->fetchAll();

// Aktuelle VPS Services
$stmt = $db->query("
    SELECT us.*, CONCAT(u.first_name, ' ', u.last_name) as username, st.name as service_name
    FROM user_services us
    LEFT JOIN users u ON us.user_id = u.id
    LEFT JOIN service_types st ON us.service_id = st.id
    WHERE st.category = 'vserver'
    ORDER BY us.created_at DESC
    LIMIT 10
");
$vpsServices = $stmt->fetchAll();

$pageTitle = "Admin Dashboard - SpectraHost";
$pageDescription = "Verwaltung und Übersicht für SpectraHost Administrator";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 to-blue-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Admin Dashboard</h1>
                    <p class="text-gray-200">Willkommen im SpectraHost Administrationsbereich</p>
                </div>
                <div class="hidden md:block">
                    <div class="text-right">
                        <div class="text-gray-300 text-sm">Angemeldet als</div>
                        <div class="text-white font-semibold">Administrator</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-8">
                <a href="/admin/dashboard" class="text-white bg-purple-600 px-4 py-2 rounded-lg font-medium">Dashboard</a>
                <a href="/admin/users" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Benutzer</a>
                <a href="/admin/services" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Services</a>
                <a href="/admin/tickets" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Tickets</a>
                <a href="/admin/ip-management" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">IP-Management</a>
            </nav>
        </div>

        <!-- Statistiken Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-800 to-blue-900 rounded-2xl p-6 border border-blue-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['users']); ?></div>
                        <div class="text-blue-200 text-sm">Benutzer</div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-800 to-green-900 rounded-2xl p-6 border border-green-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-server text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['services']); ?></div>
                        <div class="text-green-200 text-sm">Services</div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-800 to-purple-900 rounded-2xl p-6 border border-purple-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-shopping-cart text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['orders']); ?></div>
                        <div class="text-purple-200 text-sm">Bestellungen</div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-800 to-orange-900 rounded-2xl p-6 border border-orange-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-ticket-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['tickets']); ?></div>
                        <div class="text-orange-200 text-sm">Offene Tickets</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Neueste Bestellungen -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-shopping-cart mr-3"></i>Neueste Bestellungen
                </h2>
                
                <div class="space-y-4">
                    <?php if (empty($recentOrders)): ?>
                    <p class="text-gray-400 text-center py-8">Keine Bestellungen vorhanden</p>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-white font-medium"><?php echo htmlspecialchars($order['service_name'] ?? 'Service'); ?></div>
                                <div class="text-gray-400 text-sm">Benutzer: <?php echo htmlspecialchars($order['username']); ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-green-400 font-bold">€<?php echo number_format($order['total_amount'] ?? 0, 2); ?></div>
                                <div class="text-gray-400 text-sm"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- VPS Services -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-server mr-3"></i>VPS Services
                </h2>
                
                <div class="space-y-4">
                    <?php if (empty($vpsServices)): ?>
                    <p class="text-gray-400 text-center py-8">Keine VPS Services vorhanden</p>
                    <?php else: ?>
                    <?php foreach ($vpsServices as $service): ?>
                    <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-white font-medium"><?php echo htmlspecialchars($service['server_name']); ?></div>
                                <div class="text-gray-400 text-sm">
                                    <?php echo htmlspecialchars($service['username']); ?> - 
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center">
                                    <?php if ($service['status'] === 'active'): ?>
                                        <div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
                                        <span class="text-green-200 text-sm">Aktiv</span>
                                    <?php elseif ($service['status'] === 'suspended'): ?>
                                        <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
                                        <span class="text-red-200 text-sm">Gesperrt</span>
                                    <?php else: ?>
                                        <div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div>
                                        <span class="text-yellow-200 text-sm"><?php echo ucfirst($service['status']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-gray-400 text-sm">VMID: <?php echo $service['proxmox_vmid'] ?? 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Service Status Übersicht -->
        <?php if (!empty($serviceStats)): ?>
        <div class="mt-8">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-chart-pie mr-3"></i>Service Status Übersicht
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($serviceStats as $status => $count): ?>
                    <div class="bg-gray-700/50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-white"><?php echo number_format($count); ?></div>
                        <div class="text-gray-400 text-sm capitalize"><?php echo htmlspecialchars($status); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
renderFooter();
?>