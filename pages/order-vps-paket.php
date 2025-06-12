<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/layout.php';

// VPS-Pakete aus der Datenbank laden
$db = Database::getInstance();
$vpsServices = [];

try {
    $stmt = $db->query("SELECT * FROM service_types WHERE category = 'vserver' AND is_active = 1 ORDER BY monthly_price ASC");
    $vpsServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading VPS services: " . $e->getMessage());
    $vpsServices = [];
}

$pageTitle = "VPS Pakete bestellen - SpectraHost";
$pageDescription = "Hochperformante VPS-Server für Ihre Projekte. Wählen Sie aus unseren optimierten Paketen.";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-purple-900 via-blue-900 to-indigo-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    VPS <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Pakete</span>
                </h1>
                <p class="text-xl text-gray-200 mb-8 max-w-3xl mx-auto">
                    Hochperformante Virtual Private Server für maximale Flexibilität und Kontrolle
                </p>
                <div class="flex flex-wrap justify-center gap-4 text-gray-300">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <span>SSD-Speicher</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <span>Root-Zugriff</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <span>99,9% Uptime</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VPS Pakete -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        
        <?php if (empty($vpsServices)): ?>
        <!-- Fallback wenn keine Services verfügbar -->
        <div class="text-center py-16">
            <div class="bg-gray-800 rounded-2xl p-8 max-w-md mx-auto">
                <i class="fas fa-server text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-xl font-bold text-white mb-2">Pakete werden geladen</h3>
                <p class="text-gray-400">Unsere VPS-Pakete werden gerade aktualisiert. Bitte versuchen Sie es in Kürze erneut.</p>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Pakete Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($vpsServices as $index => $service): 
                $specs = json_decode($service['specifications'] ?? '{}', true);
                $isPopular = $index === 1; // Mittleres Paket als "Beliebt" markieren
            ?>
            <div class="relative bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 <?php echo $isPopular ? 'border-purple-500' : 'border-gray-700'; ?> p-8 hover:border-purple-400 transition-all duration-300">
                
                <?php if ($isPopular): ?>
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-gradient-to-r from-purple-500 to-blue-500 text-white px-4 py-2 rounded-full text-sm font-medium">
                        Beliebt
                    </span>
                </div>
                <?php endif; ?>
                
                <!-- Paket Header -->
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="text-gray-400 text-sm mb-4"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-white">€<?php echo number_format($service['monthly_price'], 2); ?></span>
                        <span class="text-gray-400">/Monat</span>
                    </div>
                </div>
                
                <!-- Spezifikationen -->
                <div class="space-y-4 mb-8">
                    <?php if (!empty($specs)): ?>
                        <?php foreach ($specs as $key => $value): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $key)); ?></span>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($value); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Standard-Spezifikationen wenn keine JSON-Specs vorhanden -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">CPU Cores</span>
                            <span class="text-white font-medium"><?php echo $index + 1; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">RAM</span>
                            <span class="text-white font-medium"><?php echo ($index + 1) * 2; ?> GB</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">SSD Storage</span>
                            <span class="text-white font-medium"><?php echo ($index + 1) * 25; ?> GB</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Traffic</span>
                            <span class="text-white font-medium">Unlimited</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Features -->
                <div class="space-y-3 mb-8">
                    <div class="flex items-center text-gray-300">
                        <i class="fas fa-check text-green-400 mr-3"></i>
                        <span>Full Root-Zugriff</span>
                    </div>
                    <div class="flex items-center text-gray-300">
                        <i class="fas fa-check text-green-400 mr-3"></i>
                        <span>99,9% Uptime SLA</span>
                    </div>
                    <div class="flex items-center text-gray-300">
                        <i class="fas fa-check text-green-400 mr-3"></i>
                        <span>DDoS-Schutz</span>
                    </div>
                    <div class="flex items-center text-gray-300">
                        <i class="fas fa-check text-green-400 mr-3"></i>
                        <span>24/7 Support</span>
                    </div>
                    <div class="flex items-center text-gray-300">
                        <i class="fas fa-check text-green-400 mr-3"></i>
                        <span>Kostenloses Setup</span>
                    </div>
                </div>
                
                <!-- Bestellen Button -->
                <button onclick="orderVPS(<?php echo $service['id']; ?>)" 
                        class="w-full bg-gradient-to-r <?php echo $isPopular ? 'from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700' : 'from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600'; ?> text-white py-3 px-6 rounded-lg font-medium transition-all duration-300 transform hover:scale-105">
                    Jetzt bestellen
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Zusätzliche Informationen -->
        <div class="mt-16 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="bg-blue-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-rocket text-white text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-white mb-2">Sofort verfügbar</h4>
                    <p class="text-gray-400 text-sm">Ihr VPS ist binnen weniger Minuten einsatzbereit</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-green-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-white mb-2">Höchste Sicherheit</h4>
                    <p class="text-gray-400 text-sm">Enterprise-Grade Security und DDoS-Schutz</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-purple-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-white mb-2">Skalierbar</h4>
                    <p class="text-gray-400 text-sm">Jederzeit upgraden und erweitern</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-yellow-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-white mb-2">Premium Support</h4>
                    <p class="text-gray-400 text-sm">24/7 deutschsprachiger Support</p>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="mt-16">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Häufig gestellte Fragen</h2>
            <div class="max-w-4xl mx-auto space-y-6">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-semibold text-white mb-3">Wie schnell ist mein VPS verfügbar?</h3>
                    <p class="text-gray-400">Ihr VPS wird automatisch nach der Bestellung provisioniert und ist innerhalb von 5-10 Minuten vollständig einsatzbereit.</p>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-semibold text-white mb-3">Welche Betriebssysteme werden unterstützt?</h3>
                    <p class="text-gray-400">Wir unterstützen alle gängigen Linux-Distributionen wie Ubuntu, CentOS, Debian und Windows Server auf Anfrage.</p>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-semibold text-white mb-3">Kann ich mein VPS upgraden?</h3>
                    <p class="text-gray-400">Ja, Sie können Ihr VPS jederzeit upgraden. Zusätzliche Ressourcen sind sofort verfügbar.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bestellmodal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-white">VPS bestellen</h3>
            <button onclick="closeOrderModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="orderContent" class="mb-6">
            <!-- Wird dynamisch gefüllt -->
        </div>
        
        <div class="flex space-x-3">
            <button onclick="proceedToOrder()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-colors">
                Zur Bestellung
            </button>
            <button onclick="closeOrderModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors">
                Abbrechen
            </button>
        </div>
    </div>
