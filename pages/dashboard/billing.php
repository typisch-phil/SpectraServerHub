<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $_SESSION['user'];

$database = Database::getInstance();

// Get payment history
$stmt = $database->prepare("
    SELECT p.*, s.name as service_name 
    FROM payments p 
    LEFT JOIN services s ON p.service_id = s.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();

renderHeader('Billing - SpectraHost Dashboard');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Dashboard Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Meine Services</a>
                        <a href="/dashboard/billing" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Billing</a>
                        <a href="/dashboard/support" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Support</a>
                        <a href="/order" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Bestellen</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Guthaben:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400"><?php echo number_format($user['balance'] ?? 0, 2); ?> €</span>
                    </div>
                    <span class="text-gray-700 dark:text-gray-300">Willkommen, <?php echo htmlspecialchars($user['first_name'] ?? 'Benutzer'); ?></span>
                    <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                        <a href="/admin" class="btn-outline">Admin Panel</a>
                    <?php endif; ?>
                    <a href="/api/logout" class="btn-outline">Abmelden</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Billing & Zahlungen</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Ihr Guthaben und Zahlungshistorie</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Balance Card -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Aktuelles Guthaben</h2>
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                    
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-4">
                        €<?php echo number_format($user['balance'], 2); ?>
                    </div>
                    
                    <div class="space-y-3">
                        <button onclick="openAddBalanceModal()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i>
                            Guthaben aufladen
                        </button>
                        
                        <button class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Rechnung herunterladen
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schnellstatistiken</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Diesen Monat ausgegeben:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">€0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Aktive Services:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Nächste Zahlung:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Zahlungshistorie</h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($payments)): ?>
                            <div class="text-center py-8">
                                <div class="text-gray-400 mb-4">
                                    <i class="fas fa-receipt text-6xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Keine Zahlungen gefunden</h3>
                                <p class="text-gray-600 dark:text-gray-400">Sie haben noch keine Zahlungen getätigt.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                                                <i class="fas fa-credit-card text-blue-600 dark:text-blue-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    <?php echo $payment['service_name'] ? htmlspecialchars($payment['service_name']) : 'Guthaben aufgeladen'; ?>
                                                </h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                                                    <?php if ($payment['payment_method']): ?>
                                                        • <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right">
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                €<?php echo number_format($payment['amount'], 2); ?>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded-full <?php echo getPaymentStatusClass($payment['status']); ?>">
                                                <?php echo getPaymentStatusText($payment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Balance Modal -->
    <div id="addBalanceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Guthaben aufladen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addBalanceForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Betrag (EUR)</label>
                    <input type="number" min="10" max="1000" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="z.B. 50.00" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zahlungsmethode</label>
                    <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <option value="ideal">iDEAL</option>
                        <option value="creditcard">Kreditkarte</option>
                        <option value="banktransfer">Banküberweisung</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Weiter zur Zahlung
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddBalanceModal() {
            document.getElementById('addBalanceModal').classList.remove('hidden');
            document.getElementById('addBalanceModal').classList.add('flex');
        }

        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }

        document.getElementById('addBalanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const amount = this.querySelector('input[type="number"]').value;
            const method = this.querySelector('select').value;
            
            // Here you would typically send this to your payment processor
            alert(`Guthaben aufladen: €${amount} via ${method}`);
            closeModal();
        });

        // Close modal with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

    <?php
    function getPaymentStatusClass($status) {
        $classes = [
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
        ];
        return $classes[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
    
    function getPaymentStatusText($status) {
        $texts = [
            'completed' => 'Abgeschlossen',
            'pending' => 'Ausstehend',
            'failed' => 'Fehlgeschlagen',
            'cancelled' => 'Storniert'
        ];
        return $texts[$status] ?? $status;
    }
    ?>
</body>
</html>