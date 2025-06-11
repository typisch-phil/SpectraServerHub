<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'VPS Server - Virtual Private Server - SpectraHost';
$pageDescription = 'Leistungsstarke VPS Server mit garantierten Ressourcen, Root-Zugriff und SSD-NVMe Speicher. Jetzt bei SpectraHost bestellen.';

// Get vServer services from s9281_spectrahost database
$vserverServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'vserver' ORDER BY id ASC");
    $stmt->execute();
    $vserverServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in vserver.php: " . $e->getMessage());
    $vserverServices = [];
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Virtual Private Server
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Leistungsstarke VPS-Server mit garantierten Ressourcen und vollständiger Kontrolle. Perfect für anspruchsvolle Anwendungen.
            </p>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white">VPS Features</h2>
            <p class="mt-4 text-lg text-gray-300">Alles was Sie für professionelle Server-Anwendungen benötigen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-crown text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Root-Zugriff</h3>
                <p class="mt-2 text-gray-300">Vollständige Administratoren-Rechte für maximale Flexibilität</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-memory text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Garantierte Ressourcen</h3>
                <p class="mt-2 text-gray-300">Dedizierte CPU, RAM und Speicher nur für Sie</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hdd text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">SSD NVMe</h3>
                <p class="mt-2 text-gray-300">Ultraschnelle NVMe-SSD-Speicher für beste Performance</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-network-wired text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">1 Gbit/s Anbindung</h3>
                <p class="mt-2 text-gray-300">Hochgeschwindigkeits-Netzwerkverbindung inklusive</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">DDoS-Schutz</h3>
                <p class="mt-2 text-gray-300">Professioneller Schutz vor DDoS-Attacken</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">24/7 Support</h3>
                <p class="mt-2 text-gray-300">Deutschsprachiger Support rund um die Uhr</p>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="py-16 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white">VPS Pakete</h2>
            <p class="mt-4 text-lg text-gray-300">Wählen Sie die passende Konfiguration für Ihre Anforderungen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (empty($vserverServices)): ?>
                <!-- Fallback static packages if no database services -->
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white">VPS Starter</h3>
                    <p class="mt-2 text-gray-300">Ideal für kleine Anwendungen und Tests</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€9,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            1 vCPU Core
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            2 GB RAM
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            25 GB SSD NVMe
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Root-Zugriff
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-purple-400 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-semibold">Beliebt</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mt-4">VPS Professional</h3>
                    <p class="mt-2 text-gray-300">Perfect für Unternehmen und größere Projekte</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€19,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            2 vCPU Cores
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            4 GB RAM
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            50 GB SSD NVMe
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Priority Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white">VPS Enterprise</h3>
                    <p class="mt-2 text-gray-300">Maximale Leistung für anspruchsvolle Anwendungen</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€39,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            4 vCPU Cores
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            8 GB RAM
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            100 GB SSD NVMe
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Managed Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($vserverServices as $service): ?>
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
                    <button class="mt-8 w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Operating Systems -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white">Betriebssysteme</h2>
            <p class="mt-4 text-lg text-gray-300">Wählen Sie aus einer Vielzahl von Betriebssystemen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                <i class="fab fa-ubuntu text-orange-500 text-3xl mb-2"></i>
                <h3 class="text-sm font-semibold text-white">Ubuntu</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                <i class="fab fa-centos text-purple-500 text-3xl mb-2"></i>
                <h3 class="text-sm font-semibold text-white">CentOS</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                <i class="fab fa-redhat text-red-500 text-3xl mb-2"></i>
                <h3 class="text-sm font-semibold text-white">Red Hat</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                <i class="fab fa-debian text-red-600 text-3xl mb-2"></i>
                <h3 class="text-sm font-semibold text-white">Debian</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                <i class="fab fa-windows text-blue-500 text-3xl mb-2"></i>
                <h3 class="text-sm font-semibold text-white">Windows</h3>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                <i class="fab fa-fedora text-blue-600 text-3xl mb-2"></i>
                <h3 class="text-sm font-semibold text-white">Fedora</h3>
            </div>
        </div>
    </div>
</div>

<!-- Use Cases -->
<div class="py-16 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white">Anwendungsbereiche</h2>
            <p class="mt-4 text-lg text-gray-300">Ideal für verschiedenste Projekte und Anwendungen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-globe text-blue-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Web-Anwendungen</h3>
                <p class="text-gray-300">Hosting für komplexe Web-Apps, APIs und Microservices</p>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-database text-green-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Datenbank-Server</h3>
                <p class="text-gray-300">MySQL, PostgreSQL, MongoDB und weitere Datenbanken</p>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-code text-purple-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Entwicklung</h3>
                <p class="text-gray-300">Test- und Entwicklungsumgebungen für Teams</p>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-envelope text-orange-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Mail-Server</h3>
                <p class="text-gray-300">Eigene E-Mail-Server mit vollständiger Kontrolle</p>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-cloud text-cyan-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Cloud Storage</h3>
                <p class="text-gray-300">Private Cloud-Lösungen und Backup-Systeme</p>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-gamepad text-red-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Game-Server</h3>
                <p class="text-gray-300">Eigene Gaming-Server für Communities</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="py-20 bg-gradient-to-r from-purple-600 to-blue-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-6">
            Bereit für Ihren VPS?
        </h2>
        <p class="text-xl text-purple-100 mb-8">
            Starten Sie noch heute mit einem leistungsstarken VPS von SpectraHost.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-purple-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <i class="fas fa-user-plus mr-2"></i>Jetzt registrieren
            </a>
            <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-purple-600 transition-colors">
                <i class="fas fa-phone mr-2"></i>Beratung anfordern
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>