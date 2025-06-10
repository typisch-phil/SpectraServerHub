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

// Get current user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    header("Location: /login");
    exit;
}

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'topup':
            $amount = (float)($_POST['amount'] ?? 0);
            if ($amount > 0 && $amount <= 1000) {
                // Redirect to Mollie payment processing
                header("Location: /api/payment/mollie?action=topup&amount=" . $amount);
                exit;
            }
            break;
        case 'pay_invoice':
            $invoice_id = (int)($_POST['invoice_id'] ?? 0);
            if ($invoice_id > 0) {
                // Redirect to invoice payment
                header("Location: /api/payment/mollie?action=invoice&id=" . $invoice_id);
                exit;
            }
            break;
    }
}

// Billing-Daten aus der Datenbank laden
try {
    // Aktueller Kontostand
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn() ?: 0.00;

    // Rechnungen abrufen
    $stmt = $db->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $invoices = $stmt->fetchAll();

    // Rechnungsstatistiken
    $stats = $db->fetchAll("SELECT status, COUNT(*) as count, SUM(amount) as total FROM invoices WHERE user_id = ? GROUP BY status", [$user_id]);
    $invoice_stats = [];
    foreach ($stats as $stat) {
        $invoice_stats[$stat['status']] = [
            'count' => $stat['count'],
            'total' => $stat['total']
        ];
    }

    // Zahlungshistorie - wird später implementiert
    $payment_history = [];

    // Nächste Abbuchungen
    $stmt = $db->prepare("
        SELECT s.name, s.price, s.next_billing_date 
        FROM services s 
        WHERE s.user_id = ? AND s.status = 'active' AND s.next_billing_date IS NOT NULL 
        ORDER BY s.next_billing_date ASC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_payments = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Billing page error: " . $e->getMessage());
    $current_balance = 0.00;
    $invoices = [];
    $invoice_stats = [];
    $payment_history = [];
    $upcoming_payments = [];
}

// Billing Content
renderDashboardLayout('Billing - SpectraHost Dashboard', 'billing', function() use ($current_balance, $invoices, $invoice_stats, $payment_history, $upcoming_payments) {
?>

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
                <p class="text-2xl font-bold text-white"><?php echo $invoice_stats['paid']['count'] ?? 0; ?></p>
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
                <p class="text-2xl font-bold text-white"><?php echo $invoice_stats['pending']['count'] ?? 0; ?></p>
                <p class="text-sm text-gray-400">Offene Rechnungen</p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-900 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-white"><?php echo $invoice_stats['overdue']['count'] ?? 0; ?></p>
                <p class="text-sm text-gray-400">Überfällige Rechnungen</p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-euro-sign text-blue-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">€<?php echo number_format(($invoice_stats['paid']['total'] ?? 0) + ($invoice_stats['pending']['total'] ?? 0), 2); ?></p>
                <p class="text-sm text-gray-400">Gesamtumsatz</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Invoices -->
    <div class="lg:col-span-2">
        <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-white">Rechnungen</h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-2 text-sm bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <button class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="divide-y divide-gray-700">
                <?php if (empty($invoices)): ?>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-invoice text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-white mb-2">Keine Rechnungen vorhanden</h3>
                        <p class="text-gray-400">Ihre Rechnungen werden hier angezeigt</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-blue-900 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-invoice text-blue-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-white">Rechnung #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h3>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($invoice['description'] ?? 'Service-Rechnung'); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Erstellt: <?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?>
                                            <?php if ($invoice['due_date']): ?>
                                            • Fällig: <?php echo date('d.m.Y', strtotime($invoice['due_date'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-white">€<?php echo number_format($invoice['amount'], 2); ?></div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($invoice['status']) {
                                                case 'paid': echo 'bg-green-900 text-green-300'; break;
                                                case 'pending': echo 'bg-yellow-900 text-yellow-300'; break;
                                                case 'overdue': echo 'bg-red-900 text-red-300'; break;
                                                case 'cancelled': echo 'bg-gray-700 text-gray-300'; break;
                                                default: echo 'bg-blue-900 text-blue-300';
                                            }
                                            ?>">
                                            <?php 
                                            $status_labels = [
                                                'paid' => 'Bezahlt',
                                                'pending' => 'Offen',
                                                'overdue' => 'Überfällig',
                                                'cancelled' => 'Storniert'
                                            ];
                                            echo $status_labels[$invoice['status']] ?? ucfirst($invoice['status']);
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <button class="p-2 text-gray-400 hover:text-blue-400 transition-colors" title="Rechnung anzeigen">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-2 text-gray-400 hover:text-green-400 transition-colors" title="PDF herunterladen">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <?php if ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue'): ?>
                                        <button onclick="payInvoice(<?php echo $invoice['id']; ?>)" class="p-2 text-gray-400 hover:text-yellow-400 transition-colors" title="Jetzt bezahlen">
                                            <i class="fas fa-credit-card"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Upcoming Payments -->
        <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Nächste Abbuchungen</h3>
            </div>
            <div class="p-6">
                <?php if (empty($upcoming_payments)): ?>
                    <p class="text-gray-400 text-sm">Keine geplanten Abbuchungen</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($upcoming_payments as $payment): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($payment['name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('d.m.Y', strtotime($payment['next_billing_date'])); ?></p>
                            </div>
                            <div class="text-sm font-bold text-white">€<?php echo number_format($payment['price'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Zahlungshistorie</h3>
            </div>
            <div class="divide-y divide-gray-700">
                <?php if (empty($payment_history)): ?>
                    <div class="p-6">
                        <p class="text-gray-400 text-sm">Keine Zahlungen vorhanden</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($payment_history as $payment): ?>
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($payment['description'] ?? 'Zahlung'); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></p>
                            </div>
                            <div class="text-sm font-bold <?php echo $payment['type'] === 'credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo $payment['type'] === 'credit' ? '+' : '-'; ?>€<?php echo number_format(abs($payment['amount']), 2); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Schnellaktionen</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <button onclick="showTopupModal()" class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left">
                        <div class="w-8 h-8 bg-green-900 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-plus text-green-400"></i>
                        </div>
                        <span class="text-sm font-medium text-white">Guthaben aufladen</span>
                    </button>
                    
                    <button class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left">
                        <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-file-alt text-blue-400"></i>
                        </div>
                        <span class="text-sm font-medium text-white">Rechnungen exportieren</span>
                    </button>
                    
                    <button class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left">
                        <div class="w-8 h-8 bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-cog text-purple-400"></i>
                        </div>
                        <span class="text-sm font-medium text-white">Zahlungseinstellungen</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top-up Modal -->
<div id="topupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-white">Guthaben aufladen</h2>
                <button onclick="closeTopupModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
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
        document.body.style.overflow = 'hidden';
    }

    function closeTopupModal() {
        document.getElementById('topupModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function setAmount(amount) {
        document.getElementById('topupAmount').value = amount;
        // Highlight selected button
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'border-blue-500');
            btn.classList.add('border-gray-600');
        });
        event.target.classList.add('bg-blue-600', 'border-blue-500');
        event.target.classList.remove('border-gray-600');
    }

    function payInvoice(invoiceId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="pay_invoice">
            <input type="hidden" name="invoice_id" value="${invoiceId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // Modal schließen bei Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTopupModal();
        }
    });

    // Modal schließen bei Klick außerhalb
    document.getElementById('topupModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTopupModal();
        }
    });
</script>

<?php
});
?>