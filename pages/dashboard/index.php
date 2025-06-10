<?php
require_once __DIR__ . '/../../includes/dashboard-layout.php';

// Dark Version Dashboard - Hauptseite
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];
$db = Database::getInstance();

// Dashboard-Statistiken aus der Datenbank laden
try {
    // Aktive Services zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_services = $stmt->fetchColumn();

    // Offene Rechnungen zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_invoices = $stmt->fetchColumn();

    // Support Tickets zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status != 'closed'");
    $stmt->execute([$user_id]);
    $open_tickets = $stmt->fetchColumn();

    // Aktueller Kontostand
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $account_balance = $stmt->fetchColumn() ?: 0.00;

    // Letzte Services
    $stmt = $db->prepare("
        SELECT s.*, st.name as service_type_name 
        FROM services s 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Letzte Rechnungen
    $stmt = $db->prepare("
        SELECT * FROM invoices 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // System-Nachrichten
    $stmt = $db->prepare("
        SELECT * FROM system_notifications 
        WHERE (user_id = ? OR user_id IS NULL) 
        AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    $active_services = 0;
    $pending_invoices = 0;
    $open_tickets = 0;
    $account_balance = 0.00;
    $recent_services = [];
    $recent_invoices = [];
    $notifications = [];
}

renderDashboardHeader('Dashboard - SpectraHost');
?>

<div class="min-h-screen bg-gray-900">
    <!-- Top Navigation -->
    <nav class="bg-gray-800 shadow-lg border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-300">
                        Guthaben: <span class="font-bold text-green-400">€<?php echo number_format($account_balance, 2); ?></span>
                    </div>
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-gray-300 hover:text-white">
                            <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></span>
                            </div>
                            <span class="text-sm font-medium"><?php echo htmlspecialchars($user['first_name']); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white">Willkommen zurück, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            <p class="mt-2 text-gray-400">Hier ist eine Übersicht über Ihre SpectraHost Services</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-server text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Aktive Services</p>
                        <p class="text-2xl font-bold text-white"><?php echo $active_services; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-euro-sign text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Kontostand</p>
                        <p class="text-2xl font-bold text-green-400">€<?php echo number_format($account_balance, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-invoice text-orange-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Offene Rechnungen</p>
                        <p class="text-2xl font-bold text-white"><?php echo $pending_invoices; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-life-ring text-purple-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Support Tickets</p>
                        <p class="text-2xl font-bold text-white"><?php echo $open_tickets; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Services -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">Meine Services</h3>
                            <a href="/dashboard/services" class="text-sm text-blue-400 hover:text-blue-300">Alle anzeigen</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($recent_services)): ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_services as $service): ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-600 rounded-lg bg-gray-750">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-900 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-server text-blue-400"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($service['service_type_name'] ?? 'Service'); ?></p>
                                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($service['name']); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $service['status'] == 'active' ? 'bg-green-900 text-green-400' : 'bg-gray-700 text-gray-300'; ?>">
                                                <?php echo ucfirst($service['status']); ?>
                                            </span>
                                            <button class="text-gray-400 hover:text-gray-300">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-server text-gray-600 text-4xl mb-4"></i>
                                <p class="text-gray-400">Keine Services gefunden</p>
                                <a href="/products" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                                    Services bestellen
                                </a>
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
                        <h3 class="text-lg font-medium text-white">Quick Actions</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <a href="/products" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg font-medium hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Service bestellen
                            </a>
                            <a href="/dashboard/billing" class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded-lg font-medium hover:bg-green-700">
                                <i class="fas fa-credit-card mr-2"></i>Guthaben aufladen
                            </a>
                            <a href="/dashboard/support" class="block w-full bg-purple-600 text-white text-center py-2 px-4 rounded-lg font-medium hover:bg-purple-700">
                                <i class="fas fa-headset mr-2"></i>Support kontaktieren
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">Letzte Rechnungen</h3>
                            <a href="/dashboard/billing" class="text-sm text-blue-400 hover:text-blue-300">Alle anzeigen</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($recent_invoices)): ?>
                            <div class="space-y-3">
                                <?php foreach ($recent_invoices as $invoice): ?>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-white">€<?php echo number_format($invoice['amount'], 2); ?></p>
                                            <p class="text-xs text-gray-400"><?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?></p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $invoice['status'] == 'paid' ? 'bg-green-900 text-green-400' : 'bg-orange-900 text-orange-400'; ?>">
                                            <?php echo ucfirst($invoice['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-400">Keine Rechnungen vorhanden</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if (!empty($notifications)): ?>
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Benachrichtigungen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="p-3 bg-blue-900 border border-blue-800 rounded-lg">
                                    <p class="text-sm text-blue-200"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <p class="text-xs text-blue-400 mt-1"><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></p>
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

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderDashboardFooter(); ?>