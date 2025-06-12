<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/layout.php';

// Benutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login');
    exit;
}

$db = Database::getInstance();

// Service-ID aus URL-Parameter
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$serviceType = isset($_GET['type']) ? $_GET['type'] : '';

if (!$serviceId) {
    header('Location: /dashboard');
    exit;
}

// Service-Details laden
$service = null;
try {
    $stmt = $db->prepare("SELECT * FROM service_types WHERE id = ? AND is_active = 1");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error loading service: " . $e->getMessage());
}

if (!$service) {
    header('Location: /dashboard');
    exit;
}

// Benutzer-Details laden
$user = null;
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error loading user: " . $e->getMessage());
}

$specs = json_decode($service['specifications'] ?? '{}', true);

$pageTitle = "Bestellung: " . $service['name'] . " - SpectraHost";
$pageDescription = "Bestellen Sie " . $service['name'] . " bei SpectraHost";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 to-blue-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Bestellung aufgeben</h1>
                    <p class="text-gray-200">Bestellen Sie <?php echo htmlspecialchars($service['name']); ?></p>
                </div>
                <div class="hidden md:block">
                    <a href="/dashboard" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Zurück zum Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Bestellformular -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-8">
                    <h2 class="text-2xl font-bold text-white mb-6">Bestelldetails</h2>
                    
                    <form id="orderForm">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        
                        <!-- Service-Konfiguration -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Service-Konfiguration</h3>
                            
                            <?php if ($service['category'] === 'vserver'): ?>
                            <!-- VPS-spezifische Optionen -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Betriebssystem</label>
                                    <select name="operating_system" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                        <option value="">Betriebssystem wählen</option>
                                        <option value="ubuntu-22.04">Ubuntu 22.04 LTS</option>
                                        <option value="ubuntu-20.04">Ubuntu 20.04 LTS</option>
                                        <option value="debian-11">Debian 11</option>
                                        <option value="debian-12">Debian 12</option>
                                        <option value="centos-8">CentOS 8</option>
                                        <option value="rocky-9">Rocky Linux 9</option>
                                        <option value="windows-2022">Windows Server 2022 (+€15/Monat)</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Server-Name</label>
                                    <input type="text" name="server_name" placeholder="mein-vps-server" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                    <div class="text-gray-400 text-xs mt-1">Nur Buchstaben, Zahlen und Bindestriche erlaubt</div>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Root-Passwort</label>
                                    <input type="password" name="root_password" placeholder="Sicheres Passwort eingeben" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                    <div class="text-gray-400 text-xs mt-1">Mindestens 8 Zeichen, empfohlen sind Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen</div>
                                </div>
                            </div>
                            
                            <?php elseif ($service['category'] === 'domain'): ?>
                            <!-- Domain-spezifische Optionen -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Domain-Name</label>
                                    <div class="flex">
                                        <input type="text" name="domain_name" placeholder="meine-domain" class="flex-1 px-3 py-2 bg-gray-700 text-white rounded-l-lg border border-gray-600 focus:border-blue-500" required>
                                        <select name="domain_extension" class="px-3 py-2 bg-gray-700 text-white rounded-r-lg border border-gray-600 focus:border-blue-500" required>
                                            <option value=".de">.de</option>
                                            <option value=".com">.com</option>
                                            <option value=".org">.org</option>
                                            <option value=".net">.net</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <?php elseif ($service['category'] === 'webspace'): ?>
                            <!-- Webspace-spezifische Optionen -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Domain verknüpfen</label>
                                    <input type="text" name="domain_name" placeholder="ihre-domain.de (optional)" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500">
                                    <div class="text-gray-400 text-xs mt-1">Falls Sie bereits eine Domain besitzen, die Sie verknüpfen möchten</div>
                                </div>
                            </div>
                            
                            <?php elseif ($service['category'] === 'gameserver'): ?>
                            <!-- GameServer-spezifische Optionen -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Spiel</label>
                                    <select name="game_type" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                        <option value="">Spiel wählen</option>
                                        <option value="minecraft">Minecraft</option>
                                        <option value="cs2">Counter-Strike 2</option>
                                        <option value="rust">Rust</option>
                                        <option value="ark">ARK: Survival Evolved</option>
                                        <option value="valheim">Valheim</option>
                                        <option value="teamspeak">TeamSpeak 3</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Server-Name</label>
                                    <input type="text" name="server_name" placeholder="Mein GameServer" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Zusätzliche Optionen -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Zusätzliche Optionen</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="backup_service" value="1" class="mr-3">
                                    <span class="text-gray-300">Tägliche Backups (+€5/Monat)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="monitoring" value="1" class="mr-3">
                                    <span class="text-gray-300">24/7 Monitoring (+€3/Monat)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="setup_service" value="1" class="mr-3">
                                    <span class="text-gray-300">Kostenloser Setup-Service (€0)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Vertragslaufzeit -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Vertragslaufzeit</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="contract_period" value="1" class="mr-3" checked>
                                    <div>
                                        <div class="text-white font-medium">1 Monat</div>
                                        <div class="text-gray-400 text-sm">Monatlich kündbar</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="contract_period" value="12" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">12 Monate</div>
                                        <div class="text-green-400 text-sm">10% Rabatt</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="contract_period" value="24" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">24 Monate</div>
                                        <div class="text-green-400 text-sm">20% Rabatt</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Bestellung abschließen -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-300">
                                <i class="fas fa-shopping-cart mr-2"></i>Jetzt bestellen
                            </button>
                            <a href="/dashboard" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 px-6 rounded-lg font-medium text-center transition-colors">
                                Abbrechen
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bestellübersicht -->
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6 sticky top-8">
                    <h3 class="text-xl font-bold text-white mb-4">Bestellübersicht</h3>
                    
                    <!-- Service-Details -->
                    <div class="mb-6">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-<?php 
                                    switch($service['category']) {
                                        case 'vserver': echo 'server'; break;
                                        case 'webspace': echo 'globe'; break;
                                        case 'gameserver': echo 'gamepad'; break;
                                        case 'domain': echo 'link'; break;
                                        default: echo 'server';
                                    }
                                ?> text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-medium"><?php echo htmlspecialchars($service['name']); ?></h4>
                                <p class="text-gray-400 text-sm"><?php echo ucfirst($service['category']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($specs)): ?>
                        <div class="space-y-2 mb-4">
                            <?php foreach ($specs as $key => $value): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $key)); ?>:</span>
                                <span class="text-white"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Preisübersicht -->
                    <div class="border-t border-gray-600 pt-4">
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-300">Grundpreis</span>
                                <span class="text-white" id="basePrice">€<?php echo number_format($service['monthly_price'], 2); ?></span>
                            </div>
                            <div class="flex justify-between" id="backupPrice" style="display: none;">
                                <span class="text-gray-300">Backup-Service</span>
                                <span class="text-white">€5.00</span>
                            </div>
                            <div class="flex justify-between" id="monitoringPrice" style="display: none;">
                                <span class="text-gray-300">Monitoring</span>
                                <span class="text-white">€3.00</span>
                            </div>
                            <div class="flex justify-between" id="windowsPrice" style="display: none;">
                                <span class="text-gray-300">Windows Lizenz</span>
                                <span class="text-white">€15.00</span>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-600 pt-2">
                            <div class="flex justify-between">
                                <span class="text-white font-medium">Gesamt (monatlich)</span>
                                <span class="text-white font-bold text-lg" id="totalPrice">€<?php echo number_format($service['monthly_price'], 2); ?></span>
                            </div>
                            <div class="flex justify-between mt-1" id="discountInfo" style="display: none;">
                                <span class="text-green-400 text-sm">Mit Rabatt</span>
                                <span class="text-green-400 text-sm font-medium" id="discountedPrice"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Info -->
                    <div class="mt-6 p-4 bg-blue-900/20 rounded-lg border border-blue-800">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                            <span class="text-blue-400 font-medium">Hinweis</span>
                        </div>
                        <p class="text-gray-300 text-sm">
                            Nach der Bestellung erhalten Sie eine Bestätigungs-E-Mail mit allen Details. 
                            Ihr Service wird innerhalb von 15 Minuten aktiviert.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = <?php echo $service['monthly_price']; ?>;

