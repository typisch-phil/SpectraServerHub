<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'vServer - SpectraHost';
renderHeader($pageTitle);

// Get vServer services from s9281_spectrahost database
$vserverServices = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'vserver' ORDER BY price ASC");
    $stmt->execute();
    $vserverServices = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in vserver.php: " . $e->getMessage());
    $vserverServices = [];
}
?>

<div class="bg-gradient-to-r from-green-600 to-green-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Virtual Private Server
            </h1>
            <p class="mt-6 text-xl text-green-100 max-w-3xl mx-auto">
                Leistungsstarke vServer mit voller Root-Berechtigung und garantierten Ressourcen für maximale Flexibilität.
            </p>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">vServer Features</h2>
            <p class="mt-4 text-lg text-gray-600">Professionelle Virtualisierung mit Enterprise-Hardware</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-server text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">KVM Virtualisierung</h3>
                <p class="mt-2 text-gray-600">Vollständige Virtualisierung mit eigenen Kernel</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-crown text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Root-Zugriff</h3>
                <p class="mt-2 text-gray-600">Vollständige Administratorrechte auf Ihrem Server</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hdd text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">NVMe SSD</h3>
                <p class="mt-2 text-gray-600">Ultraschnelle NVMe-Speicher für beste Performance</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-network-wired text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">1 Gbit/s Port</h3>
                <p class="mt-2 text-gray-600">Hochgeschwindigkeits-Internetanbindung</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-globe text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">IPv4 & IPv6</h3>
                <p class="mt-2 text-gray-600">Dedicated IP-Adressen inklusive</p>
            </div>
            
            <div class="text-center">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-desktop text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">VNC Konsole</h3>
                <p class="mt-2 text-gray-600">Direkter Serverzugriff über Web-Interface</p>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">vServer Pakete</h2>
            <p class="mt-4 text-lg text-gray-600">Skalierbare Lösungen für jeden Bedarf</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (empty($vserverServices)): ?>
                <!-- Fallback Pakete wenn keine Daten aus DB -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-900">VPS Basic</h3>
                    <p class="mt-2 text-gray-600">Einstieg in die vServer-Welt</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€14.99</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            2 vCPU Kerne
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            4 GB RAM
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            50 GB NVMe SSD
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Unlimited Traffic
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border-2 border-green-600 rounded-lg p-6 hover:shadow-lg transition-shadow relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm">Empfohlen</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">VPS Pro</h3>
                    <p class="mt-2 text-gray-600">Perfekt für Unternehmen</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€29.99</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            4 vCPU Kerne
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            8 GB RAM
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            100 GB NVMe SSD
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Priority Support
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-900">VPS Enterprise</h3>
                    <p class="mt-2 text-gray-600">Für höchste Ansprüche</p>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-gray-900">€59.99</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            8 vCPU Kerne
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            16 GB RAM
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            250 GB NVMe SSD
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Managed Services
                        </li>
                    </ul>
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($vserverServices as $service): ?>
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
                    <button class="mt-8 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Jetzt bestellen
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Operating Systems -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Betriebssysteme</h2>
            <p class="mt-4 text-lg text-gray-600">Wählen Sie aus einer Vielzahl von Betriebssystemen</p>
        </div>
        
        <div class="mt-12 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div class="text-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow">
                <i class="fab fa-ubuntu text-4xl text-orange-500 mb-2"></i>
                <p class="font-medium">Ubuntu</p>
            </div>
            <div class="text-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow">
                <i class="fab fa-debian text-4xl text-red-600 mb-2"></i>
                <p class="font-medium">Debian</p>
            </div>
            <div class="text-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow">
                <i class="fab fa-centos text-4xl text-purple-600 mb-2"></i>
                <p class="font-medium">CentOS</p>
            </div>
            <div class="text-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow">
                <i class="fab fa-redhat text-4xl text-red-700 mb-2"></i>
                <p class="font-medium">Red Hat</p>
            </div>
            <div class="text-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow">
                <i class="fab fa-windows text-4xl text-blue-600 mb-2"></i>
                <p class="font-medium">Windows</p>
            </div>
            <div class="text-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow">
                <i class="fab fa-fedora text-4xl text-blue-800 mb-2"></i>
                <p class="font-medium">Fedora</p>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>