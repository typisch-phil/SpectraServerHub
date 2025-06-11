<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'Gameserver - Gaming-Server Hosting - SpectraHost';
$pageDescription = 'Optimierte Gaming-Server für Minecraft, CS:GO, ARK und viele weitere Spiele. DDoS-Schutz, Instant Setup und Mod-Support inklusive.';

// Get gameserver services from s9281_spectrahost database
$gameserverServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'gameserver' ORDER BY id ASC");
    $stmt->execute();
    $gameserverServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in gameserver.php: " . $e->getMessage());
    $gameserverServices = [];
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Gaming Server
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Optimierte Gaming-Server für Minecraft, CS:GO, ARK und viele weitere Spiele. Maximale Performance für das beste Spielerlebnis.
            </p>
        </div>
    </div>
</div>

<!-- Supported Games -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Unterstützte Spiele</h2>
            <p class="mt-4 text-lg text-gray-300">Eine Auswahl der beliebtesten Gaming-Server</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700 hover:border-green-500 transition-colors">
                <i class="fas fa-cube text-green-400 text-3xl mb-3"></i>
                <h3 class="text-sm font-semibold text-white">Minecraft</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700 hover:border-green-500 transition-colors">
                <i class="fas fa-crosshairs text-red-400 text-3xl mb-3"></i>
                <h3 class="text-sm font-semibold text-white">CS:GO</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700 hover:border-green-500 transition-colors">
                <i class="fas fa-dragon text-orange-400 text-3xl mb-3"></i>
                <h3 class="text-sm font-semibold text-white">ARK</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700 hover:border-green-500 transition-colors">
                <i class="fas fa-hammer text-yellow-400 text-3xl mb-3"></i>
                <h3 class="text-sm font-semibold text-white">Rust</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700 hover:border-green-500 transition-colors">
                <i class="fas fa-rocket text-blue-400 text-3xl mb-3"></i>
                <h3 class="text-sm font-semibold text-white">Garry's Mod</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700 hover:border-green-500 transition-colors">
                <i class="fas fa-plus text-purple-400 text-3xl mb-3"></i>
                <h3 class="text-sm font-semibold text-white">Viele mehr</h3>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Gameserver Features</h2>
            <p class="mt-4 text-lg text-gray-300">Alles was Sie für professionelles Gaming benötigen</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">DDoS-Schutz</h3>
                <p class="mt-2 text-gray-300">Professioneller Schutz vor DDoS-Attacken für unterbrechungsfreies Gaming</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Instant Setup</h3>
                <p class="mt-2 text-gray-300">Ihr Server ist in wenigen Minuten nach der Bestellung einsatzbereit</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-puzzle-piece text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Mod-Support</h3>
                <p class="mt-2 text-gray-300">Vollständige Unterstützung für Mods und Plugins</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-save text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Backup-System</h3>
                <p class="mt-2 text-gray-300">Automatische Backups Ihrer Spielwelt und Konfiguration</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Web-Interface</h3>
                <p class="mt-2 text-gray-300">Einfache Verwaltung über benutzerfreundliches Web-Panel</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Gaming Support</h3>
                <p class="mt-2 text-gray-300">Spezialisierter Support für Gaming-spezifische Probleme</p>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Gameserver Pakete</h2>
            <p class="mt-4 text-lg text-gray-300">Wählen Sie die passende Konfiguration für Ihre Community</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (empty($gameserverServices)): ?>
                <!-- Fallback static packages if no database services -->
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white">Gaming Starter</h3>
                    <p class="mt-2 text-gray-300">Perfect für kleine Communities</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€7,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            4 GB RAM
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            10 Spieler Slots
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            25 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            DDoS-Schutz
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-green-400 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-semibold">Beliebt</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mt-4">Gaming Pro</h3>
                    <p class="mt-2 text-gray-300">Ideal für mittlere Communities</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€15,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            8 GB RAM
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            25 Spieler Slots
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            50 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Priority Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white">Gaming Elite</h3>
                    <p class="mt-2 text-gray-300">Maximale Performance für große Communities</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€29,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            16 GB RAM
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            50 Spieler Slots
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            100 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Dedicated Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($gameserverServices as $service): ?>
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="mt-2 text-gray-300"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€<?php echo number_format(floatval($service['monthly_price'] ?? $service['price'] ?? 0), 2); ?></span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <?php if (!empty($service['features'])): ?>
                    <ul class="mt-6 space-y-3">
                        <?php 
                        $features = is_string($service['features']) ? json_decode($service['features'], true) : $service['features'];
                        if (is_array($features)):
                            foreach ($features as $feature): ?>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            <?php echo htmlspecialchars($feature); ?>
                        </li>
                        <?php endforeach; endif; ?>
                    </ul>
                    <?php endif; ?>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Management Panel -->
<div class="py-16 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-white mb-6">Einfache Verwaltung</h2>
                <p class="text-lg text-gray-300 mb-8">
                    Verwalten Sie Ihren Gameserver mit unserem intuitiven Web-Panel. Keine technischen Kenntnisse erforderlich.
                </p>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <i class="fas fa-play text-green-400 text-xl mr-4"></i>
                        <span class="text-white">Server starten/stoppen mit einem Klick</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-upload text-green-400 text-xl mr-4"></i>
                        <span class="text-white">Einfacher Upload von Mods und Plugins</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-users text-green-400 text-xl mr-4"></i>
                        <span class="text-white">Spieler-Management und Moderationstools</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-green-400 text-xl mr-4"></i>
                        <span class="text-white">Echtzeit-Monitoring und Statistiken</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <div class="bg-gray-900 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-green-400 font-semibold">Server Status</span>
                        <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">ONLINE</span>
                    </div>
                    <div class="text-gray-300 text-sm">
                        <div>Spieler: 15/25</div>
                        <div>Uptime: 99.8%</div>
                        <div>RAM: 6.2/8 GB</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <button class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-play mr-1"></i>Start
                    </button>
                    <button class="bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 transition-colors text-sm">
                        <i class="fas fa-stop mr-1"></i>Stop
                    </button>
                    <button class="bg-yellow-600 text-white py-2 px-4 rounded hover:bg-yellow-700 transition-colors text-sm">
                        <i class="fas fa-redo mr-1"></i>Restart
                    </button>
                    <button class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm">
                        <i class="fas fa-cog mr-1"></i>Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="py-20 bg-gradient-to-r from-green-600 to-blue-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-6">
            Bereit für Ihren Gameserver?
        </h2>
        <p class="text-xl text-green-100 mb-8">
            Starten Sie noch heute mit einem leistungsstarken Gameserver von SpectraHost.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-green-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <i class="fas fa-user-plus mr-2"></i>Jetzt registrieren
            </a>
            <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-green-600 transition-colors">
                <i class="fas fa-phone mr-2"></i>Beratung anfordern
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>