<?php 
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

$pageTitle = 'Domains - SpectraHost';
renderHeader($pageTitle);

// Get domain services
$stmt = $db->prepare("SELECT * FROM services WHERE type = 'domain' AND active = 1 ORDER BY price ASC");
$stmt->execute();
$domainServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-gradient-to-r from-orange-600 to-orange-800 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Premium Domains</h1>
        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
            Sichern Sie sich Ihre Wunschdomain mit kostenlosen DNS-Services, 
            E-Mail-Weiterleitung und professioneller Domain-Verwaltung.
        </p>
        
        <!-- Domain Search -->
        <div class="max-w-2xl mx-auto mb-8">
            <div class="flex bg-white rounded-lg shadow-lg overflow-hidden">
                <input type="text" 
                       id="domain-search" 
                       placeholder="Ihre Wunschdomain eingeben..." 
                       class="flex-1 px-6 py-4 text-gray-900 focus:outline-none text-lg">
                <button onclick="searchDomain()" 
                        class="bg-orange-600 hover:bg-orange-700 px-8 py-4 text-white font-semibold transition-colors">
                    <i class="fas fa-search mr-2"></i>Prüfen
                </button>
            </div>
        </div>
        
        <div class="flex flex-wrap justify-center gap-4">
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-shield-alt mr-2"></i>
                Kostenloser Domain-Schutz
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-dns mr-2"></i>
                Professionelle DNS-Services
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-envelope mr-2"></i>
                E-Mail-Weiterleitung inklusive
            </div>
        </div>
    </div>
</div>

<!-- Domain Search Results -->
<div id="search-results" class="hidden py-16 bg-gray-50 dark:bg-gray-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold mb-4">Verfügbarkeit für "<span id="searched-domain"></span>"</h2>
        </div>
        <div id="results-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Results will be populated here -->
        </div>
    </div>
</div>

<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Beliebte Domain-Endungen</h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Wählen Sie aus über 500 Domain-Endungen die passende für Ihr Projekt
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($domainServices as $service): ?>
            <div class="card hover-lift text-center">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-link text-2xl text-orange-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <div class="text-3xl font-bold text-orange-600 mb-2">
                        €<?php echo number_format($service['price'], 2); ?>/Jahr
                    </div>
                </div>
                
                <div class="space-y-3 mb-8 text-left">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 w-5 mr-3"></i>
                        <span>Kostenlose DNS-Verwaltung</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 w-5 mr-3"></i>
                        <span>E-Mail-Weiterleitung</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 w-5 mr-3"></i>
                        <span>Domain-Schutz inklusive</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 w-5 mr-3"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
                
                <a href="/order?service=<?php echo $service['id']; ?>" 
                   class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-6 rounded-lg transition-colors text-center block">
                    Jetzt registrieren
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Domain Extensions Grid -->
<div class="bg-gray-50 dark:bg-gray-800 py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Alle verfügbaren Endungen</h2>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.de</div>
                <div class="text-sm text-gray-600">€9.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.com</div>
                <div class="text-sm text-gray-600">€12.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.org</div>
                <div class="text-sm text-gray-600">€14.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.net</div>
                <div class="text-sm text-gray-600">€15.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.eu</div>
                <div class="text-sm text-gray-600">€8.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.info</div>
                <div class="text-sm text-gray-600">€16.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.biz</div>
                <div class="text-sm text-gray-600">€18.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.shop</div>
                <div class="text-sm text-gray-600">€35.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.online</div>
                <div class="text-sm text-gray-600">€39.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.tech</div>
                <div class="text-sm text-gray-600">€49.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">.app</div>
                <div class="text-sm text-gray-600">€19.99/Jahr</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg text-center">
                <div class="text-lg font-bold">+500 weitere</div>
                <div class="text-sm text-gray-600">ab €5.99/Jahr</div>
            </div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Domain-Services inklusive</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-2xl text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Domain-Schutz</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Whois-Privacy und Domain-Lock zum Schutz vor unerwünschten Transfers
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-dns text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">DNS-Management</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Vollständige DNS-Kontrolle mit A, CNAME, MX und TXT Records
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">E-Mail Services</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Kostenlose E-Mail-Weiterleitung und Catch-All Funktionalität
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function searchDomain() {
    const domain = document.getElementById('domain-search').value.trim();
    if (!domain) {
        showNotification('Bitte geben Sie eine Domain ein', 'error');
        return;
    }
    
    // Show results section
    const resultsSection = document.getElementById('search-results');
    const searchedDomain = document.getElementById('searched-domain');
    const resultsGrid = document.getElementById('results-grid');
    
    searchedDomain.textContent = domain;
    resultsSection.classList.remove('hidden');
    
    // Mock results for demonstration
    const extensions = ['.de', '.com', '.org', '.net'];
    const prices = ['€9.99', '€12.99', '€14.99', '€15.99'];
    
    resultsGrid.innerHTML = extensions.map((ext, index) => {
        const available = Math.random() > 0.3; // Random availability
        return `
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg border ${available ? 'border-green-200' : 'border-red-200'}">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold">${domain}${ext}</span>
                    <span class="text-sm ${available ? 'text-green-600' : 'text-red-600'}">
                        ${available ? 'Verfügbar' : 'Belegt'}
                    </span>
                </div>
                <div class="text-lg font-bold text-orange-600 mb-2">${prices[index]}/Jahr</div>
                ${available ? 
                    `<button onclick="orderDomain('${domain}${ext}')" 
                             class="w-full bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded transition-colors">
                        Registrieren
                     </button>` :
                    `<button class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded cursor-not-allowed" disabled>
                        Nicht verfügbar
                     </button>`
                }
            </div>
        `;
    }).join('');
    
    // Scroll to results
    resultsSection.scrollIntoView({ behavior: 'smooth' });
}

function orderDomain(domain) {
    // Redirect to order page with domain parameter
    window.location.href = `/order?domain=${encodeURIComponent(domain)}`;
}

// Allow Enter key for search
document.getElementById('domain-search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchDomain();
    }
});
</script>

<?php renderFooter(); ?>