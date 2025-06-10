<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Balance abrufen
try {
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $current_balance = $user['balance'] ?? 0.00;
} catch (Exception $e) {
    $current_balance = 0.00;
}

// Invoice statistics berechnen
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'paid'");
    $paid_invoices = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pending_invoices = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'paid'");
    $total_amount = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $monthly_amount = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $paid_invoices = 0;
    $pending_invoices = 0;
    $total_amount = 0;
    $monthly_amount = 0;
}

$pageTitle = 'Billing - SpectraHost Dashboard';
require_once __DIR__ . '/../../includes/layout.php';
renderHeader($pageTitle);
?>

<!-- Dashboard Dark Theme Layout -->
<div class="min-h-screen bg-gray-900 text-white">
    <!-- Dashboard Header -->
    <header class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-white">SpectraHost Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300">Willkommen, <?php echo htmlspecialchars($_SESSION['user']['first_name'] ?? 'User'); ?></span>
                    <a href="/api/logout" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-md text-white transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="/dashboard" class="border-b-2 border-transparent hover:border-blue-500 py-4 px-1 text-gray-300 hover:text-white transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="/dashboard/services" class="border-b-2 border-transparent hover:border-blue-500 py-4 px-1 text-gray-300 hover:text-white transition-colors">
                    <i class="fas fa-server mr-2"></i>Services
                </a>
                <a href="/dashboard/billing" class="border-b-2 border-blue-500 py-4 px-1 text-white">
                    <i class="fas fa-credit-card mr-2"></i>Billing
                </a>
                <a href="/dashboard/support" class="border-b-2 border-transparent hover:border-blue-500 py-4 px-1 text-gray-300 hover:text-white transition-colors">
                    <i class="fas fa-headset mr-2"></i>Support
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Billing Header -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600 rounded-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Billing & Rechnungen</h1>
                    <p class="mt-2 text-green-100">Verwalten Sie Ihr Guthaben und Ihre Rechnungen</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-green-100">Aktuelles Guthaben</div>
                    <div class="text-4xl font-bold text-white">€<?php echo number_format($current_balance, 2); ?></div>
                    <button onclick="showTopupModal()" class="mt-2 px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                        <i class="fas fa-plus mr-2"></i>Guthaben aufladen
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white"><?php echo $paid_invoices; ?></p>
                        <p class="text-sm text-gray-400">Bezahlte Rechnungen</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-clock text-yellow-400"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white"><?php echo $pending_invoices; ?></p>
                        <p class="text-sm text-gray-400">Ausstehende Rechnungen</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-euro-sign text-blue-400"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">€<?php echo number_format($total_amount, 2); ?></p>
                        <p class="text-sm text-gray-400">Gesamtumsatz</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-calendar text-purple-400"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">€<?php echo number_format($monthly_amount, 2); ?></p>
                        <p class="text-sm text-gray-400">Aktueller Monat</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 mb-8">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">Aktuelle Rechnungen</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="py-3 text-gray-300 font-medium">Rechnung #</th>
                                <th class="py-3 text-gray-300 font-medium">Service</th>
                                <th class="py-3 text-gray-300 font-medium">Betrag</th>
                                <th class="py-3 text-gray-300 font-medium">Status</th>
                                <th class="py-3 text-gray-300 font-medium">Datum</th>
                                <th class="py-3 text-gray-300 font-medium">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-700">
                                <td class="py-4 text-white">#INV-001</td>
                                <td class="py-4 text-gray-300">Webspace Business</td>
                                <td class="py-4 text-white">€9.99</td>
                                <td class="py-4">
                                    <span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs">Bezahlt</span>
                                </td>
                                <td class="py-4 text-gray-300">15.11.2024</td>
                                <td class="py-4">
                                    <button class="text-blue-400 hover:text-blue-300 mr-3">Download</button>
                                </td>
                            </tr>
                            <tr class="border-b border-gray-700">
                                <td class="py-4 text-white">#INV-002</td>
                                <td class="py-4 text-gray-300">VPS Pro</td>
                                <td class="py-4 text-white">€29.99</td>
                                <td class="py-4">
                                    <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">Ausstehend</span>
                                </td>
                                <td class="py-4 text-gray-300">01.12.2024</td>
                                <td class="py-4">
                                    <button class="text-blue-400 hover:text-blue-300 mr-3">Bezahlen</button>
                                    <button class="text-blue-400 hover:text-blue-300">Download</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">Zahlungshistorie</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-700">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-900 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-plus text-green-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-medium">Guthaben aufgeladen</p>
                                <p class="text-sm text-gray-400">via Mollie Payment</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-green-400 font-semibold">+€50.00</p>
                            <p class="text-sm text-gray-400">15.11.2024</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b border-gray-700">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-red-900 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-minus text-red-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-medium">Rechnung bezahlt</p>
                                <p class="text-sm text-gray-400">Webspace Business #INV-001</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-red-400 font-semibold">-€9.99</p>
                            <p class="text-sm text-gray-400">15.11.2024</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Guthaben aufladen Modal -->
<div id="topupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg max-w-md w-full mx-4 border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">Guthaben aufladen</h3>
        </div>
        
        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action" value="topup">
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Betrag auswählen</label>
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <button type="button" onclick="setAmount(10)" class="amount-btn px-4 py-2 border border-gray-600 rounded-lg text-white hover:bg-gray-700 transition-colors">€10</button>
                    <button type="button" onclick="setAmount(25)" class="amount-btn px-4 py-2 border border-gray-600 rounded-lg text-white hover:bg-gray-700 transition-colors">€25</button>
                    <button type="button" onclick="setAmount(50)" class="amount-btn px-4 py-2 border border-gray-600 rounded-lg text-white hover:bg-gray-700 transition-colors">€50</button>
                    <button type="button" onclick="setAmount(100)" class="amount-btn px-4 py-2 border border-gray-600 rounded-lg text-white hover:bg-gray-700 transition-colors">€100</button>
                    <button type="button" onclick="setAmount(250)" class="amount-btn px-4 py-2 border border-gray-600 rounded-lg text-white hover:bg-gray-700 transition-colors">€250</button>
                    <button type="button" onclick="setAmount(500)" class="amount-btn px-4 py-2 border border-gray-600 rounded-lg text-white hover:bg-gray-700 transition-colors">€500</button>
                </div>
                <input type="number" name="amount" id="topupAmount" step="0.01" min="5" max="1000" required
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                       placeholder="Individueller Betrag (€5 - €1000)">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-2">Zahlungsmethode</label>
                <select class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="mollie">Mollie (Kreditkarte, PayPal, etc.)</option>
                    <option value="bank">Banküberweisung</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeTopupModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Abbrechen
                </button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Aufladen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTopupModal() {
    document.getElementById('topupModal').classList.remove('hidden');
}

function closeTopupModal() {
    document.getElementById('topupModal').classList.add('hidden');
}

function setAmount(amount) {
    document.getElementById('topupAmount').value = amount;
    // Remove active class from all buttons
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('border-gray-600', 'text-white');
    });
    // Add active class to clicked button
    event.target.classList.add('bg-blue-600');
    event.target.classList.remove('border-gray-600');
}

// Modal schließen bei Klick außerhalb
document.getElementById('topupModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTopupModal();
    }
});
</script>

<?php renderFooter(); ?>