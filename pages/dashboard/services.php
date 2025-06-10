<?php
// Dark Version Services Dashboard
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];
$db = Database::getInstance();

// Services aus der Datenbank laden
try {
    // Alle Services des Benutzers
    $stmt = $db->prepare("
        SELECT s.*, st.name as service_type_name, st.category, st.icon 
        FROM services s 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Service-Statistiken
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM services WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $service_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (Exception $e) {
    error_log("Services data error: " . $e->getMessage());
    $services = [];
    $service_stats = [];
}

renderHeader('Meine Services - Dashboard');
?>

<div class="min-h-screen bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-gray-800 shadow-lg border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/products" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Service bestellen
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Meine Services</h1>
                    <p class="mt-2 text-gray-400">Verwalten Sie Ihre aktiven Hosting-Services</p>
                </div>
                <div class="flex space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400"><?php echo $service_stats['active'] ?? 0; ?></div>
                        <div class="text-sm text-gray-400">Aktiv</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-400"><?php echo $service_stats['pending'] ?? 0; ?></div>
                        <div class="text-sm text-gray-400">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400"><?php echo $service_stats['suspended'] ?? 0; ?></div>
                        <div class="text-sm text-gray-400">Suspended</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!empty($services)): ?>
            <div class="space-y-6">
                <?php foreach ($services as $service): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-900 rounded-lg flex items-center justify-center">
                                        <i class="fas <?php echo $service['icon'] ?? 'fa-server'; ?> text-blue-400 text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($service['name']); ?></h3>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($service['service_type_name'] ?? 'Service'); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 text-sm font-medium rounded-full 
                                        <?php 
                                        switch($service['status']) {
                                            case 'active': echo 'bg-green-900 text-green-400'; break;
                                            case 'pending': echo 'bg-orange-900 text-orange-400'; break;
                                            case 'suspended': echo 'bg-red-900 text-red-400'; break;
                                            default: echo 'bg-gray-700 text-gray-300';
                                        }
                                        ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Service Details -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">Erstellt</p>
                                    <p class="text-sm font-medium text-white"><?php echo date('d.m.Y', strtotime($service['created_at'])); ?></p>
                                </div>
                                <?php if ($service['expires_at']): ?>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">Läuft ab</p>
                                    <p class="text-sm font-medium text-white"><?php echo date('d.m.Y', strtotime($service['expires_at'])); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($service['monthly_price']): ?>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">Monatlich</p>
                                    <p class="text-sm font-medium text-white">€<?php echo number_format($service['monthly_price'], 2); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($service['server_ip']): ?>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">Server IP</p>
                                    <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($service['server_ip']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2">
                                <?php if ($service['status'] == 'active'): ?>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                                        <i class="fas fa-cog mr-2"></i>Verwalten
                                    </button>
                                    <?php if ($service['category'] == 'vps' || $service['category'] == 'gameserver'): ?>
                                    <button class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-700">
                                        <i class="fas fa-redo mr-2"></i>Neustart
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                                    <i class="fas fa-credit-card mr-2"></i>Verlängern
                                </button>
                                
                                <button class="bg-gray-700 text-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-600">
                                    <i class="fas fa-chart-bar mr-2"></i>Statistiken
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-server text-gray-600 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-white mb-2">Keine Services gefunden</h3>
                <p class="text-gray-400 mb-8 max-w-md mx-auto">
                    Sie haben noch keine Services bestellt. Starten Sie jetzt mit unserem umfangreichen Hosting-Angebot.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/products" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Ersten Service bestellen
                    </a>
                    <a href="/contact" class="bg-gray-700 text-gray-300 border border-gray-600 px-8 py-3 rounded-lg font-medium hover:bg-gray-600">
                        <i class="fas fa-phone mr-2"></i>Beratung anfordern
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderFooter(); ?>