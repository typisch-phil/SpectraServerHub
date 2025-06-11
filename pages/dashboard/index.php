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
require_once __DIR__ . '/../../includes/unread-notifications.php';
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get current user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    header("Location: /login");
    exit;
}

// Dashboard-Statistiken aus der Datenbank laden
try {
    // Aktive Services zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_services = $stmt->fetchColumn() ?: 0;

    // Offene Rechnungen zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_invoices = $stmt->fetchColumn() ?: 0;

    // Support Tickets zählen
    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status != 'closed'");
    $stmt->execute([$user_id]);
    $open_tickets = $stmt->fetchColumn() ?: 0;

    // Kontostand abrufen
    $account_balance = $user['balance'] ?? 0.00;

    // Letzte Services abrufen
    $stmt = $db->prepare("
        SELECT s.*, st.name as service_type_name, st.category, st.icon 
        FROM services s 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_services = $stmt->fetchAll() ?: [];

    // Letzte Rechnungen abrufen
    $stmt = $db->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_invoices = $stmt->fetchAll() ?: [];

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $active_services = 0;
    $pending_invoices = 0;
    $open_tickets = 0;
    $account_balance = 0.00;
    $recent_services = [];
    $recent_invoices = [];
}
?>

<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SpectraHost</title>
    <meta name="description" content="SpectraHost Dashboard - Verwalten Sie Ihre Services">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            750: '#374151',
                            850: '#1f2937'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">

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
                        <a href="/dashboard" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium flex items-center">
                            Support
                            <?php echo getTicketNotificationBadge($db, $user_id); ?>
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-300">
                        Guthaben: <span class="font-bold text-green-400">€<?php echo number_format($account_balance, 2); ?></span>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-300 hover:text-white focus:outline-none">
                            <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium"><?php echo strtoupper(substr($user['email'] ?? 'U', 0, 1)); ?></span>
                            </div>
                            <span class="text-sm"><?php echo htmlspecialchars($user['email'] ?? 'Benutzer'); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <a href="/dashboard/profile" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profil bearbeiten</a>
                            <a href="/dashboard/settings" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Einstellungen</a>
                            <div class="border-t border-gray-700"></div>
                            <a href="/logout" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Abmelden</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Aktive Services -->
            <div class="bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-server text-blue-400"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Aktive Services</dt>
                                <dd class="text-2xl font-bold text-white"><?php echo $active_services; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-750 px-5 py-3">
                    <div class="text-sm">
                        <a href="/dashboard/services" class="font-medium text-blue-400 hover:text-blue-300">Services verwalten</a>
                    </div>
                </div>
            </div>

            <!-- Offene Rechnungen -->
            <div class="bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-invoice text-orange-400"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Offene Rechnungen</dt>
                                <dd class="text-2xl font-bold text-white"><?php echo $pending_invoices; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-750 px-5 py-3">
                    <div class="text-sm">
                        <a href="/dashboard/billing" class="font-medium text-orange-400 hover:text-orange-300">Rechnungen anzeigen</a>
                    </div>
                </div>
            </div>

            <!-- Support Tickets -->
            <div class="bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-headset text-green-400"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Offene Tickets</dt>
                                <dd class="text-2xl font-bold text-white"><?php echo $open_tickets; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-750 px-5 py-3">
                    <div class="text-sm">
                        <a href="/dashboard/support" class="font-medium text-green-400 hover:text-green-300">Support kontaktieren</a>
                    </div>
                </div>
            </div>

            <!-- Kontostand -->
            <div class="bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-wallet text-purple-400"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Guthaben</dt>
                                <dd class="text-2xl font-bold text-white">€<?php echo number_format($account_balance, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-750 px-5 py-3">
                    <div class="text-sm">
                        <a href="/dashboard/billing?action=topup" class="font-medium text-purple-400 hover:text-purple-300">Guthaben aufladen</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Services -->
            <div class="bg-gray-800 shadow rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Letzte Services</h3>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php if (empty($recent_services)): ?>
                        <div class="p-6 text-center">
                            <div class="w-12 h-12 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-server text-gray-400"></i>
                            </div>
                            <h4 class="text-sm font-medium text-white">Keine Services vorhanden</h4>
                            <p class="text-sm text-gray-400 mt-1">Bestellen Sie Ihren ersten Service</p>
                            <a href="/products" class="mt-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Services ansehen
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_services as $service): ?>
                            <div class="p-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-900 rounded-lg flex items-center justify-center">
                                        <i class="<?php echo $service['icon'] ?? 'fas fa-server'; ?> text-blue-400"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($service['name']); ?></p>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($service['service_type_name'] ?? 'Service'); ?></p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($service['status'] ?? 'active') {
                                                case 'active': echo 'bg-green-900 text-green-300'; break;
                                                case 'pending': echo 'bg-yellow-900 text-yellow-300'; break;
                                                case 'suspended': echo 'bg-red-900 text-red-300'; break;
                                                default: echo 'bg-gray-700 text-gray-300';
                                            }
                                            ?>">
                                            <?php echo ucfirst($service['status'] ?? 'active'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="px-6 py-3 bg-gray-750">
                            <a href="/dashboard/services" class="text-sm font-medium text-blue-400 hover:text-blue-300">Alle Services anzeigen →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="bg-gray-800 shadow rounded-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Letzte Rechnungen</h3>
                </div>
                <div class="divide-y divide-gray-700">
                    <?php if (empty($recent_invoices)): ?>
                        <div class="p-6 text-center">
                            <div class="w-12 h-12 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-invoice text-gray-400"></i>
                            </div>
                            <h4 class="text-sm font-medium text-white">Keine Rechnungen vorhanden</h4>
                            <p class="text-sm text-gray-400 mt-1">Ihre Rechnungen werden hier angezeigt</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_invoices as $invoice): ?>
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-orange-900 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-file-invoice text-orange-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-white">Rechnung #<?php echo htmlspecialchars($invoice['invoice_number'] ?? 'N/A'); ?></p>
                                            <p class="text-sm text-gray-400"><?php echo date('d.m.Y', strtotime($invoice['created_at'] ?? 'now')); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-white">€<?php echo number_format($invoice['amount'] ?? 0, 2); ?></p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($invoice['status'] ?? 'pending') {
                                                case 'paid': echo 'bg-green-900 text-green-300'; break;
                                                case 'pending': echo 'bg-yellow-900 text-yellow-300'; break;
                                                case 'overdue': echo 'bg-red-900 text-red-300'; break;
                                                default: echo 'bg-gray-700 text-gray-300';
                                            }
                                            ?>">
                                            <?php 
                                            $status_labels = [
                                                'paid' => 'Bezahlt',
                                                'pending' => 'Offen',
                                                'overdue' => 'Überfällig'
                                            ];
                                            echo $status_labels[$invoice['status'] ?? 'pending'] ?? ucfirst($invoice['status'] ?? 'pending');
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="px-6 py-3 bg-gray-750">
                            <a href="/dashboard/billing" class="text-sm font-medium text-orange-400 hover:text-orange-300">Alle Rechnungen anzeigen →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>