<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'Domain-Registrierung - Über 500 TLDs verfügbar - SpectraHost';
$pageDescription = 'Registrieren Sie Ihre Wunschdomain aus über 500 verfügbaren Endungen. DNS-Management, Domain-Transfer und Whois-Privacy inklusive.';

// Get domain services from s9281_spectrahost database
$domainServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'domain' ORDER BY id ASC");
    $stmt->execute();
    $domainServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in domain.php: " . $e->getMessage());
    $domainServices = [];
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Domain-Registrierung
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Sichern Sie sich Ihre perfekte Domain aus über 500 verfügbaren Endungen. Professionelles DNS-Management inklusive.
            </p>
        </div>
    </div>
</div>

<!-- Domain Search -->
<div class="py-16 bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
            <h2 class="text-2xl font-bold text-white text-center mb-6">Domain-Verfügbarkeit prüfen</h2>
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" placeholder="Ihre Wunschdomain eingeben..." 
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <button class="bg-orange-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-orange-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Suchen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Popular TLDs -->
<div class="py-16 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Beliebte Domain-Endungen</h2>
            <p class="mt-4 text-lg text-gray-300">Die gefragtesten TLDs zu Top-Preisen</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gray-700 rounded-lg p-6 text-center border border-gray-600">
                <h3 class="text-2xl font-bold text-white">.de</h3>
                <p class="text-gray-300 mt-2">Deutschland</p>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-orange-400">€0,99</span>
                    <span class="text-gray-400 text-sm">/Jahr</span>
                </div>
                <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                    Registrieren
                </button>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 text-center border border-gray-600">
                <h3 class="text-2xl font-bold text-white">.com</h3>
                <p class="text-gray-300 mt-2">International</p>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-orange-400">€12,99</span>
                    <span class="text-gray-400 text-sm">/Jahr</span>
                </div>
                <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                    Registrieren
                </button>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 text-center border border-gray-600">
                <h3 class="text-2xl font-bold text-white">.org</h3>
                <p class="text-gray-300 mt-2">Organisation</p>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-orange-400">€14,99</span>
                    <span class="text-gray-400 text-sm">/Jahr</span>
                </div>
                <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                    Registrieren
                </button>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 text-center border border-gray-600">
                <h3 class="text-2xl font-bold text-white">.net</h3>
                <p class="text-gray-300 mt-2">Netzwerk</p>
                <div class="mt-4">
                    <span class="text-2xl font-bold text-orange-400">€15,99</span>
                    <span class="text-gray-400 text-sm">/Jahr</span>
                </div>
                <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                    Registrieren
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Domain-Features</h2>
            <p class="mt-4 text-lg text-gray-300">Professionelle Domain-Services inklusive</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">DNS-Management</h3>
                <p class="mt-2 text-gray-300">Vollständige Kontrolle über Ihre DNS-Einstellungen</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Whois-Privacy</h3>
                <p class="mt-2 text-gray-300">Schutz Ihrer persönlichen Daten im Whois</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exchange-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Domain-Transfer</h3>
                <p class="mt-2 text-gray-300">Einfacher Transfer bestehender Domains</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-redo text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Auto-Renewal</h3>
                <p class="mt-2 text-gray-300">Automatische Verlängerung Ihrer Domains</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">Domain-Lock</h3>
                <p class="mt-2 text-gray-300">Schutz vor unauthorisierten Transfers</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white">24/7 Support</h3>
                <p class="mt-2 text-gray-300">Deutschsprachiger Support bei Fragen</p>
            </div>
        </div>
    </div>
</div>

<!-- TLD Categories -->
<div class="py-16 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Domain-Kategorien</h2>
            <p class="mt-4 text-lg text-gray-300">Über 500 Domain-Endungen verfügbar</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-globe text-blue-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Generic</h3>
                <p class="text-gray-300 text-sm mb-4">Internationale Domains für jeden Zweck</p>
                <div class="text-xs text-gray-400">
                    .com, .net, .org, .info, .biz
                </div>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-flag text-green-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Länder</h3>
                <p class="text-gray-300 text-sm mb-4">Länderspezifische Domain-Endungen</p>
                <div class="text-xs text-gray-400">
                    .de, .at, .ch, .uk, .fr, .es
                </div>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-briefcase text-purple-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Business</h3>
                <p class="text-gray-300 text-sm mb-4">Professionelle Business-Domains</p>
                <div class="text-xs text-gray-400">
                    .company, .business, .shop, .store
                </div>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-6 border border-gray-600">
                <i class="fas fa-palette text-orange-400 text-2xl mb-4"></i>
                <h3 class="text-lg font-semibold text-white mb-2">Kreativ</h3>
                <p class="text-gray-300 text-sm mb-4">Domains für kreative Projekte</p>
                <div class="text-xs text-gray-400">
                    .design, .art, .photo, .blog
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Database Services -->
<?php if (!empty($domainServices)): ?>
<div class="py-16 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white">Domain-Services</h2>
            <p class="mt-4 text-lg text-gray-300">Unsere Domain-Pakete im Überblick</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($domainServices as $service): ?>
            <div class="border border-gray-600 rounded-lg p-6 hover:shadow-lg transition-shadow bg-gray-700">
                <h3 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($service['name']); ?></h3>
                <p class="mt-2 text-gray-300"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                <div class="mt-4">
                    <span class="text-3xl font-bold text-white">€<?php echo number_format(floatval($service['monthly_price'] ?? $service['price'] ?? 0), 2); ?></span>
                    <span class="text-gray-300">/Jahr</span>
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
                <button class="mt-8 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                    Jetzt registrieren
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CTA Section -->
<div class="py-20 bg-gradient-to-r from-orange-600 to-red-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-6">
            Bereit für Ihre Domain?
        </h2>
        <p class="text-xl text-orange-100 mb-8">
            Sichern Sie sich noch heute Ihre perfekte Domain bei SpectraHost.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-orange-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <i class="fas fa-user-plus mr-2"></i>Jetzt registrieren
            </a>
            <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-orange-600 transition-colors">
                <i class="fas fa-phone mr-2"></i>Beratung anfordern
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>