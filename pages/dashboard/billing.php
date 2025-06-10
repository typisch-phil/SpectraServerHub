<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get current user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    header("Location: /login");
    exit;
}

// Billing-Daten aus der Datenbank laden
try {
    // Aktueller Kontostand
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn() ?: 0.00;

    // Rechnungen laden
    $stmt = $db->prepare("
        SELECT i.*, s.name as service_name, st.name as service_type_name
        FROM invoices i 
        LEFT JOIN services s ON i.service_id = s.id 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE i.user_id = ? 
        ORDER BY i.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Zahlungshistorie
    $stmt = $db->prepare("
        SELECT p.*, pm.name as payment_method_name
        FROM payments p 
        LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Billing-Statistiken
    $stmt = $db->prepare("SELECT status, COUNT(*) as count, SUM(amount) as total FROM invoices WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $invoice_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Billing data error: " . $e->getMessage());
    $current_balance = 0.00;
    $invoices = [];
    $payments = [];
    $invoice_stats = [];
}

// Statistiken berechnen
$total_paid = 0;
$total_pending = 0;
$total_overdue = 0;

foreach ($invoice_stats as $stat) {
    switch ($stat['status']) {
        case 'paid':
            $total_paid = $stat['total'];
            break;
        case 'pending':
            $total_pending = $stat['total'];
            break;
        case 'overdue':
            $total_overdue = $stat['total'];
            break;
    }
}

renderDashboardHeader('Billing - Dashboard');
?>

<div class="min-h-screen bg-gray-900">
    <!-- Dashboard Navigation -->
    <nav class="bg-gray-800 shadow-lg border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost Dashboard</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-300">
                        Guthaben: <span class="font-bold text-green-400">€<?php echo number_format($current_balance, 2); ?></span>
                    </div>
                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700" onclick="showAddFundsModal()">
                        <i class="fas fa-plus mr-2"></i>Guthaben aufladen
                    </button>
                    <a href="/" class="text-gray-300 hover:text-blue-400 px-3 py-1 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Zur Website
                    </a>
                    <button onclick="logout()" class="text-gray-300 hover:text-red-400 px-3 py-1 rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Billing & Zahlungen</h1>
                    <p class="mt-2 text-gray-400">Verwalten Sie Ihre Rechnungen und Zahlungen</p>
                </div>
                <div class="flex space-x-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">€<?php echo number_format($total_paid, 2); ?></div>
                        <div class="text-sm text-gray-400">Bezahlt</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-400">€<?php echo number_format($total_pending, 2); ?></div>
                        <div class="text-sm text-gray-400">Offen</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400">€<?php echo number_format($total_overdue, 2); ?></div>
                        <div class="text-sm text-gray-400">Überfällig</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Account Balance Card -->
                <div class="bg-gradient-to-r from-green-600 to-blue-600 rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-8 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium">Aktueller Kontostand</h3>
                                <div class="text-4xl font-bold mt-2">€<?php echo number_format($current_balance, 2); ?></div>
                                <p class="text-green-100 mt-2">Verfügbares Guthaben</p>
                            </div>
                            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i class="fas fa-wallet text-3xl"></i>
                            </div>
                        </div>
                        <div class="mt-6 flex space-x-3">
                            <button class="bg-white text-green-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100" onclick="showAddFundsModal()">
                                <i class="fas fa-plus mr-2"></i>Guthaben aufladen
                            </button>
                            <button class="bg-green-700 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-800 border border-white border-opacity-30">
                                <i class="fas fa-download mr-2"></i>Kontoauszug
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">Rechnungen</h3>
                            <button class="text-sm text-blue-400 hover:text-blue-300">Alle anzeigen</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-750">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rechnung</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Betrag</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Datum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                <?php if (!empty($invoices)): ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr class="hover:bg-gray-750">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-white">#<?php echo str_pad($invoice['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-white"><?php echo htmlspecialchars($invoice['service_name'] ?? 'Allgemein'); ?></div>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($invoice['service_type_name'] ?? ''); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-white">€<?php echo number_format($invoice['amount'], 2); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php 
                                                    switch($invoice['status']) {
                                                        case 'paid': echo 'bg-green-900 text-green-400'; break;
                                                        case 'pending': echo 'bg-orange-900 text-orange-400'; break;
                                                        case 'overdue': echo 'bg-red-900 text-red-400'; break;
                                                        default: echo 'bg-gray-700 text-gray-300';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($invoice['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                <?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button class="text-blue-400 hover:text-blue-300">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="text-green-400 hover:text-green-300">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'overdue'): ?>
                                                        <button class="text-orange-400 hover:text-orange-300">
                                                            <i class="fas fa-credit-card"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                            <i class="fas fa-file-invoice text-gray-600 text-4xl mb-4"></i>
                                            <div>Keine Rechnungen vorhanden</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Zahlungshistorie</h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($payments)): ?>
                            <div class="space-y-4">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-600 rounded-lg bg-gray-750">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-green-900 rounded-lg flex items-center justify-center">
                                                <i class="fas <?php echo $payment['type'] == 'deposit' ? 'fa-plus' : 'fa-credit-card'; ?> text-green-400"></i>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-white">
                                                    <?php echo $payment['type'] == 'deposit' ? 'Guthaben aufgeladen' : 'Zahlung'; ?>
                                                </p>
                                                <p class="text-sm text-gray-400">
                                                    <?php echo htmlspecialchars($payment['payment_method_name'] ?? 'Unbekannt'); ?> • 
                                                    <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-white">
                                                <?php echo $payment['type'] == 'deposit' ? '+' : '-'; ?>€<?php echo number_format($payment['amount'], 2); ?>
                                            </p>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                <?php echo $payment['status'] == 'completed' ? 'bg-green-900 text-green-400' : 'bg-orange-900 text-orange-400'; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-credit-card text-gray-600 text-4xl mb-4"></i>
                                <p class="text-gray-400">Keine Zahlungen vorhanden</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Aktionen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <button class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700" onclick="showAddFundsModal()">
                                <i class="fas fa-plus mr-2"></i>Guthaben aufladen
                            </button>
                            <button class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700">
                                <i class="fas fa-file-download mr-2"></i>Kontoauszug herunterladen
                            </button>
                            <button class="w-full bg-gray-700 text-gray-300 py-3 px-4 rounded-lg font-medium hover:bg-gray-600">
                                <i class="fas fa-cog mr-2"></i>Zahlungseinstellungen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Funds Modal -->
<div id="addFundsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border border-gray-600 w-96 shadow-lg rounded-md bg-gray-800">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-white">Guthaben aufladen</h3>
                <button onclick="hideAddFundsModal()" class="text-gray-400 hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addFundsForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Betrag (€)</label>
                    <input type="number" min="5" step="0.01" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Zahlungsmethode</label>
                    <select class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="mollie">Mollie (Kreditkarte, PayPal, etc.)</option>
                        <option value="bank">Banküberweisung</option>
                    </select>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="hideAddFundsModal()" class="flex-1 bg-gray-700 text-gray-300 py-2 px-4 rounded-lg font-medium hover:bg-gray-600">
                        Abbrechen
                    </button>
                    <button type="submit" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700">
                        Aufladen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddFundsModal() {
    document.getElementById('addFundsModal').classList.remove('hidden');
}

function hideAddFundsModal() {
    document.getElementById('addFundsModal').classList.add('hidden');
}
</script>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderDashboardFooter(); ?>