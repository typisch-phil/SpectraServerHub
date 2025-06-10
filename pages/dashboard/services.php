<?php
// Neues Services Dashboard mit MySQL-Integration
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

    // Nächste Verlängerungen
    $stmt = $db->prepare("
        SELECT s.*, st.name as service_type_name 
        FROM services s 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE s.user_id = ? AND s.expires_at > NOW() 
        ORDER BY s.expires_at ASC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_renewals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Services data error: " . $e->getMessage());
    $services = [];
    $service_stats = [];
    $upcoming_renewals = [];
}

renderHeader('Meine Services - Dashboard');
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">SpectraHost</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-blue-600 border-b-2 border-blue-600 px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Support</a>
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
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Meine Services</h1>
                    <p class="mt-2 text-gray-600">Verwalten Sie Ihre aktiven Hosting-Services</p>
                </div>
                <div class="flex space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $service_stats['active'] ?? 0; ?></div>
                        <div class="text-sm text-gray-500">Aktiv</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600"><?php echo $service_stats['pending'] ?? 0; ?></div>
                        <div class="text-sm text-gray-500">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600"><?php echo $service_stats['suspended'] ?? 0; ?></div>
                        <div class="text-sm text-gray-500">Suspended</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!empty($services)): ?>
            <!-- Services Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Services List -->
                <div class="lg:col-span-2">
                    <div class="space-y-6">
                        <?php foreach ($services as $service): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas <?php echo $service['icon'] ?? 'fa-server'; ?> text-blue-600 text-xl"></i>
                                            </div>
                                            <div class="ml-4">
                                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($service['name']); ?></h3>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($service['service_type_name'] ?? 'Service'); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-3 py-1 text-sm font-medium rounded-full 
                                                <?php 
                                                switch($service['status']) {
                                                    case 'active': echo 'bg-green-100 text-green-800'; break;
                                                    case 'pending': echo 'bg-orange-100 text-orange-800'; break;
                                                    case 'suspended': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($service['status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Service Details -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase tracking-wide">Erstellt</p>
                                            <p class="text-sm font-medium text-gray-900"><?php echo date('d.m.Y', strtotime($service['created_at'])); ?></p>
                                        </div>
                                        <?php if ($service['expires_at']): ?>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase tracking-wide">Läuft ab</p>
                                            <p class="text-sm font-medium text-gray-900"><?php echo date('d.m.Y', strtotime($service['expires_at'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($service['monthly_price']): ?>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase tracking-wide">Monatlich</p>
                                            <p class="text-sm font-medium text-gray-900">€<?php echo number_format($service['monthly_price'], 2); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($service['server_ip']): ?>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase tracking-wide">Server IP</p>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($service['server_ip']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Resource Usage (wenn verfügbar) -->
                                    <?php if (isset($service['disk_used']) || isset($service['bandwidth_used'])): ?>
                                    <div class="mb-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Ressourcennutzung</h4>
                                        <div class="space-y-2">
                                            <?php if (isset($service['disk_used']) && isset($service['disk_limit'])): ?>
                                            <div>
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-600">Speicher</span>
                                                    <span class="font-medium"><?php echo round($service['disk_used']/1024, 1); ?> GB / <?php echo round($service['disk_limit']/1024, 1); ?> GB</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min(100, ($service['disk_used']/$service['disk_limit'])*100); ?>%"></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($service['bandwidth_used']) && isset($service['bandwidth_limit'])): ?>
                                            <div>
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-600">Bandbreite</span>
                                                    <span class="font-medium"><?php echo round($service['bandwidth_used']/1024, 1); ?> GB / <?php echo round($service['bandwidth_limit']/1024, 1); ?> GB</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min(100, ($service['bandwidth_used']/$service['bandwidth_limit'])*100); ?>%"></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

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
                                        
                                        <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200">
                                            <i class="fas fa-chart-bar mr-2"></i>Statistiken
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Upcoming Renewals -->
                    <?php if (!empty($upcoming_renewals)): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Nächste Verlängerungen</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach ($upcoming_renewals as $renewal): ?>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($renewal['name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($renewal['service_type_name']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900"><?php echo date('d.m.Y', strtotime($renewal['expires_at'])); ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?php 
                                                $days = ceil((strtotime($renewal['expires_at']) - time()) / 86400);
                                                echo $days . ' Tage';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Service-Aktionen</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <a href="/products" class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Neuen Service bestellen
                                </a>
                                <a href="/dashboard/billing" class="block w-full bg-green-600 text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-green-700">
                                    <i class="fas fa-credit-card mr-2"></i>Services verlängern
                                </a>
                                <a href="/dashboard/support" class="block w-full bg-purple-600 text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-purple-700">
                                    <i class="fas fa-headset mr-2"></i>Support kontaktieren
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Service Categories -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Service-Kategorien</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-2">
                                <a href="/webspace" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-globe text-blue-600 w-5"></i>
                                        <span class="ml-3 text-sm font-medium">Webspace</span>
                                    </div>
                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                </a>
                                <a href="/vserver" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-server text-green-600 w-5"></i>
                                        <span class="ml-3 text-sm font-medium">Virtual Server</span>
                                    </div>
                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                </a>
                                <a href="/gameserver" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-gamepad text-purple-600 w-5"></i>
                                        <span class="ml-3 text-sm font-medium">Game Server</span>
                                    </div>
                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                </a>
                                <a href="/domain" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <i class="fas fa-link text-orange-600 w-5"></i>
                                        <span class="ml-3 text-sm font-medium">Domains</span>
                                    </div>
                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-server text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">Keine Services gefunden</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Sie haben noch keine Services bestellt. Starten Sie jetzt mit unserem umfangreichen Hosting-Angebot.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/products" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Ersten Service bestellen
                    </a>
                    <a href="/contact" class="bg-white text-gray-700 border border-gray-300 px-8 py-3 rounded-lg font-medium hover:bg-gray-50">
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