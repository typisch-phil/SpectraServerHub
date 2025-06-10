<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'Domain - SpectraHost';
renderHeader($pageTitle);

// Get domain services from s9281_spectrahost database
$domainServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'domain' ORDER BY price ASC");
    $stmt->execute();
    $domainServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in domain.php: " . $e->getMessage());
    $domainServices = [];
}
?>

<div class="bg-gradient-to-r from-orange-600 to-orange-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Domain Registration
            </h1>
            <p class="mt-6 text-xl text-orange-100 max-w-3xl mx-auto">
                Sichern Sie sich Ihre perfekte Domain. Über 500 TLDs verfügbar mit kostenlosem DNS-Management und WHOIS-Schutz.
            </p>
        </div>
    </div>
</div>

<!-- Domain Search -->
<div class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Finden Sie Ihre perfekte Domain</h2>
            <p class="mt-4 text-lg text-gray-600">Prüfen Sie die Verfügbarkeit Ihrer Wunschdomain</p>
        </div>
        
        <div class="mt-8">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" placeholder="Ihre Wunschdomain eingeben..." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg">
                </div>
                <button class="px-8 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium">
                    Domain suchen
                </button>
            </div>
            
            <div class="mt-4 flex flex-wrap gap-2 justify-center">
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">.de</span>
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">.com</span>
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">.org</span>
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">.net</span>
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">.eu</span>
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">.info</span>
            </div>
        </div>
    </div>
</div>

<!-- Popular TLDs -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Beliebte Domain-Endungen</h2>
            <p class="mt-4 text-lg text-gray-600">Attraktive Preise für alle gängigen TLDs</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if (empty($domainServices)): ?>
                <!-- Fallback Domain-Preise wenn keine Daten aus DB -->
                <div class="bg-white rounded-lg p-6 text-center border hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-bold text-gray-900">.de</h3>
                    <p class="text-3xl font-bold text-orange-600 mt-2">€8.99</p>
                    <p class="text-gray-600 mt-1">pro Jahr</p>
                    <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                        Registrieren
                    </button>
                </div>
                
                <div class="bg-white rounded-lg p-6 text-center border hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-bold text-gray-900">.com</h3>
                    <p class="text-3xl font-bold text-orange-600 mt-2">€12.99</p>
                    <p class="text-gray-600 mt-1">pro Jahr</p>
                    <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                        Registrieren
                    </button>
                </div>
                
                <div class="bg-white rounded-lg p-6 text-center border hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-bold text-gray-900">.org</h3>
                    <p class="text-3xl font-bold text-orange-600 mt-2">€14.99</p>
                    <p class="text-gray-600 mt-1">pro Jahr</p>
                    <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                        Registrieren
                    </button>
                </div>
                
                <div class="bg-white rounded-lg p-6 text-center border hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-bold text-gray-900">.net</h3>
                    <p class="text-3xl font-bold text-orange-600 mt-2">€15.99</p>
                    <p class="text-gray-600 mt-1">pro Jahr</p>
                    <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                        Registrieren
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($domainServices as $service): ?>
                <div class="bg-white rounded-lg p-6 text-center border hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="text-3xl font-bold text-orange-600 mt-2">€<?php echo number_format($service['price'], 2); ?></p>
                    <p class="text-gray-600 mt-1">pro Jahr</p>
                    <button class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                        Registrieren
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Features -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Domain Features</h2>
            <p class="mt-4 text-lg text-gray-600">Alles inklusive für Ihre Domain-Verwaltung</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-dns text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">DNS-Management</h3>
                <p class="mt-2 text-gray-600">Professionelles DNS mit Web-Interface</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">WHOIS-Schutz</h3>
                <p class="mt-2 text-gray-600">Kostenloser Schutz Ihrer persönlichen Daten</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sync-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Domain Transfer</h3>
                <p class="mt-2 text-gray-600">Einfacher Transfer von anderen Anbietern</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">E-Mail Forwarding</h3>
                <p class="mt-2 text-gray-600">Kostenlose E-Mail-Weiterleitung</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-globe text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Subdomain-Support</h3>
                <p class="mt-2 text-gray-600">Unbegrenzte Subdomains inklusive</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Domain Lock</h3>
                <p class="mt-2 text-gray-600">Schutz vor unbefugtem Transfer</p>
            </div>
        </div>
    </div>
</div>

<!-- Domain Extensions -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Alle verfügbaren Domain-Endungen</h2>
            <p class="mt-4 text-lg text-gray-600">Über 500 TLDs für jeden Zweck</p>
        </div>
        
        <div class="mt-12 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php 
            $tlds = ['.de', '.com', '.org', '.net', '.info', '.biz', '.eu', '.at', '.ch', '.uk', '.fr', '.it', '.es', '.nl', '.be', '.pl', '.cz', '.shop', '.store', '.online', '.tech', '.app', '.dev', '.io'];
            foreach ($tlds as $tld): ?>
            <div class="bg-white rounded-lg p-4 text-center border hover:shadow-md transition-shadow">
                <span class="font-mono font-bold text-lg"><?php echo $tld; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <button class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                Alle TLDs anzeigen
            </button>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-orange-600 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white">Bereit für Ihre neue Domain?</h2>
        <p class="mt-4 text-xl text-orange-100">Registrieren Sie noch heute Ihre Wunschdomain</p>
        <div class="mt-8">
            <a href="/register" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-orange-600 bg-white hover:bg-orange-50 transition-colors">
                Jetzt Domain registrieren
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>