// Preisberechnung
function updatePricing() {
    let total = basePrice;
    const contractPeriod = document.querySelector('input[name="contract_period"]:checked')?.value || '1';
    
    // Zusätzliche Services
    if (document.querySelector('input[name="backup_service"]').checked) {
        total += 5;
        document.getElementById('backupPrice').style.display = 'flex';
    } else {
        document.getElementById('backupPrice').style.display = 'none';
    }
    
    if (document.querySelector('input[name="monitoring"]').checked) {
        total += 3;
        document.getElementById('monitoringPrice').style.display = 'flex';
    } else {
        document.getElementById('monitoringPrice').style.display = 'none';
    }
    
    // Windows Lizenz
    const os = document.querySelector('select[name="operating_system"]')?.value || '';
    if (os.includes('windows')) {
        total += 15;
        document.getElementById('windowsPrice').style.display = 'flex';
    } else {
        document.getElementById('windowsPrice').style.display = 'none';
    }
    
    // Rabatt berechnen
    let discount = 0;
    if (contractPeriod === '12') {
        discount = 0.1; // 10%
    } else if (contractPeriod === '24') {
        discount = 0.2; // 20%
    }
    
    const discountedTotal = total * (1 - discount);
    
    document.getElementById('totalPrice').textContent = '€' + total.toFixed(2);
    
    if (discount > 0) {
        document.getElementById('discountInfo').style.display = 'flex';
        document.getElementById('discountedPrice').textContent = '€' + discountedTotal.toFixed(2);
    } else {
        document.getElementById('discountInfo').style.display = 'none';
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Preisberechnung bei Änderungen
    document.querySelectorAll('input[type="checkbox"], input[type="radio"], select').forEach(element => {
        element.addEventListener('change', updatePricing);
    });
    
    // Formular-Submit
    document.getElementById('orderForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        try {
            const response = await fetch('/api/create-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                alert('Bestellung erfolgreich aufgegeben! Sie werden zur Übersicht weitergeleitet.');
                window.location.href = '/dashboard';
            } else {
                alert('Fehler bei der Bestellung: ' + (result.error || 'Unbekannter Fehler'));
            }
        } catch (error) {
            alert('Fehler bei der Bestellung: ' + error.message);
        }
    });
    
    // Initiale Preisberechnung
    updatePricing();
});
</script>

<?php
renderFooter();
?>