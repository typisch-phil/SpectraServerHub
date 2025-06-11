<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'Webhosting - Professionelle Hosting-Lösungen - SpectraHost';
$pageDescription = 'Professionelles Webhosting mit SSD-Speicher, SSL-Zertifikaten und unbegrenzten E-Mail-Postfächern. Jetzt günstig bei SpectraHost.';

// Get webspace services from s9281_spectrahost database
$webspaceServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'webspace' ORDER BY id ASC");
    $stmt->execute();
    $webspaceServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in webspace.php: " . $e->getMessage());
    $webspaceServices = [];
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Webspace Hosting
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Professionelle Webhosting-Lösungen für Ihre Online-Präsenz. Von kleinen Websites bis hin zu umfangreichen Web-Anwendungen.
            </p>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white">Webspace Features</h2>
            <p class="mt-4 text-lg text-gray-300">Alles was Sie für erfolgreiches Webhosting benötigen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">SSD-Speicher</h3>
                <p class="mt-2 text-gray-300">Ultraschnelle SSD-Festplatten für maximale Performance</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">SSL-Zertifikate</h3>
                <p class="mt-2 text-gray-300">Kostenlose SSL-Zertifikate für sichere Verbindungen</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-database text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">MySQL Datenbanken</h3>
                <p class="mt-2 text-gray-300">Unbegrenzte MySQL-Datenbanken inklusive</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">E-Mail Postfächer</h3>
                <p class="mt-2 text-gray-300">Professionelle E-Mail-Adressen mit Ihrer Domain</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sync-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Daily Backups</h3>
                <p class="mt-2 text-gray-300">Tägliche automatische Sicherung Ihrer Daten</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
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
            <h2 class="text-3xl font-bold text-white">Webspace Pakete</h2>
            <p class="mt-4 text-lg text-gray-300">Wählen Sie das passende Paket für Ihre Bedürfnisse</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (empty($webspaceServices)): ?>
                <!-- Fallback static packages if no database services -->
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white">Starter</h3>
                    <p class="mt-2 text-gray-300">Perfekt für kleine Websites und Blogs</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€4,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            10 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            1 Domain inklusive
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            5 E-Mail-Postfächer
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            SSL-Zertifikat
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-blue-400 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">Beliebt</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mt-4">Professional</h3>
                    <p class="mt-2 text-gray-300">Ideal für Unternehmen und größere Projekte</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€9,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            50 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            5 Domains inklusive
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Unbegrenzte E-Mails
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            24/7 Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                    <h3 class="text-xl font-semibold text-white">Enterprise</h3>
                    <p class="mt-2 text-gray-300">Maximale Leistung für anspruchsvolle Anwendungen</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-white">€19,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            100 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            10 Domains inklusive
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Unbegrenzte E-Mails
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check text-green-400 mr-2"></i>
                            Priority Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($webspaceServices as $service): ?>
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
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Technical Specifications -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white">Technische Details</h2>
            <p class="mt-4 text-lg text-gray-300">Modernste Technik für Ihr Webhosting</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700">
                <i class="fas fa-server text-blue-400 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">PHP 8.2</h3>
                <p class="text-gray-300">Neueste PHP-Version für beste Performance</p>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700">
                <i class="fas fa-database text-green-400 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">MySQL 8.0</h3>
                <p class="text-gray-300">Moderne Datenbank-Technologie</p>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700">
                <i class="fas fa-bolt text-yellow-400 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">SSD NVMe</h3>
                <p class="text-gray-300">Ultraschnelle NVMe-SSD-Speicher</p>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 text-center border border-gray-700">
                <i class="fas fa-shield-alt text-purple-400 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">DDoS-Schutz</h3>
                <p class="text-gray-300">Professioneller Schutz vor Angriffen</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-6">
            Bereit für Ihr neues Webhosting?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
            Starten Sie noch heute mit SpectraHost und erleben Sie den Unterschied.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <i class="fas fa-user-plus mr-2"></i>Jetzt registrieren
            </a>
            <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                <i class="fas fa-phone mr-2"></i>Beratung anfordern
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>