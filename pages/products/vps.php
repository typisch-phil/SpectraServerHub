<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = "VPS Server - Virtual Private Server - SpectraHost";
$pageDescription = "Leistungsstarke VPS Server mit garantierten Ressourcen. Flexible Konfiguration, SSD-Speicher und 24/7 Support.";

renderHeader($pageTitle, $pageDescription);

// VPS Services aus der Datenbank laden
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'VPS' AND is_active = 1 ORDER BY monthly_price ASC");
$stmt->execute();
$vpsServices = $stmt->fetchAll();
?>

<div class="min-h-screen bg-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-purple-900 via-blue-900 to-indigo-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    VPS <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Server</span>
                </h1>
                <p class="text-xl text-gray-200 mb-8 max-w-3xl mx-auto">
                    Leistungsstarke Virtual Private Server mit garantierten Ressourcen, SSD-Speicher und vollständiger Root-Kontrolle
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/order-vps" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-cog mr-2"></i>VPS Konfigurator
                    </a>
                    <a href="#packages" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-purple-600 transition-colors">
                        <i class="fas fa-list mr-2"></i>Pakete anzeigen
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-20 bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Warum SpectraHost VPS?</h2>
                <p class="text-xl text-gray-300">Premium-Features für maximale Performance</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-gradient-to-br from-gray-700 to-gray-800 p-6 rounded-2xl border border-gray-600">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-rocket text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">SSD Performance</h3>
                    <p class="text-gray-300">Blitzschnelle NVMe SSD-Speicher für maximale I/O-Performance</p>
                </div>

                <div class="bg-gradient-to-br from-gray-700 to-gray-800 p-6 rounded-2xl border border-gray-600">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">DDoS-Schutz</h3>
                    <p class="text-gray-300">Umfassender Schutz vor DDoS-Attacken inklusive</p>
                </div>

                <div class="bg-gradient-to-br from-gray-700 to-gray-800 p-6 rounded-2xl border border-gray-600">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-tools text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Root-Zugriff</h3>
                    <p class="text-gray-300">Vollständige Kontrolle über Ihren Server</p>
                </div>

                <div class="bg-gradient-to-br from-gray-700 to-gray-800 p-6 rounded-2xl border border-gray-600">
                    <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">24/7 Support</h3>
                    <p class="text-gray-300">Rund um die Uhr verfügbarer deutscher Support</p>
                </div>
            </div>
        </div>
    </div>

    <!-- VPS Konfigurator CTA -->
    <div class="py-16 bg-gradient-to-r from-blue-900 to-purple-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Individueller VPS-Konfigurator</h2>
            <p class="text-xl text-gray-200 mb-8">
                Stellen Sie Ihren perfekten VPS-Server genau nach Ihren Anforderungen zusammen
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                    <i class="fas fa-microchip text-blue-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold">CPU & RAM</h3>
                    <p class="text-gray-300 text-sm">1-8 CPU Cores, 2-16 GB RAM</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                    <i class="fas fa-hdd text-green-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold">SSD-Speicher</h3>
                    <p class="text-gray-300 text-sm">25-250 GB NVMe SSD</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                    <i class="fas fa-server text-purple-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold">Betriebssystem</h3>
                    <p class="text-gray-300 text-sm">Linux & Windows verfügbar</p>
                </div>
            </div>
            <a href="/order-vps" class="bg-white text-purple-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors inline-flex items-center">
                <i class="fas fa-cog mr-2"></i>VPS jetzt konfigurieren
            </a>
        </div>
    </div>

    <!-- VPS Pakete -->
    <?php if (!empty($vpsServices)): ?>
    <div id="packages" class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">VPS-Pakete</h2>
                <p class="text-xl text-gray-300">Vorkonfigurierte Pakete für den schnellen Start</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($vpsServices as $service): ?>
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-8 hover:border-blue-500 transition-all duration-300 transform hover:scale-105">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($service['name']) ?></h3>
                        <div class="text-4xl font-bold text-blue-400 mb-2">
                            €<?= number_format($service['monthly_price'], 2) ?>
                        </div>
                        <div class="text-gray-400">pro Monat</div>
                    </div>
                    
                    <div class="mb-8">
                        <?php 
                        $specs = json_decode($service['specifications'], true);
                        if ($specs): 
                        ?>
                        <ul class="space-y-3">
                            <?php foreach ($specs as $spec): ?>
                            <li class="flex items-center text-gray-300">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                <?= htmlspecialchars($spec) ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <div class="text-gray-400"><?= htmlspecialchars($service['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button onclick="orderVPSService(<?= $service['id'] ?>, '<?= htmlspecialchars($service['name']) ?>', <?= $service['monthly_price'] ?>)" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-300">
                        <i class="fas fa-shopping-cart mr-2"></i>Jetzt bestellen
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Betriebssysteme -->
    <div class="py-20 bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Unterstützte Betriebssysteme</h2>
                <p class="text-xl text-gray-300">Eine große Auswahl an Linux-Distributionen und Windows</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <div class="bg-gray-700 rounded-lg p-6 text-center hover:bg-gray-600 transition-colors">
                    <i class="fab fa-ubuntu text-orange-400 text-3xl mb-2"></i>
                    <div class="text-white font-medium">Ubuntu</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-6 text-center hover:bg-gray-600 transition-colors">
                    <i class="fab fa-debian text-red-400 text-3xl mb-2"></i>
                    <div class="text-white font-medium">Debian</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-6 text-center hover:bg-gray-600 transition-colors">
                    <i class="fab fa-centos text-purple-400 text-3xl mb-2"></i>
                    <div class="text-white font-medium">CentOS</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-6 text-center hover:bg-gray-600 transition-colors">
                    <i class="fab fa-redhat text-red-600 text-3xl mb-2"></i>
                    <div class="text-white font-medium">Rocky Linux</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-6 text-center hover:bg-gray-600 transition-colors">
                    <i class="fab fa-windows text-blue-400 text-3xl mb-2"></i>
                    <div class="text-white font-medium">Windows</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-6 text-center hover:bg-gray-600 transition-colors">
                    <i class="fas fa-server text-gray-400 text-3xl mb-2"></i>
                    <div class="text-white font-medium">Weitere</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="py-20 bg-gray-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Häufige Fragen</h2>
                <p class="text-xl text-gray-300">Alles was Sie über unsere VPS wissen müssen</p>
            </div>
            
            <div class="space-y-6">
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <h3 class="text-xl font-semibold text-white mb-3">Was ist ein VPS?</h3>
                    <p class="text-gray-300">Ein Virtual Private Server (VPS) ist ein virtueller Server mit garantierten Ressourcen und vollem Root-Zugriff. Sie erhalten die Leistung eines dedizierten Servers zu einem Bruchteil der Kosten.</p>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <h3 class="text-xl font-semibold text-white mb-3">Wie schnell ist mein VPS einsatzbereit?</h3>
                    <p class="text-gray-300">Ihr VPS wird automatisch innerhalb von wenigen Minuten nach der Bestellung und Zahlung bereitgestellt. Sie erhalten sofort Ihre Zugangsdaten per E-Mail.</p>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <h3 class="text-xl font-semibold text-white mb-3">Kann ich mein VPS später erweitern?</h3>
                    <p class="text-gray-300">Ja, Sie können CPU, RAM und Speicher jederzeit über Ihr Kundendashboard erweitern. Downgrades sind ebenfalls möglich.</p>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <h3 class="text-xl font-semibold text-white mb-3">Sind Backups inklusive?</h3>
                    <p class="text-gray-300">Manuelle Backups können Sie jederzeit kostenlos erstellen. Automatische tägliche Backups sind als optionaler Service für €5/Monat verfügbar.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-purple-900 to-blue-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Bereit für Ihren VPS?</h2>
            <p class="text-xl text-gray-200 mb-8">
                Starten Sie noch heute mit einem leistungsstarken Virtual Private Server
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/order-vps" class="bg-white text-purple-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-cog mr-2"></i>VPS konfigurieren
                </a>
                <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-purple-600 transition-colors">
                    <i class="fas fa-phone mr-2"></i>Beratung anfordern
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Bestellfunktion für VPS-Konfigurator
function orderVPS() {
    window.location.href = '/order-vps';
}

// Bestellfunktion für datenbankbasierte VPS-Services
function orderVPSService(serviceId, serviceName, price) {
    window.location.href = `/order?service_id=${serviceId}&type=vps`;
}
</script>

<?php renderFooter(); ?>