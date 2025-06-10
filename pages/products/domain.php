<?php
require_once __DIR__ . '/../../includes/layout.php';
require_once __DIR__ . '/../../includes/database.php';

$db = Database::getInstance();
$connection = $db->getConnection();

// Get domain services
$stmt = $connection->prepare("SELECT * FROM services WHERE type = 'domain' AND active = 1 ORDER BY price ASC");
$stmt->execute();
$domain_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('Domain Registration - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">
                    Domain Registration
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                    Sichern Sie sich Ihre Wunschdomain mit kostenlosem DNS-Management
                </p>
                <div class="flex flex-wrap justify-center gap-4 text-lg">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        WHOIS-Datenschutz
                    </span>
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        DNS-Management
                    </span>
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        E-Mail-Weiterleitung
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Domain Search Section -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 -mt-8 relative z-10">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Domain-Verfügbarkeit prüfen
                </h2>
                <p class="text-gray-600 dark:text-gray-300">
                    Geben Sie Ihre Wunschdomain ein und prüfen Sie deren Verfügbarkeit
                </p>
            </div>
            
            <form class="flex flex-col sm:flex-row gap-4" onsubmit="return false;">
                <div class="flex-1">
                    <input type="text" 
                           placeholder="ihre-domain" 
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                           id="domain-search">
                </div>
                <div class="sm:w-32">
                    <select class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option>.de</option>
                        <option>.com</option>
                        <option>.org</option>
                        <option>.net</option>
                        <option>.eu</option>
                    </select>
                </div>
                <button type="submit" 
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                    Prüfen
                </button>
            </form>
        </div>
    </div>

    <!-- Domain Pricing -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Domain-Preise
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">
                Faire Preise für alle beliebten Domain-Endungen
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($domain_services as $service): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </h3>
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">
                            €<?php echo number_format($service['price'], 2); ?>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">pro Jahr</div>
                    </div>

                    <div class="mb-6">
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>
                        
                        <ul class="space-y-2">
                            <?php 
                            $features = json_decode($service['features'], true);
                            if ($features): ?>
                                <?php foreach ($features as $key => $value): ?>
                                    <li class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                        <svg class="w-4 h-4 text-indigo-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?php 
                                        if ($key === 'whois_privacy') {
                                            echo 'WHOIS-Datenschutz inklusive';
                                        } elseif ($key === 'dns_management') {
                                            echo 'Kostenloses DNS-Management';
                                        } elseif ($key === 'email_forwarding') {
                                            echo $value . ' E-Mail-Weiterleitungen';
                                        } else {
                                            echo htmlspecialchars($key . ': ' . (is_bool($value) ? ($value ? 'Ja' : 'Nein') : $value));
                                        }
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <li class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                <svg class="w-4 h-4 text-indigo-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Kostenlose Nameserver
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-300 text-sm">
                                <svg class="w-4 h-4 text-indigo-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Domain-Verwaltung
                            </li>
                        </ul>
                    </div>

                    <a href="/order?service=<?php echo $service['id']; ?>" 
                       class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-center block">
                        Jetzt registrieren
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Popular TLDs -->
    <div class="bg-white dark:bg-gray-800 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Beliebte Domain-Endungen
                </h2>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">.de</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">ab €12.99/Jahr</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">.com</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">ab €14.99/Jahr</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">.org</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">ab €16.99/Jahr</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">.net</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">ab €16.99/Jahr</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">.eu</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">ab €18.99/Jahr</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">.info</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">ab €19.99/Jahr</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-gray-50 dark:bg-gray-900 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Domain-Features
                </h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="bg-indigo-100 dark:bg-indigo-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">WHOIS-Schutz</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Ihre Daten bleiben privat</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-indigo-100 dark:bg-indigo-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">DNS-Management</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Einfache Verwaltung</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-indigo-100 dark:bg-indigo-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">E-Mail-Weiterleitung</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Kostenlose Weiterleitungen</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-indigo-100 dark:bg-indigo-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">24/7 Support</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Deutscher Support</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>