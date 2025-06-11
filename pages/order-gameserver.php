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

$pageTitle = 'Gameserver bestellen - SpectraHost';
$pageDescription = 'Bestellen Sie Ihren Gaming-Server für Minecraft, CS:GO, ARK und viele weitere Spiele.';

// Get gameserver packages from database
$gameserverPackages = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM service_types WHERE category = 'gameserver' ORDER BY id ASC");
    $stmt->execute();
    $gameserverPackages = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database error in order-gameserver.php: " . $e->getMessage());
    $gameserverPackages = [];
}

// Handle form submission
$orderSuccess = false;
$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $packageId = intval($_POST['package_id']);
        $serverName = trim($_POST['server_name']);
        $gameType = $_POST['game_type'];
        $playerSlots = intval($_POST['player_slots']);
        
        // Validate input
        if (empty($serverName) || !preg_match('/^[a-zA-Z0-9\s-]+$/', $serverName)) {
            throw new Exception('Servername ist ungültig. Nur Buchstaben, Zahlen, Leerzeichen und Bindestriche erlaubt.');
        }
        
        if ($playerSlots < 2 || $playerSlots > 100) {
            throw new Exception('Spieleranzahl muss zwischen 2 und 100 liegen.');
        }
        
        // Get package details
        $stmt = $db->prepare("SELECT * FROM service_types WHERE id = ? AND category = 'gameserver'");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            throw new Exception('Gewähltes Paket nicht gefunden.');
        }
        
        $monthlyPrice = $package['monthly_price'] ?? 7.99;
        
        // Create order in database
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, service_id, total_amount, billing_period, status, notes, created_at) 
            VALUES (?, ?, ?, 'monthly', 'paid', ?, NOW())
        ");
        $orderNotes = json_encode([
            'service_type' => 'gameserver',
            'server_name' => $serverName,
            'game_type' => $gameType,
            'player_slots' => $playerSlots
        ]);
        $stmt->execute([$_SESSION['user_id'], $packageId, $monthlyPrice, $orderNotes]);
        $orderId = $db->lastInsertId();
        
        // Create user service entry
        $expiresAt = date('Y-m-d', strtotime('+1 month'));
        $stmt = $db->prepare("
            INSERT INTO user_services (user_id, service_id, server_name, status, expires_at, created_at) 
            VALUES (?, ?, ?, 'active', ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $packageId, $serverName, $expiresAt]);
        
        $orderSuccess = true;
        $_SESSION['order_server_name'] = $serverName;
        $_SESSION['order_game_type'] = $gameType;
        $_SESSION['order_package'] = $package['name'];
        
    } catch (Exception $e) {
        $orderError = $e->getMessage();
        error_log("Gameserver Order Error: " . $e->getMessage());
        
        // Update order status to failed if order was created
        if (isset($orderId)) {
            $stmt = $db->prepare("UPDATE orders SET status = 'failed', notes = ? WHERE id = ?");
            $errorNotes = json_encode(['error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
            $stmt->execute([$errorNotes, $orderId]);
        }
    }
}

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Gameserver bestellen
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Wählen Sie Ihr Gaming-Server-Paket und starten Sie noch heute mit Ihrer Community.
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
            <h2 class="text-2xl font-bold text-white mb-4">Gameserver erfolgreich bestellt!</h2>
            <p class="text-green-200 mb-6">
                Ihr Gaming-Server wird jetzt eingerichtet und ist in wenigen Minuten einsatzbereit.
            </p>
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div>
                        <span class="text-gray-400">Servername:</span>
                        <span class="text-white"><?php echo htmlspecialchars($_SESSION['order_server_name']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Spiel:</span>
                        <span class="text-white"><?php echo htmlspecialchars($_SESSION['order_game_type']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Paket:</span>
                        <span class="text-white"><?php echo htmlspecialchars($_SESSION['order_package']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Status:</span>
                        <span class="text-yellow-400">Wird eingerichtet</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard/services" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-gamepad mr-2"></i>Zu meinen Services
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
                <h2 class="text-2xl font-bold text-white mb-6">1. Gameserver-Paket wählen</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if (!empty($gameserverPackages)): ?>
                        <?php foreach ($gameserverPackages as $package): ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="package_id" value="<?php echo $package['id']; ?>" class="sr-only peer" required>
                            <div class="border-2 border-gray-600 peer-checked:border-green-500 rounded-lg p-6 hover:border-gray-500 transition-colors bg-gray-700">
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
                            <input type="radio" name="package_id" value="gaming-starter" class="sr-only peer" required>
                            <div class="border-2 border-gray-600 peer-checked:border-green-500 rounded-lg p-6 hover:border-gray-500 transition-colors bg-gray-700">
                                <h3 class="text-lg font-semibold text-white">Gaming Starter</h3>
                                <p class="text-gray-300 text-sm mt-1">Perfect für kleine Communities</p>
                                <div class="mt-4">
                                    <span class="text-2xl font-bold text-white">€7,99</span>
                                    <span class="text-gray-300">/Monat</span>
                                </div>
                                <ul class="mt-4 space-y-2 text-sm text-gray-300">
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>4 GB RAM</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>10 Spieler Slots</li>
                                    <li><i class="fas fa-check text-green-400 mr-2"></i>DDoS-Schutz</li>
                                </ul>
                            </div>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Server Configuration -->
            <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">2. Server-Konfiguration</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="server_name" class="block text-sm font-medium text-gray-300 mb-2">
                            Servername <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="server_name" 
                               name="server_name" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Mein Gaming Server"
                               maxlength="50"
                               required>
                        <p class="mt-1 text-xs text-gray-400">Name Ihres Gaming-Servers (max. 50 Zeichen)</p>
                    </div>
                    
                    <div>
                        <label for="game_type" class="block text-sm font-medium text-gray-300 mb-2">
                            Spiel <span class="text-red-400">*</span>
                        </label>
                        <select id="game_type" 
                                name="game_type" 
                                class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                required>
                            <option value="">Bitte wählen...</option>
                            <option value="minecraft">Minecraft</option>
                            <option value="cs2">Counter-Strike 2</option>
                            <option value="csgo">CS:GO</option>
                            <option value="ark">ARK: Survival Evolved</option>
                            <option value="rust">Rust</option>
                            <option value="gmod">Garry's Mod</option>
                            <option value="valheim">Valheim</option>
                            <option value="7dtd">7 Days to Die</option>
                            <option value="tf2">Team Fortress 2</option>
                            <option value="other">Anderes Spiel</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="player_slots" class="block text-sm font-medium text-gray-300 mb-2">
                            Spieler-Slots <span class="text-red-400">*</span>
                        </label>
                        <input type="number" 
                               id="player_slots" 
                               name="player_slots" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                               min="2"
                               max="100"
                               value="10"
                               required>
                        <p class="mt-1 text-xs text-gray-400">Anzahl der gleichzeitigen Spieler (2-100)</p>
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
                            <span class="text-blue-400">5-10 Minuten</span>
                        </div>
                        <hr class="border-gray-600">
                        <div class="flex justify-between text-lg font-semibold">
                            <span class="text-white">Gesamt monatlich:</span>
                            <span class="text-white" id="total-price">€0,00</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-900 border border-green-700 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-green-400 mt-1 mr-3"></i>
                        <div class="text-green-200 text-sm">
                            <p class="font-semibold mb-1">Automatische Einrichtung</p>
                            <p>Ihr Gaming-Server wird automatisch eingerichtet und ist innerhalb von 5-10 Minuten einsatzbereit. Sie erhalten alle Zugangsdaten per E-Mail.</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        name="place_order"
                        class="w-full bg-green-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                    <i class="fas fa-gamepad mr-2"></i>Gameserver jetzt bestellen
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
});
</script>
<?php endif; ?>

<?php renderFooter(); ?>