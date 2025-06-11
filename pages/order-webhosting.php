<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle = 'Webhosting bestellen - SpectraHost';
$pageDescription = 'Bestellen Sie Ihr professionelles Webhosting-Paket mit SSD-Speicher und SSL-Zertifikat.';

// Get webhosting packages from database
$webhostingPackages = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'webspace' ORDER BY id ASC");
    $stmt->execute();
    $webhostingPackages = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in order-webhosting.php: " . $e->getMessage());
    $webhostingPackages = [];
}

// Handle form submission
$orderSuccess = false;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $packageId = intval($_POST['package_id']);
        $domain = trim($_POST['domain']);
        
        // Validate input
        if (empty($domain) || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            throw new Exception('Domain ist ungültig. Bitte geben Sie eine gültige Domain ein.');
        }
        
        // Get package details
        $stmt = $db->prepare("SELECT * FROM service_types WHERE id = ? AND category = 'webspace'");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            throw new Exception('Gewähltes Paket nicht gefunden.');
        }
        
        // Create order in database
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, service_type_id, status, hostname, specifications, created_at) 
            VALUES (?, ?, 'pending', ?, ?, NOW())
        ");
        $orderSpecs = json_encode([
            'domain' => $domain,
            'service_type' => 'webhosting'
        ]);
        $stmt->execute([$_SESSION['user_id'], $packageId, $domain, $orderSpecs]);
        $orderId = $db->lastInsertId();
        
        // Update order status to active (webhosting is provisioned instantly)
        $stmt = $db->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Create service entry
        $stmt = $db->prepare("
            INSERT INTO services (user_id, service_type_id, order_id, status, hostname, created_at) 
            VALUES (?, ?, ?, 'active', ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $packageId, $orderId, $domain]);
        
        $orderSuccess = true;
        $_SESSION['order_domain'] = $domain;
        $_SESSION['order_package'] = $package['name'];
        
    } catch (Exception $e) {
        $orderError = $e->getMessage();
        error_log("Webhosting Order Error: " . $e->getMessage());
        
        // Update order status to failed if order was created
        if (isset($orderId)) {
            $stmt = $db->prepare("UPDATE orders SET status = 'failed', error_message = ? WHERE id = ?");
            $stmt->execute([$e->getMessage(), $orderId]);
        }
    }
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Webhosting bestellen
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Wählen Sie Ihr Webhosting-Paket und starten Sie noch heute mit Ihrer Website.
            </p>
        </div>
    </div>
</div>

