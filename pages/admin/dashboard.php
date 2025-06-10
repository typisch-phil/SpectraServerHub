<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/admin_layout.php';

requireAdmin();

$title = 'Dashboard';
$description = 'Administrationsbereich für SpectraHost';
renderAdminHeader($title, $description);

// Dashboard Statistics
$database = Database::getInstance();
try {
    $stmt = $database->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 0");
    $stmt->execute();
    $total_users = $stmt->fetchColumn();
    
    $stmt = $database->prepare("SELECT COUNT(*) FROM user_services WHERE status = 'active'");
    $stmt->execute();
    $active_services = $stmt->fetchColumn();
    
    $stmt = $database->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $pending_orders = $stmt->fetchColumn();
    
    $stmt = $database->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'open'");
    $stmt->execute();
    $open_tickets = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_users = $active_services = $pending_orders = $open_tickets = 0;
}
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Admin Panel</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-user mr-2"></i>Zum Dashboard
                    </a>
                    <a href="/api/logout" class="btn-outline">
                        <i class="fas fa-sign-out-alt mr-2"></i>Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Kunden</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $total_users ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <i class="fas fa-server text-green-600 dark:text-green-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktive Services</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $active_services ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                        <i class="fas fa-shopping-cart text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Offene Bestellungen</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $pending_orders ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                        <i class="fas fa-ticket-alt text-red-600 dark:text-red-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Offene Tickets</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $open_tickets ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-users mr-2"></i>Benutzerverwaltung
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Kunden verwalten, bearbeiten und überwachen</p>
                <a href="/admin/users" class="btn-primary w-full">Benutzer verwalten</a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-ticket-alt mr-2"></i>Support-Tickets
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Kundenanfragen bearbeiten und beantworten</p>
                <a href="/admin/tickets" class="btn-primary w-full">Tickets bearbeiten</a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-cog mr-2"></i>Services verwalten
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Hosting-Pakete und Services konfigurieren</p>
                <a href="/admin/services" class="btn-primary w-full">Services verwalten</a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-file-invoice mr-2"></i>Rechnungen
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Rechnungen erstellen und verwalten</p>
                <a href="/admin/invoices" class="btn-primary w-full">Rechnungen verwalten</a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-plug mr-2"></i>Integrationen
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Mollie & Proxmox Konfiguration</p>
                <a href="/admin/integrations" class="btn-primary w-full">Integrationen</a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>Statistiken
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">Umsatz und Performance-Berichte</p>
                <a href="/admin/statistics" class="btn-primary w-full">Statistiken ansehen</a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Letzte Aktivitäten</h3>
            </div>
            <div class="p-6">
                <div id="recent-activity">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">Lade Aktivitäten...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRecentActivity();
});

async function loadRecentActivity() {
    try {
        const response = await apiRequest('/api/admin/activity');
        if (response.success) {
            renderRecentActivity(response.activities);
        }
    } catch (error) {
        document.getElementById('recent-activity').innerHTML = 
            '<p class="text-red-500 text-center">Fehler beim Laden der Aktivitäten</p>';
    }
}

function renderRecentActivity(activities) {
    const container = document.getElementById('recent-activity');
    
    if (activities.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center">Keine Aktivitäten verfügbar</p>';
        return;
    }
    
    const html = activities.map(activity => `
        <div class="flex items-center py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
            <div class="flex-shrink-0">
                <i class="fas ${getActivityIcon(activity.type)} text-gray-400"></i>
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm text-gray-900 dark:text-white">${activity.description}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${formatDateTime(activity.created_at)}</p>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

function getActivityIcon(type) {
    const icons = {
        'user_registered': 'fa-user-plus',
        'order_created': 'fa-shopping-cart',
        'payment_received': 'fa-credit-card',
        'ticket_created': 'fa-ticket-alt',
        'service_activated': 'fa-check-circle'
    };
    return icons[type] || 'fa-info-circle';
}
</script>

<?php renderAdminFooter(); ?>