<?php
require_once __DIR__ . '/../../includes/dashboard-header.php';

// Dashboard-Variablen initialisieren
list($db, $user_id, $user) = getDashboardGlobals();

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
    $services = $stmt->fetchAll();

    // Service-Statistiken berechnen
    $stats = $db->fetchAll("SELECT status, COUNT(*) as count FROM services WHERE user_id = ? GROUP BY status", [$user_id]);
    $service_stats = [];
    foreach ($stats as $stat) {
        $service_stats[$stat['status']] = $stat['count'];
    }

} catch (Exception $e) {
    error_log("Services page error: " . $e->getMessage());
    $services = [];
    $service_stats = [];
}

// Services Content
renderDashboardLayout('Services - SpectraHost Dashboard', 'services', function() use ($services, $service_stats) {
?>

<!-- Services Header mit Statistiken -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">Meine Services</h1>
            <p class="mt-2 text-blue-100">Verwalten Sie Ihre aktiven Hosting-Services</p>
        </div>
        <div class="flex space-x-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-white"><?php echo $service_stats['active'] ?? 0; ?></div>
                <div class="text-sm text-blue-100">Aktiv</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-300"><?php echo $service_stats['pending'] ?? 0; ?></div>
                <div class="text-sm text-blue-100">Pending</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-red-300"><?php echo $service_stats['suspended'] ?? 0; ?></div>
                <div class="text-sm text-blue-100">Suspended</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <a href="/products/webspace" class="bg-gray-800 p-4 rounded-lg border border-gray-700 hover:bg-gray-750 transition-colors">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-globe text-blue-400"></i>
            </div>
            <div>
                <h3 class="font-medium text-white">Webspace</h3>
                <p class="text-sm text-gray-400">Webhosting bestellen</p>
            </div>
        </div>
    </a>
    
    <a href="/products/vserver" class="bg-gray-800 p-4 rounded-lg border border-gray-700 hover:bg-gray-750 transition-colors">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-server text-green-400"></i>
            </div>
            <div>
                <h3 class="font-medium text-white">vServer</h3>
                <p class="text-sm text-gray-400">Virtual Server</p>
            </div>
        </div>
    </a>
    
    <a href="/products/gameserver" class="bg-gray-800 p-4 rounded-lg border border-gray-700 hover:bg-gray-750 transition-colors">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-purple-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-gamepad text-purple-400"></i>
            </div>
            <div>
                <h3 class="font-medium text-white">Gameserver</h3>
                <p class="text-sm text-gray-400">Gaming Server</p>
            </div>
        </div>
    </a>
    
    <a href="/products/domain" class="bg-gray-800 p-4 rounded-lg border border-gray-700 hover:bg-gray-750 transition-colors">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-orange-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-link text-orange-400"></i>
            </div>
            <div>
                <h3 class="font-medium text-white">Domain</h3>
                <p class="text-sm text-gray-400">Domain registrieren</p>
            </div>
        </div>
    </a>
</div>

<!-- Services Liste -->
<div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
    <div class="px-6 py-4 border-b border-gray-700">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-white">Aktive Services</h2>
            <div class="flex space-x-2">
                <button class="px-3 py-2 text-sm bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <button class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Service bestellen
                </button>
            </div>
        </div>
    </div>
    
    <div class="divide-y divide-gray-700">
        <?php if (empty($services)): ?>
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-server text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-white mb-2">Keine Services vorhanden</h3>
                <p class="text-gray-400 mb-6">Bestellen Sie Ihren ersten Service und starten Sie durch</p>
                <div class="flex justify-center space-x-4">
                    <a href="/products/webspace" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-globe mr-2"></i>Webspace bestellen
                    </a>
                    <a href="/products/vserver" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-server mr-2"></i>vServer bestellen
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="p-6 hover:bg-gray-750 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-900 rounded-lg flex items-center justify-center">
                                <i class="<?php echo $service['icon'] ?? 'fas fa-server'; ?> text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-white"><?php echo htmlspecialchars($service['name']); ?></h3>
                                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($service['service_type_name'] ?? 'Service'); ?></p>
                                <div class="flex items-center space-x-4 mt-1">
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Erstellt: <?php echo date('d.m.Y', strtotime($service['created_at'])); ?>
                                    </span>
                                    <?php if ($service['expires_at']): ?>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        Läuft ab: <?php echo date('d.m.Y', strtotime($service['expires_at'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <div class="text-lg font-bold text-white">€<?php echo number_format($service['price'] ?? 0, 2); ?></div>
                                <div class="text-sm text-gray-400">/Monat</div>
                            </div>
                            
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                <?php 
                                switch($service['status']) {
                                    case 'active': echo 'bg-green-900 text-green-300'; break;
                                    case 'pending': echo 'bg-yellow-900 text-yellow-300'; break;
                                    case 'suspended': echo 'bg-red-900 text-red-300'; break;
                                    case 'cancelled': echo 'bg-gray-700 text-gray-300'; break;
                                    default: echo 'bg-blue-900 text-blue-300';
                                }
                                ?>">
                                <?php 
                                $status_labels = [
                                    'active' => 'Aktiv',
                                    'pending' => 'Pending',
                                    'suspended' => 'Gesperrt',
                                    'cancelled' => 'Gekündigt'
                                ];
                                echo $status_labels[$service['status']] ?? ucfirst($service['status']);
                                ?>
                            </span>
                            
                            <div class="flex space-x-2">
                                <button class="p-2 text-gray-400 hover:text-blue-400 transition-colors" title="Service verwalten">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-green-400 transition-colors" title="Details anzeigen">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-red-400 transition-colors" title="Service kündigen">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Details -->
                    <?php if ($service['status'] === 'active'): ?>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-400">CPU Usage</span>
                                    <span class="text-sm font-medium text-white">45%</span>
                                </div>
                                <div class="mt-2 bg-gray-600 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: 45%"></div>
                                </div>
                            </div>
                            <div class="bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-400">RAM Usage</span>
                                    <span class="text-sm font-medium text-white">67%</span>
                                </div>
                                <div class="mt-2 bg-gray-600 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 67%"></div>
                                </div>
                            </div>
                            <div class="bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-400">Storage</span>
                                    <span class="text-sm font-medium text-white">32%</span>
                                </div>
                                <div class="mt-2 bg-gray-600 rounded-full h-2">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: 32%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
});
?>