<?php if ($orderSuccess): ?>
<!-- Success Message -->
<div class="py-16 bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-green-800 border border-green-600 rounded-lg p-8 text-center">
            <i class="fas fa-check-circle text-green-400 text-6xl mb-6"></i>
            <h2 class="text-2xl font-bold text-white mb-4">Webhosting erfolgreich bestellt!</h2>
            <p class="text-green-200 mb-6">
                Ihr Webhosting-Paket wurde erfolgreich eingerichtet und ist bereit für die Nutzung.
            </p>
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div>
                        <span class="text-gray-400">Domain:</span>
                        <span class="text-white font-mono"><?php echo htmlspecialchars($_SESSION['order_domain']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Paket:</span>
                        <span class="text-white"><?php echo htmlspecialchars($_SESSION['order_package']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Status:</span>
                        <span class="text-green-400">Aktiv</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Erstellt:</span>
                        <span class="text-white"><?php echo date('d.m.Y H:i'); ?></span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard/services" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-server mr-2"></i>Zu meinen Services
                </a>
                <a href="/dashboard" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Order Form -->
<div class="py-16 bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($orderError): ?>
        <div class="bg-red-800 border border-red-600 rounded-lg p-4 mb-8">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                <span class="text-red-200"><?php echo htmlspecialchars($orderError); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <!-- Package Selection -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">1. Webhosting-Paket wählen</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if (!empty($webhostingPackages)): ?>
                        <?php foreach ($webhostingPackages as $package): ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="package_id" value="<?php echo $package['id']; ?>" class="sr-only peer" required>
                            <div class="border-2 border-gray-600 peer-checked:border-blue-500 rounded-lg p-6 hover:border-gray-500 transition-colors bg-gray-700">
                                <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($package['name']); ?></h3>
                                <p class="text-gray-300 text-sm mt-1"><?php echo htmlspecialchars($package['description'] ?? ''); ?></p>
                                <div class="mt-4">
                                    <span class="text-2xl font-bold text-white">€<?php echo number_format(floatval($package['monthly_price'] ?? 0), 2); ?></span>
                                    <span class="text-gray-300">/Monat</span>
                                </div>
                                <?php if (!empty($package['features'])): ?>
                                <ul class="mt-4 space-y-2 text-sm text-gray-300">
                                    <?php 
                                    $features = is_string($package['features']) ? json_decode($package['features'], true) : $package['features'];
                                    if (is_array($features)):
                                        foreach ($features as $feature): ?>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; endif; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback packages -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="package_id" value="starter" class="sr-only peer" required>
                            <div class="border-2 border-gray-600 peer-checked:border-blue-500 rounded-lg p-6 hover:border-gray-500 transition-colors bg-gray-700">
                                <h3 class="text-lg font-semibold text-white">Starter</h3>
                                <p class="text-gray-300 text-sm mt-1">Perfekt für kleine Websites</p>
                                <div class="mt-4">
                                    <span class="text-2xl font-bold text-white">€4,99</span>
                                    <span class="text-gray-300">/Monat</span>
                                </div>
                                <ul class="mt-4 space-y-2 text-sm text-gray-300">
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>10 GB SSD Speicher</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>1 Domain inklusive</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>5 E-Mail-Postfächer</li>
                                </ul>
                            </div>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Domain Configuration -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">2. Domain-Konfiguration</h2>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="domain" class="block text-sm font-medium text-gray-300 mb-2">
                            Ihre Domain <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="domain" 
                               name="domain" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="beispiel.de"
                               pattern="[a-z0-9.-]+\.[a-z]{2,}"
                               title="Bitte geben Sie eine gültige Domain ein"
                               required>
                        <p class="mt-1 text-xs text-gray-400">Geben Sie Ihre bereits registrierte Domain ein oder registrieren Sie eine neue</p>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">3. Bestellung abschließen</h2>
                
                <div class="bg-gray-700 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Bestellübersicht</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-300">Gewähltes Paket:</span>
                            <span class="text-white" id="selected-package">Bitte wählen Sie ein Paket</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Einrichtung:</span>
                            <span class="text-green-400">Kostenlos</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Bereitstellung:</span>
                            <span class="text-blue-400">Sofort</span>
                        </div>
                        <hr class="border-gray-600">
                        <div class="flex justify-between text-lg font-semibold">
                            <span class="text-white">Gesamt monatlich:</span>
                            <span class="text-white" id="total-price">€0,00</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        name="place_order"
                        class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    <i class="fas fa-globe mr-2"></i>Webhosting jetzt bestellen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Package selection handling
document.addEventListener('DOMContentLoaded', function() {
    const packageRadios = document.querySelectorAll('input[name="package_id"]');
    const selectedPackageSpan = document.getElementById('selected-package');
    const totalPriceSpan = document.getElementById('total-price');
    
    packageRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const label = this.closest('label');
            const packageName = label.querySelector('h3').textContent;
            const priceElement = label.querySelector('.text-2xl');
            const price = priceElement ? priceElement.textContent : '€0,00';
            
            selectedPackageSpan.textContent = packageName;
            totalPriceSpan.textContent = price;
        });
    });
    
    // Domain validation
    const domainInput = document.getElementById('domain');
    domainInput.addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9.-]/g, '');
    });
});
</script>
<?php endif; ?>

<?php renderFooter(); ?>