<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// Get user's services
$stmt = $database->prepare("
    SELECT us.*, s.name, s.type, s.description, s.price, s.features 
    FROM user_services us 
    JOIN services s ON us.service_id = s.id 
    WHERE us.user_id = ? 
    ORDER BY us.created_at DESC
");
$stmt->execute([$user_id]);
$user_services = $stmt->fetchAll();

renderHeader('Meine Services - SpectraHost Dashboard');
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
                        <a href="/dashboard/services" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Meine Services</a>
                        <a href="/dashboard/billing" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Billing</a>
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Meine Services</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Ihre aktiven Hosting-Services und Server</p>
        </div>

        <!-- Services Grid -->
        <div class="grid gap-6">
            <?php if (empty($user_services)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-server text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Keine Services gefunden</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Sie haben noch keine Services bestellt. Starten Sie jetzt mit einem unserer Hosting-Pakete!</p>
                    <a href="/order" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Service bestellen
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($user_services as $service): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                                    <?php
                                    $icon = match($service['type']) {
                                        'webspace' => 'fas fa-globe',
                                        'vserver' => 'fas fa-server',
                                        'gameserver' => 'fas fa-gamepad',
                                        'domain' => 'fas fa-link',
                                        default => 'fas fa-cog'
                                    };
                                    ?>
                                    <i class="<?php echo $icon; ?> text-blue-600 dark:text-blue-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <?php if ($service['domain']): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($service['domain']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo getStatusClass($service['status']); ?>">
                                <?php echo getStatusText($service['status']); ?>
                            </span>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 mb-4"><?php echo htmlspecialchars($service['description']); ?></p>

                        <div class="flex justify-between items-center">
                            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                €<?php echo number_format($service['price'], 2); ?>/Monat
                            </div>
                            <div class="flex space-x-2">
                                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-cog mr-2"></i>Verwalten
                                </button>
                                <?php if ($service['type'] === 'vserver' || $service['type'] === 'gameserver'): ?>
                                    <button class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                                        <i class="fas fa-play mr-2"></i>Starten
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($service['next_payment']): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Nächste Zahlung: <?php echo date('d.m.Y', strtotime($service['next_payment'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function getStatusClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'suspended': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'terminated': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
            };
            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        }
        
        function getStatusText(status) {
            const texts = {
                'active': 'Aktiv',
                'pending': 'Ausstehend',
                'suspended': 'Gesperrt',
                'terminated': 'Beendet'
            };
            return texts[status] || status;
        }
    </script>
</div>

<?php renderFooter(); ?>