<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$user = $_SESSION['user'];
$user_id = $user['id'];

$database = Database::getInstance();

// Get current user balance from database
$stmt = $database->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_balance = $stmt->fetchColumn() ?: 0.00;

// Update session with current balance
$_SESSION['user']['balance'] = $current_balance;
$user['balance'] = $current_balance;

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

// Handle payment notifications
$notification = '';
$notification_type = '';

if (isset($_GET['success']) && $_GET['success'] === 'payment_completed') {
    $amount = $_GET['amount'] ?? '0';
    $notification = "Zahlung erfolgreich! €" . number_format((float)$amount, 2) . " wurden Ihrem Guthaben gutgeschrieben.";
    $notification_type = 'success';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'payment_failed':
            $status = $_GET['status'] ?? 'unknown';
            $notification = "Zahlung fehlgeschlagen. Status: " . htmlspecialchars($status);
            $notification_type = 'error';
            break;
        case 'invalid_payment':
            $notification = "Ungültige Zahlungs-ID.";
            $notification_type = 'error';
            break;
        case 'payment_not_found':
            $notification = "Zahlung nicht gefunden.";
            $notification_type = 'error';
            break;
        default:
            $notification = "Ein unbekannter Fehler ist aufgetreten.";
            $notification_type = 'error';
    }
}
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
        <?php if ($notification): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $notification_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $notification_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i>
                    <span><?php echo htmlspecialchars($notification); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
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
                    <input type="number" name="amount" id="amount" min="10" max="1000" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="z.B. 50.00" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zahlungsmethode</label>
                    <div id="payment-methods-loading" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Zahlungsmethoden werden geladen...
                    </div>
                    <div id="payment-methods-container" class="hidden space-y-3">
                        <!-- Payment methods will be loaded here -->
                    </div>
                    <input type="hidden" id="payment-method-select" name="payment_method" required>
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
            loadPaymentMethods();
        }
        
        async function loadPaymentMethods() {
            try {
                const response = await fetch('/api/payment/mollie-methods.php');
                const result = await response.json();
                
                const loadingDiv = document.getElementById('payment-methods-loading');
                const containerDiv = document.getElementById('payment-methods-container');
                const selectElement = document.getElementById('payment-method-select');
                
                if (result.success && result.methods.length > 0) {
                    loadingDiv.classList.add('hidden');
                    
                    // Show visual payment method selection
                    containerDiv.classList.remove('hidden');
                    
                    // Clear existing content
                    containerDiv.innerHTML = '';
                    
                    let selectedMethod = null;
                    
                    result.methods.forEach((method, index) => {
                        // Create visual method card
                        const methodCard = document.createElement('div');
                        methodCard.className = `payment-method-card border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-colors ${index === 0 ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : ''}`;
                        methodCard.dataset.method = method.id;
                        
                        methodCard.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    ${method.image ? `<img src="${method.image}" alt="${method.description}" class="w-10 h-10 mr-4 rounded">` : `<div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded mr-4 flex items-center justify-center"><i class="fas fa-credit-card text-gray-500"></i></div>`}
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">${method.description}</div>
                                        ${method.pricing ? `<div class="text-sm text-gray-500 dark:text-gray-400">Gebühr: €${method.pricing.fixed} + ${(parseFloat(method.pricing.variable) * 100).toFixed(1)}%</div>` : ''}
                                    </div>
                                </div>
                                <div class="radio-indicator w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center ${index === 0 ? 'border-blue-500 bg-blue-500' : ''}">
                                    ${index === 0 ? '<div class="w-2.5 h-2.5 bg-white rounded-full"></div>' : ''}
                                </div>
                            </div>
                        `;
                        
                        methodCard.addEventListener('click', () => selectPaymentMethod(method.id));
                        containerDiv.appendChild(methodCard);
                        
                        if (index === 0) {
                            selectedMethod = method.id;
                            selectElement.value = method.id;
                        }
                    });
                    
                } else {
                    // Show error message if no methods available
                    loadingDiv.classList.add('hidden');
                    containerDiv.innerHTML = '<div class="text-red-500 p-4 text-center">Keine Zahlungsmethoden verfügbar. Bitte kontaktieren Sie den Support.</div>';
                }
                
            } catch (error) {
                console.error('Error loading payment methods:', error);
                
                // Show error message
                const loadingDiv = document.getElementById('payment-methods-loading');
                const containerDiv = document.getElementById('payment-methods-container');
                
                loadingDiv.classList.add('hidden');
                containerDiv.innerHTML = '<div class="text-red-500 p-4 text-center">Fehler beim Laden der Zahlungsmethoden. Bitte versuchen Sie es später erneut.</div>';
            }
        }
        
        function selectPaymentMethod(methodId) {
            // Update visual selection
            document.querySelectorAll('.payment-method-card').forEach(card => {
                const indicator = card.querySelector('.radio-indicator');
                if (card.dataset.method === methodId) {
                    card.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
                    card.classList.remove('border-gray-200', 'dark:border-gray-600');
                    indicator.classList.add('border-blue-500', 'bg-blue-500');
                    indicator.classList.remove('border-gray-300');
                    indicator.innerHTML = '<div class="w-2.5 h-2.5 bg-white rounded-full"></div>';
                } else {
                    card.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
                    card.classList.add('border-gray-200', 'dark:border-gray-600');
                    indicator.classList.remove('border-blue-500', 'bg-blue-500');
                    indicator.classList.add('border-gray-300');
                    indicator.innerHTML = '';
                }
            });
            
            // Update hidden input value
            document.getElementById('payment-method-select').value = methodId;
        }

        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }

        document.getElementById('addBalanceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const amount = this.querySelector('input[name="amount"]').value;
            const method = this.querySelector('input[name="payment_method"]').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validate amount
            if (amount < 10 || amount > 1000) {
                showNotification('Betrag muss zwischen €10 und €1000 liegen', 'error');
                return;
            }
            
            // Validate payment method selection
            if (!method) {
                showNotification('Bitte wählen Sie eine Zahlungsmethode aus', 'error');
                return;
            }
            
            // Set loading state
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Zahlung wird erstellt...';
            
            try {
                const response = await fetch('/api/payment/mollie.php?action=create_payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        amount: amount,
                        payment_method: method
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Weiterleitung zur Zahlung...', 'info');
                    // Redirect to Mollie payment page
                    window.location.href = result.payment_url;
                } else {
                    showNotification(result.error || 'Fehler bei der Zahlungserstellung', 'error');
                }
            } catch (error) {
                console.error('Payment creation error:', error);
                showNotification('Netzwerkfehler. Bitte versuchen Sie es erneut.', 'error');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        // Close modal with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${getNotificationClass(type)}`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        function getNotificationClass(type) {
            const classes = {
                'success': 'bg-green-100 border border-green-400 text-green-700',
                'error': 'bg-red-100 border border-red-400 text-red-700',
                'warning': 'bg-yellow-100 border border-yellow-400 text-yellow-700',
                'info': 'bg-blue-100 border border-blue-400 text-blue-700'
            };
            return classes[type] || classes.info;
        }
        
        // Handle URL parameters for payment status
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === 'payment_completed') {
            showNotification('Zahlung erfolgreich abgeschlossen! Ihr Guthaben wurde aufgeladen.', 'success');
        } else if (urlParams.get('error')) {
            const error = urlParams.get('error');
            let message = 'Ein Fehler ist aufgetreten.';
            if (error === 'payment_failed') message = 'Zahlung fehlgeschlagen. Bitte versuchen Sie es erneut.';
            if (error === 'payment_not_found') message = 'Zahlung nicht gefunden.';
            if (error === 'invalid_payment') message = 'Ungültige Zahlungsparameter.';
            showNotification(message, 'error');
        }

        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.classList.remove('dark', 'light');
            html.classList.add(newTheme);
            
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const themeToggle = document.getElementById('theme-toggle');
            const isDark = document.documentElement.classList.contains('dark');
            themeToggle.innerHTML = isDark 
                ? '<i class="fas fa-sun text-yellow-500"></i>'
                : '<i class="fas fa-moon text-gray-600"></i>';
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(savedTheme);
            updateThemeIcon();
            
            // Add event listener to theme toggle button
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
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