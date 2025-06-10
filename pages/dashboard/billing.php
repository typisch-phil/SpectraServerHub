<?php
// Neues Billing Dashboard mit MySQL-Integration
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];
$db = Database::getInstance();

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

    // Monatliche Ausgaben
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total 
        FROM payments 
        WHERE user_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
        ORDER BY month DESC
    ");
    $stmt->execute([$user_id]);
    $monthly_spending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nächste Rechnungen
    $stmt = $db->prepare("
        SELECT s.name, s.monthly_price, s.expires_at, st.name as service_type_name
        FROM services s 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW() 
        ORDER BY s.expires_at ASC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_renewals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Billing data error: " . $e->getMessage());
    $current_balance = 0.00;
    $invoices = [];
    $payments = [];
    $invoice_stats = [];
    $monthly_spending = [];
    $upcoming_renewals = [];
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

renderHeader('Billing - Dashboard');
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">SpectraHost</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-blue-600 border-b-2 border-blue-600 px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        Guthaben: <span class="font-bold text-green-600">€<?php echo number_format($current_balance, 2); ?></span>
                    </div>
                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700" onclick="showAddFundsModal()">
                        <i class="fas fa-plus mr-2"></i>Guthaben aufladen
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Billing & Zahlungen</h1>
                    <p class="mt-2 text-gray-600">Verwalten Sie Ihre Rechnungen und Zahlungen</p>
                </div>
                <div class="flex space-x-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">€<?php echo number_format($total_paid, 2); ?></div>
                        <div class="text-sm text-gray-500">Bezahlt</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">€<?php echo number_format($total_pending, 2); ?></div>
                        <div class="text-sm text-gray-500">Offen</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">€<?php echo number_format($total_overdue, 2); ?></div>
                        <div class="text-sm text-gray-500">Überfällig</div>
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
                <div class="bg-gradient-to-r from-green-500 to-blue-600 rounded-lg shadow-lg overflow-hidden">
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
                            <button class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 border border-white border-opacity-30">
                                <i class="fas fa-download mr-2"></i>Kontoauszug
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Rechnungen</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-800">Alle anzeigen</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rechnung</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betrag</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($invoices)): ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">#<?php echo str_pad($invoice['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($invoice['service_name'] ?? 'Allgemein'); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($invoice['service_type_name'] ?? ''); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">€<?php echo number_format($invoice['amount'], 2); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php 
                                                    switch($invoice['status']) {
                                                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                                                        case 'pending': echo 'bg-orange-100 text-orange-800'; break;
                                                        case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($invoice['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button class="text-blue-600 hover:text-blue-800">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="text-green-600 hover:text-green-800">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'overdue'): ?>
                                                        <button class="text-orange-600 hover:text-orange-800">
                                                            <i class="fas fa-credit-card"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-file-invoice text-gray-300 text-4xl mb-4"></i>
                                            <div>Keine Rechnungen vorhanden</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Zahlungshistorie</h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($payments)): ?>
                            <div class="space-y-4">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas <?php echo $payment['type'] == 'deposit' ? 'fa-plus' : 'fa-credit-card'; ?> text-green-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?php echo $payment['type'] == 'deposit' ? 'Guthaben aufgeladen' : 'Zahlung'; ?>
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($payment['payment_method_name'] ?? 'Unbekannt'); ?> • 
                                                    <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo $payment['type'] == 'deposit' ? '+' : '-'; ?>€<?php echo number_format($payment['amount'], 2); ?>
                                            </p>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                <?php echo $payment['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-credit-card text-gray-300 text-4xl mb-4"></i>
                                <p class="text-gray-500">Keine Zahlungen vorhanden</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Aktionen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <button class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700" onclick="showAddFundsModal()">
                                <i class="fas fa-plus mr-2"></i>Guthaben aufladen
                            </button>
                            <button class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700">
                                <i class="fas fa-file-download mr-2"></i>Kontoauszug herunterladen
                            </button>
                            <button class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200">
                                <i class="fas fa-cog mr-2"></i>Zahlungseinstellungen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Renewals -->
                <?php if (!empty($upcoming_renewals)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Nächste Verlängerungen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($upcoming_renewals as $renewal): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($renewal['name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($renewal['service_type_name']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">€<?php echo number_format($renewal['monthly_price'], 2); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d.m.Y', strtotime($renewal['expires_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Monthly Spending Chart -->
                <?php if (!empty($monthly_spending)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Monatliche Ausgaben</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach (array_slice($monthly_spending, 0, 6) as $month): ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600"><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></span>
                                    <span class="text-sm font-medium text-gray-900">€<?php echo number_format($month['total'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Funds Modal -->
<div id="addFundsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Guthaben aufladen</h3>
                <button onclick="hideAddFundsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addFundsForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Betrag (€)</label>
                    <input type="number" min="5" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Zahlungsmethode</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="mollie">Mollie (Kreditkarte, PayPal, etc.)</option>
                        <option value="bank">Banküberweisung</option>
                    </select>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="hideAddFundsModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200">
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

<?php renderFooter(); ?>