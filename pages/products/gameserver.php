<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'GameServer - SpectraHost';
renderHeader($pageTitle);

// Get game server services - with database safety check
$gameServerServices = [];
if (isset($db) && $db) {
    try {
        $connection = $db->getConnection();
        $stmt = $connection->prepare("SELECT * FROM services WHERE type = 'gameserver' AND active = 1 ORDER BY price ASC");
        $stmt->execute();
        $gameServerServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Database error in gameserver.php: " . $e->getMessage());
        $gameServerServices = [];
    }
}
?>

<div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Premium GameServer</h1>
        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
            Hochperformante Game Server für Minecraft, CS2, ARK und viele weitere Spiele. 
            Sofortiger Start und vollständige Kontrolle über Ihren Gaming-Server.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-rocket mr-2"></i>
                Instant Setup
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-gamepad mr-2"></i>
                100+ Spiele verfügbar
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-tachometer-alt mr-2"></i>
                Anti-DDoS Schutz
            </div>
        </div>
    </div>
</div>

<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Wählen Sie Ihren GameServer</h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Alle GameServer werden automatisch bereitgestellt und sind sofort spielbereit
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($gameServerServices as $service): ?>
            <div class="card hover-lift">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-gamepad text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <div class="text-3xl font-bold text-purple-600 mb-2">
                        €<?php echo number_format($service['price'], 2); ?>/Monat
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                </div>
                
                <div class="space-y-3 mb-8">
                    <?php if ($service['cpu_cores'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-microchip text-purple-500 w-5 mr-3"></i>
                        <span><?php echo $service['cpu_cores']; ?> vCPU Core<?php echo $service['cpu_cores'] > 1 ? 's' : ''; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($service['memory_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-memory text-purple-500 w-5 mr-3"></i>
                        <span><?php echo $service['memory_gb']; ?> GB RAM</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($service['storage_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-hdd text-purple-500 w-5 mr-3"></i>
                        <span><?php echo $service['storage_gb']; ?> GB SSD</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center">
                        <i class="fas fa-users text-purple-500 w-5 mr-3"></i>
                        <span>Unbegrenzte Slots</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-purple-500 w-5 mr-3"></i>
                        <span>DDoS-Schutz</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-terminal text-purple-500 w-5 mr-3"></i>
                        <span>FTP & SSH Zugang</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-backup text-purple-500 w-5 mr-3"></i>
                        <span>Automatische Backups</span>
                    </div>
                </div>
                
                <a href="/order?service=<?php echo $service['id']; ?>" 
                   class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-6 rounded-lg transition-colors text-center block">
                    Jetzt bestellen
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Supported Games -->
<div class="bg-gray-50 dark:bg-gray-800 py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Unterstützte Spiele</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Über 100 Spiele mit 1-Click Installation verfügbar
            </p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-cube text-2xl text-green-600"></i>
                </div>
                <span class="text-sm font-medium">Minecraft</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-crosshairs text-2xl text-orange-600"></i>
                </div>
                <span class="text-sm font-medium">Counter-Strike 2</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-mountain text-2xl text-blue-600"></i>
                </div>
                <span class="text-sm font-medium">ARK: Survival</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-hammer text-2xl text-purple-600"></i>
                </div>
                <span class="text-sm font-medium">Rust</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-fire text-2xl text-red-600"></i>
                </div>
                <span class="text-sm font-medium">Valheim</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-plus text-2xl text-gray-600"></i>
                </div>
                <span class="text-sm font-medium">100+ weitere</span>
            </div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">GameServer Features</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Instant Setup</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Ihr GameServer ist innerhalb von 60 Sekunden bereit und spielbar
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Mod Support</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Volle Mod-Unterstützung für alle gängigen Spiele und Frameworks
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Gaming Support</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Spezialisierter Gaming-Support von erfahrenen Server-Administratoren
                </p>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>