</div>

<script>
let selectedServiceId = null;

function orderVPS(serviceId) {
    selectedServiceId = serviceId;
    
    // Service-Details anzeigen
    const serviceElements = document.querySelectorAll('[onclick="orderVPS(' + serviceId + ')"]');
    if (serviceElements.length > 0) {
        const serviceCard = serviceElements[0].closest('.relative');
        const serviceName = serviceCard.querySelector('h3').textContent;
        const servicePrice = serviceCard.querySelector('.text-4xl').textContent;
        
        document.getElementById('orderContent').innerHTML = `
            <div class="text-center">
                <h4 class="text-lg font-semibold text-white mb-2">${serviceName}</h4>
                <p class="text-2xl font-bold text-blue-400 mb-4">${servicePrice}/Monat</p>
                <p class="text-gray-300 mb-4">Sie sind dabei, dieses VPS-Paket zu bestellen. Nach der Bestätigung werden Sie zur Bestellübersicht weitergeleitet.</p>
            </div>
        `;
    }
    
    document.getElementById('orderModal').classList.remove('hidden');
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
    selectedServiceId = null;
}

function proceedToOrder() {
    if (selectedServiceId) {
        // Zur Bestellseite weiterleiten mit Service-ID
        window.location.href = `/order?service_id=${selectedServiceId}&type=vps`;
    }
    closeOrderModal();
}

// Smooth Scroll für Anker-Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
</script>

<?php
renderFooter();
?>