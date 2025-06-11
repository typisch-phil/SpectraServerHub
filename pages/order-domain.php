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

$pageTitle = 'Domain registrieren - SpectraHost';
$pageDescription = 'Registrieren Sie Ihre Wunschdomain aus über 500 verfügbaren Endungen.';

// Handle form submission
$orderSuccess = false;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $domainName = trim($_POST['domain_name']);
        $domainTld = $_POST['domain_tld'];
        $registrationYears = intval($_POST['registration_years']);
        $whoisPrivacy = isset($_POST['whois_privacy']);
        
        // Validate input
        if (empty($domainName) || !preg_match('/^[a-z0-9-]+$/', $domainName)) {
            throw new Exception('Domainname ist ungültig. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.');
        }
        
        if (strlen($domainName) < 2 || strlen($domainName) > 63) {
            throw new Exception('Domainname muss zwischen 2 und 63 Zeichen lang sein.');
        }
        
        if ($registrationYears < 1 || $registrationYears > 10) {
            throw new Exception('Registrierungsdauer muss zwischen 1 und 10 Jahren liegen.');
        }
        
        $fullDomain = $domainName . '.' . $domainTld;
        
        // Domain pricing
        $domainPrices = [
            'de' => 0.99,
            'com' => 12.99,
            'net' => 15.99,
            'org' => 14.99,
            'info' => 16.99,
            'biz' => 18.99,
            'eu' => 8.99,
            'at' => 12.99,
            'ch' => 14.99,
            'co.uk' => 11.99
        ];
        
        $domainPrice = $domainPrices[$domainTld] ?? 19.99;
        $whoisPrivacyPrice = $whoisPrivacy ? 2.99 : 0;
        $totalAnnualPrice = ($domainPrice + $whoisPrivacyPrice) * $registrationYears;
        
        // Create order in database
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, service_type_id, status, hostname, specifications, created_at) 
            VALUES (?, ?, 'pending', ?, ?, NOW())
        ");
        $orderSpecs = json_encode([
            'domain' => $fullDomain,
            'tld' => $domainTld,
            'registration_years' => $registrationYears,
            'whois_privacy' => $whoisPrivacy,
            'annual_price' => $domainPrice,
            'whois_privacy_price' => $whoisPrivacyPrice,
            'total_price' => $totalAnnualPrice,
            'service_type' => 'domain'
        ]);
        $stmt->execute([$_SESSION['user_id'], 1, $fullDomain, $orderSpecs]); // Using service_type_id = 1 as fallback
        $orderId = $db->lastInsertId();
        
        // Update order status to active (domain registration handled separately)
        $stmt = $db->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Create service entry
        $stmt = $db->prepare("
            INSERT INTO services (user_id, service_type_id, order_id, status, hostname, created_at) 
            VALUES (?, ?, ?, 'active', ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], 1, $orderId, $fullDomain]);
        
        $orderSuccess = true;
        $_SESSION['order_domain'] = $fullDomain;
        $_SESSION['order_total_price'] = $totalAnnualPrice;
        $_SESSION['order_years'] = $registrationYears;
        
    } catch (Exception $e) {
        $orderError = $e->getMessage();
        error_log("Domain Order Error: " . $e->getMessage());
        
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
                Domain registrieren
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Sichern Sie sich Ihre perfekte Domain aus über 500 verfügbaren Endungen.
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
            <h2 class="text-2xl font-bold text-white mb-4">Domain erfolgreich bestellt!</h2>
            <p class="text-green-200 mb-6">
                Ihre Domain wurde erfolgreich registriert und wird in Kürze aktiviert.
            </p>
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div>
                        <span class="text-gray-400">Domain:</span>
                        <span class="text-white font-mono"><?php echo htmlspecialchars($_SESSION['order_domain']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Laufzeit:</span>
                        <span class="text-white"><?php echo htmlspecialchars($_SESSION['order_years']); ?> Jahr(e)</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Gesamtpreis:</span>
                        <span class="text-white">€<?php echo number_format($_SESSION['order_total_price'], 2); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Status:</span>
                        <span class="text-yellow-400">Wird aktiviert</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard/services" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-link mr-2"></i>Zu meinen Services
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
            <!-- Domain Search -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">1. Domain wählen</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="domain_name" class="block text-sm font-medium text-gray-300 mb-2">
                            Domainname <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="domain_name" 
                               name="domain_name" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                               placeholder="beispiel"
                               pattern="[a-z0-9-]+"
                               title="Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt"
                               required>
                        <p class="mt-1 text-xs text-gray-400">Ohne Domainendung (.de, .com, etc.)</p>
                    </div>
                    
                    <div>
                        <label for="domain_tld" class="block text-sm font-medium text-gray-300 mb-2">
                            Domainendung <span class="text-red-400">*</span>
                        </label>
                        <select id="domain_tld" 
                                name="domain_tld" 
                                class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                                required>
                            <option value="">Bitte wählen...</option>
                            <option value="de" data-price="0.99">.de - €0,99/Jahr</option>
                            <option value="com" data-price="12.99">.com - €12,99/Jahr</option>
                            <option value="net" data-price="15.99">.net - €15,99/Jahr</option>
                            <option value="org" data-price="14.99">.org - €14,99/Jahr</option>
                            <option value="info" data-price="16.99">.info - €16,99/Jahr</option>
                            <option value="biz" data-price="18.99">.biz - €18,99/Jahr</option>
                            <option value="eu" data-price="8.99">.eu - €8,99/Jahr</option>
                            <option value="at" data-price="12.99">.at - €12,99/Jahr</option>
                            <option value="ch" data-price="14.99">.ch - €14,99/Jahr</option>
                            <option value="co.uk" data-price="11.99">.co.uk - €11,99/Jahr</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Domain Configuration -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">2. Konfiguration</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="registration_years" class="block text-sm font-medium text-gray-300 mb-2">
                            Registrierungsdauer <span class="text-red-400">*</span>
                        </label>
                        <select id="registration_years" 
                                name="registration_years" 
                                class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                                required>
                            <option value="1">1 Jahr</option>
                            <option value="2">2 Jahre</option>
                            <option value="3">3 Jahre</option>
                            <option value="5">5 Jahre</option>
                            <option value="10">10 Jahre</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center pt-8">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   name="whois_privacy" 
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                            <span class="ml-3 text-sm text-white">
                                Whois-Privacy (+€2,99/Jahr)
                                <br><span class="text-xs text-gray-400">Schutz Ihrer persönlichen Daten</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">3. Bestellung abschließen</h2>
                
                <div class="bg-gray-700 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Bestellübersicht</h3>
                    <div class="space-y-2 text-sm" id="order-summary">
                        <div class="flex justify-between">
                            <span class="text-gray-300">Gewählte Domain:</span>
                            <span class="text-white" id="selected-domain">Bitte Domain eingeben</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Registrierungsdauer:</span>
                            <span class="text-white" id="selected-years">1 Jahr</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Domain-Preis:</span>
                            <span class="text-white" id="domain-price">€0,00</span>
                        </div>
                        <div class="flex justify-between" id="whois-privacy-row" style="display: none;">
                            <span class="text-gray-300">Whois-Privacy:</span>
                            <span class="text-white" id="whois-price">€0,00</span>
                        </div>
                        <hr class="border-gray-600">
                        <div class="flex justify-between text-lg font-semibold">
                            <span class="text-white">Gesamt:</span>
                            <span class="text-white" id="total-price">€0,00</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-orange-900 border border-orange-700 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-orange-400 mt-1 mr-3"></i>
                        <div class="text-orange-200 text-sm">
                            <p class="font-semibold mb-1">Domain-Registrierung</p>
                            <p>Ihre Domain wird bei der entsprechenden Registry registriert und ist innerhalb von 24 Stunden vollständig aktiv.</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        name="place_order"
                        class="w-full bg-orange-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-orange-700 transition-colors">
                    <i class="fas fa-link mr-2"></i>Domain jetzt registrieren
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const domainNameInput = document.getElementById('domain_name');
    const domainTldSelect = document.getElementById('domain_tld');
    const registrationYearsSelect = document.getElementById('registration_years');
    const whoisPrivacyCheckbox = document.querySelector('input[name="whois_privacy"]');
    
    const selectedDomainSpan = document.getElementById('selected-domain');
    const selectedYearsSpan = document.getElementById('selected-years');
    const domainPriceSpan = document.getElementById('domain-price');
    const whoisPriceSpan = document.getElementById('whois-price');
    const totalPriceSpan = document.getElementById('total-price');
    const whoisPrivacyRow = document.getElementById('whois-privacy-row');
    
    function updateOrderSummary() {
        const domainName = domainNameInput.value;
        const selectedTld = domainTldSelect.options[domainTldSelect.selectedIndex];
        const years = parseInt(registrationYearsSelect.value);
        const whoisPrivacy = whoisPrivacyCheckbox.checked;
        
        if (domainName && selectedTld.value) {
            const fullDomain = domainName + '.' + selectedTld.value;
            selectedDomainSpan.textContent = fullDomain;
            
            const domainPrice = parseFloat(selectedTld.dataset.price || 0);
            const whoisPrice = whoisPrivacy ? 2.99 : 0;
            const totalDomainPrice = domainPrice * years;
            const totalWhoisPrice = whoisPrice * years;
            const totalPrice = totalDomainPrice + totalWhoisPrice;
            
            selectedYearsSpan.textContent = years + ' Jahr' + (years > 1 ? 'e' : '');
            domainPriceSpan.textContent = '€' + totalDomainPrice.toFixed(2);
            whoisPriceSpan.textContent = '€' + totalWhoisPrice.toFixed(2);
            totalPriceSpan.textContent = '€' + totalPrice.toFixed(2);
            
            whoisPrivacyRow.style.display = whoisPrivacy ? 'flex' : 'none';
        } else {
            selectedDomainSpan.textContent = 'Bitte Domain eingeben';
            domainPriceSpan.textContent = '€0,00';
            whoisPriceSpan.textContent = '€0,00';
            totalPriceSpan.textContent = '€0,00';
            whoisPrivacyRow.style.display = 'none';
        }
    }
    
    // Domain name validation
    domainNameInput.addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        updateOrderSummary();
    });
    
    domainTldSelect.addEventListener('change', updateOrderSummary);
    registrationYearsSelect.addEventListener('change', updateOrderSummary);
    whoisPrivacyCheckbox.addEventListener('change', updateOrderSummary);
    
    // Initial update
    updateOrderSummary();
});
</script>
<?php endif; ?>

<?php renderFooter(); ?>