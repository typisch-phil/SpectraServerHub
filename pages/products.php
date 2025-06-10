<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$pageTitle = 'Produkte - SpectraHost';
renderHeader($pageTitle);

// Get all service categories from s9281_spectrahost database
$serviceCategories = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT DISTINCT category, COUNT(*) as count FROM service_types GROUP BY category ORDER BY category");
    $stmt->execute();
    $serviceCategories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in products.php: " . $e->getMessage());
    $serviceCategories = [];
}
?>

<div class="bg-gradient-to-r from-blue-600 to-purple-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Unsere Hosting-Produkte
            </h1>
            <p class="mt-6 text-xl text-blue-100 max-w-3xl mx-auto">
                Professionelle Hosting-Lösungen für jeden Bedarf. Von Webspace bis hin zu leistungsstarken Servern.
            </p>
        </div>
    </div>
</div>

<!-- Product Categories -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Webspace -->
            <a href="/products/webspace" class="group block bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl p-8 transition-all duration-300 hover:shadow-xl">
                <div class="w-16 h-16 bg-blue-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-globe text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Webspace</h3>
                <p class="text-gray-600 mb-4">Professionelles Webhosting für Ihre Website mit SSD-Speicher und kostenlosen SSL-Zertifikaten.</p>
                <div class="flex items-center text-blue-600 font-medium">
                    <span>Ab €4.99/Monat</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
                <?php if (!empty($serviceCategories)): ?>
                    <?php foreach ($serviceCategories as $category): ?>
                        <?php if ($category['category'] === 'webspace'): ?>
                            <div class="mt-2 text-sm text-gray-500"><?php echo $category['count']; ?> Pakete verfügbar</div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </a>

            <!-- vServer -->
            <a href="/products/vserver" class="group block bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl p-8 transition-all duration-300 hover:shadow-xl">
                <div class="w-16 h-16 bg-green-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-server text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">vServer</h3>
                <p class="text-gray-600 mb-4">Leistungsstarke Virtual Private Server mit Root-Zugriff und garantierten Ressourcen.</p>
                <div class="flex items-center text-green-600 font-medium">
                    <span>Ab €14.99/Monat</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
                <?php if (!empty($serviceCategories)): ?>
                    <?php foreach ($serviceCategories as $category): ?>
                        <?php if ($category['category'] === 'vserver'): ?>
                            <div class="mt-2 text-sm text-gray-500"><?php echo $category['count']; ?> Pakete verfügbar</div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </a>

            <!-- GameServer -->
            <a href="/products/gameserver" class="group block bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-xl p-8 transition-all duration-300 hover:shadow-xl">
                <div class="w-16 h-16 bg-purple-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-gamepad text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">GameServer</h3>
                <p class="text-gray-600 mb-4">Optimierte Gaming-Server für Minecraft, CS2, ARK und viele weitere Spiele.</p>
                <div class="flex items-center text-purple-600 font-medium">
                    <span>Ab €9.99/Monat</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
                <?php if (!empty($serviceCategories)): ?>
                    <?php foreach ($serviceCategories as $category): ?>
                        <?php if ($category['category'] === 'gameserver'): ?>
                            <div class="mt-2 text-sm text-gray-500"><?php echo $category['count']; ?> Pakete verfügbar</div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </a>

            <!-- Domain -->
            <a href="/products/domain" class="group block bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 rounded-xl p-8 transition-all duration-300 hover:shadow-xl">
                <div class="w-16 h-16 bg-orange-600 rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-link text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Domain</h3>
                <p class="text-gray-600 mb-4">Domain-Registrierung mit über 500 TLDs und kostenlosem WHOIS-Schutz.</p>
                <div class="flex items-center text-orange-600 font-medium">
                    <span>Ab €8.99/Jahr</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
                <?php if (!empty($serviceCategories)): ?>
                    <?php foreach ($serviceCategories as $category): ?>
                        <?php if ($category['category'] === 'domain'): ?>
                            <div class="mt-2 text-sm text-gray-500"><?php echo $category['count']; ?> TLDs verfügbar</div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </a>
        </div>
    </div>
</div>

<!-- Why Choose SpectraHost -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Warum SpectraHost?</h2>
            <p class="mt-4 text-lg text-gray-600">Ihre Vorteile bei uns im Überblick</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Höchste Performance</h3>
                <p class="mt-2 text-gray-600">Moderne Hardware und SSD-Speicher für maximale Geschwindigkeit</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Maximale Sicherheit</h3>
                <p class="mt-2 text-gray-600">Tägliche Backups und proaktive Sicherheitsmaßnahmen</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">24/7 Support</h3>
                <p class="mt-2 text-gray-600">Kompetenter deutschsprachiger Support rund um die Uhr</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-euro-sign text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Faire Preise</h3>
                <p class="mt-2 text-gray-600">Transparente Preisgestaltung ohne versteckte Kosten</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">99,9% Uptime</h3>
                <p class="mt-2 text-gray-600">Garantierte Verfügbarkeit für Ihre Online-Präsenz</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Einfache Verwaltung</h3>
                <p class="mt-2 text-gray-600">Intuitive Control Panels für die einfache Verwaltung</p>
            </div>
        </div>
    </div>
</div>

<!-- Featured Products -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Beliebte Pakete</h2>
            <p class="mt-4 text-lg text-gray-600">Unsere meistgewählten Hosting-Lösungen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Webspace Business -->
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-globe text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Webspace Business</h3>
                        <p class="text-sm text-gray-600">Ideal für Unternehmen</p>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">€9.99</span>
                    <span class="text-gray-600">/Monat</span>
                </div>
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        25 GB SSD Speicher
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        3 Domains inklusive
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        25 E-Mail Postfächer
                    </li>
                </ul>
                <a href="/products/webspace" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                    Details ansehen
                </a>
            </div>

            <!-- VPS Pro -->
            <div class="border-2 border-green-600 rounded-lg p-6 hover:shadow-lg transition-shadow relative">
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                    <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm">Beliebt</span>
                </div>
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-server text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">VPS Pro</h3>
                        <p class="text-sm text-gray-600">Perfekt für Unternehmen</p>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">€29.99</span>
                    <span class="text-gray-600">/Monat</span>
                </div>
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        4 vCPU Kerne
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        8 GB RAM
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        100 GB NVMe SSD
                    </li>
                </ul>
                <a href="/products/vserver" class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                    Details ansehen
                </a>
            </div>

            <!-- Minecraft Server -->
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-gamepad text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Minecraft Server</h3>
                        <p class="text-sm text-gray-600">Für 20 Spieler</p>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">€14.99</span>
                    <span class="text-gray-600">/Monat</span>
                </div>
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        4 GB RAM
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        DDoS-Schutz
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Instant Setup
                    </li>
                </ul>
                <a href="/products/gameserver" class="block w-full bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                    Details ansehen
                </a>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white">Bereit für Ihr Hosting?</h2>
        <p class="mt-4 text-xl text-blue-100">Starten Sie noch heute mit SpectraHost</p>
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 transition-colors">
                Account erstellen
            </a>
            <a href="/contact" class="inline-flex items-center px-6 py-3 border-2 border-white text-base font-medium rounded-md text-white hover:bg-white hover:text-blue-600 transition-colors">
                Beratung anfordern
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>