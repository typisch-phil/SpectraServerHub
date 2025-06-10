<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'Webspace - SpectraHost';
renderHeader($pageTitle);

// Get webspace services from s9281_spectrahost database
$webspaceServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'webspace' ORDER BY price ASC");
    $stmt->execute();
    $webspaceServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in webspace.php: " . $e->getMessage());
    $webspaceServices = [];
}
?>

<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Webspace Hosting
            </h1>
            <p class="mt-6 text-xl text-blue-100 max-w-3xl mx-auto">
                Professionelle Webhosting-Lösungen für Ihre Online-Präsenz. Von kleinen Websites bis hin zu umfangreichen Web-Anwendungen.
            </p>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Webspace Features</h2>
            <p class="mt-4 text-lg text-gray-600">Alles was Sie für erfolgreiches Webhosting benötigen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">SSD-Speicher</h3>
                <p class="mt-2 text-gray-600">Ultraschnelle SSD-Festplatten für maximale Performance</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">SSL-Zertifikate</h3>
                <p class="mt-2 text-gray-600">Kostenlose SSL-Zertifikate für sichere Verbindungen</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-database text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">MySQL Datenbanken</h3>
                <p class="mt-2 text-gray-600">Unbegrenzte MySQL-Datenbanken inklusive</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">E-Mail Postfächer</h3>
                <p class="mt-2 text-gray-600">Professionelle E-Mail-Adressen mit Ihrer Domain</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sync-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Tägliche Backups</h3>
                <p class="mt-2 text-gray-600">Automatische tägliche Sicherungen Ihrer Daten</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">24/7 Support</h3>
                <p class="mt-2 text-gray-600">Rund um die Uhr technischer Support</p>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Webspace Pakete</h2>
            <p class="mt-4 text-lg text-gray-600">Wählen Sie das passende Paket für Ihre Bedürfnisse</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (empty($webspaceServices)): ?>
                <!-- Fallback Pakete wenn keine Daten aus DB -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-900">Starter</h3>
                    <p class="mt-2 text-gray-600">Perfekt für kleine Websites</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€4.99</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            5 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            1 Domain inklusive
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            5 E-Mail Postfächer
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            MySQL Datenbanken
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border-2 border-blue-600 rounded-lg p-6 hover:shadow-lg transition-shadow relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm">Beliebt</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Business</h3>
                    <p class="mt-2 text-gray-600">Ideal für Unternehmen</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€9.99</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            25 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            3 Domains inklusive
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            25 E-Mail Postfächer
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Unbegrenzte Datenbanken
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-900">Premium</h3>
                    <p class="mt-2 text-gray-600">Für anspruchsvolle Projekte</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€19.99</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            100 GB SSD Speicher
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            10 Domains inklusive
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Unbegrenzte E-Mails
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Priority Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($webspaceServices as $service): ?>
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€<?php echo number_format($service['price'], 2); ?></span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <?php if (!empty($service['features'])): ?>
                    <ul class="mt-6 space-y-3">
                        <?php 
                        $features = is_string($service['features']) ? json_decode($service['features'], true) : $service['features'];
                        if (is_array($features)):
                            foreach ($features as $feature): ?>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
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

<!-- CTA Section -->
<div class="bg-blue-600 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white">Bereit für Ihr Webhosting?</h2>
        <p class="mt-4 text-xl text-blue-100">Starten Sie noch heute mit unserem zuverlässigen Webspace</p>
        <div class="mt-8">
            <a href="/register" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 transition-colors">
                Account erstellen
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>