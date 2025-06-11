<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Admin-Authentifizierung überprüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Überprüfung ob Benutzer Admin-Rechte hat
$db = Database::getInstance();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

// Tickets laden
$tickets = [];
try {
    $stmt = $db->query("
        SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email as user_email,
               COUNT(tr.id) as reply_count
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN ticket_replies tr ON t.id = tr.ticket_id
        GROUP BY t.id
        ORDER BY 
            CASE t.priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END,
            CASE t.status 
                WHEN 'open' THEN 1 
                WHEN 'in_progress' THEN 2 
                WHEN 'waiting_customer' THEN 3 
                WHEN 'closed' THEN 4 
                ELSE 5 
            END,
            t.updated_at DESC
    ");
    $tickets = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading tickets: " . $e->getMessage());
}

$pageTitle = "Support-Tickets - SpectraHost Admin";
$pageDescription = "Verwaltung von Kundenanfragen und Support-Tickets";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 to-blue-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Support-Tickets</h1>
                    <p class="text-gray-200">Verwaltung von Kundenanfragen und Support-Tickets</p>
                </div>
                <div class="hidden md:block">
                    <div class="text-right">
                        <div class="text-gray-300 text-sm">Gesamt Tickets</div>
                        <div class="text-white font-semibold text-2xl"><?php echo count($tickets); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-8">
                <a href="/admin/dashboard" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Dashboard</a>
                <a href="/admin/users" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Benutzer</a>
                <a href="/admin/services" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Services</a>
                <a href="/admin/tickets" class="text-white bg-purple-600 px-4 py-2 rounded-lg font-medium">Tickets</a>
                <a href="/admin/ip-management" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">IP-Management</a>
            </nav>
        </div>

        <!-- Ticket Filter -->
        <div class="mb-6 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl border border-gray-700 p-4">
            <div class="flex flex-wrap gap-4">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Alle</button>
                <button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">Offen</button>
                <button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">In Bearbeitung</button>
                <button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">Warten auf Kunde</button>
                <button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">Geschlossen</button>
            </div>
        </div>

        <!-- Tickets Übersicht -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-ticket-alt mr-3"></i>Tickets Übersicht
                </h2>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Neues Ticket
                </button>
            </div>
            
            <div class="space-y-4">
                <?php if (empty($tickets)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>Keine Tickets vorhanden</p>
                </div>
                <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <div class="bg-gray-700/50 rounded-xl p-6 border border-gray-600 hover:bg-gray-700/70 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-4 mb-2">
                                <h3 class="text-lg font-semibold text-white">#<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['subject']); ?></h3>
                                
                                <!-- Priority Badge -->
                                <?php
                                $priorityColors = [
                                    'critical' => 'bg-red-900 text-red-200',
                                    'high' => 'bg-orange-900 text-orange-200',
                                    'medium' => 'bg-yellow-900 text-yellow-200',
                                    'low' => 'bg-green-900 text-green-200'
                                ];
                                $priorityColor = $priorityColors[$ticket['priority']] ?? 'bg-gray-900 text-gray-200';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $priorityColor; ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>

                                <!-- Status Badge -->
                                <?php
                                $statusColors = [
                                    'open' => 'bg-blue-900 text-blue-200',
                                    'in_progress' => 'bg-purple-900 text-purple-200',
                                    'waiting_customer' => 'bg-yellow-900 text-yellow-200',
                                    'closed' => 'bg-gray-900 text-gray-200'
                                ];
                                $statusColor = $statusColors[$ticket['status']] ?? 'bg-gray-900 text-gray-200';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                    <?php 
                                    $statusLabels = [
                                        'open' => 'Offen',
                                        'in_progress' => 'In Bearbeitung',
                                        'waiting_customer' => 'Warten auf Kunde',
                                        'closed' => 'Geschlossen'
                                    ];
                                    echo $statusLabels[$ticket['status']] ?? ucfirst($ticket['status']);
                                    ?>
                                </span>
                            </div>

                            <p class="text-gray-300 mb-3"><?php echo htmlspecialchars(substr($ticket['message'], 0, 150)) . (strlen($ticket['message']) > 150 ? '...' : ''); ?></p>
                            
                            <div class="flex items-center gap-6 text-sm text-gray-400">
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-2"></i>
                                    <?php echo htmlspecialchars($ticket['user_name'] ?? 'Unbekannt'); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-envelope mr-2"></i>
                                    <?php echo htmlspecialchars($ticket['user_email'] ?? ''); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-tag mr-2"></i>
                                    <?php echo ucfirst($ticket['category']); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock mr-2"></i>
                                    <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                                </div>
                                <?php if ($ticket['reply_count'] > 0): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-comments mr-2"></i>
                                    <?php echo $ticket['reply_count']; ?> Antworten
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex space-x-2 ml-4">
                            <button class="text-blue-400 hover:text-blue-300 p-2 rounded-lg hover:bg-gray-600" title="Antworten">
                                <i class="fas fa-reply"></i>
                            </button>
                            <button class="text-green-400 hover:text-green-300 p-2 rounded-lg hover:bg-gray-600" title="Bearbeiten">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-gray-600" title="Schließen">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ticket Statistiken -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <?php
            $statusCounts = [
                'open' => count(array_filter($tickets, fn($t) => $t['status'] === 'open')),
                'in_progress' => count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress')),
                'waiting_customer' => count(array_filter($tickets, fn($t) => $t['status'] === 'waiting_customer')),
                'closed' => count(array_filter($tickets, fn($t) => $t['status'] === 'closed'))
            ];
            
            $statusConfig = [
                'open' => ['color' => 'blue', 'icon' => 'fa-folder-open', 'label' => 'Offen'],
                'in_progress' => ['color' => 'purple', 'icon' => 'fa-cog', 'label' => 'In Bearbeitung'],
                'waiting_customer' => ['color' => 'yellow', 'icon' => 'fa-clock', 'label' => 'Warten auf Kunde'],
                'closed' => ['color' => 'green', 'icon' => 'fa-check-circle', 'label' => 'Geschlossen']
            ];
            
            foreach ($statusConfig as $status => $config):
            ?>
            <div class="bg-gradient-to-br from-<?php echo $config['color']; ?>-800 to-<?php echo $config['color']; ?>-900 rounded-2xl p-6 border border-<?php echo $config['color']; ?>-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-<?php echo $config['color']; ?>-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas <?php echo $config['icon']; ?> text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $statusCounts[$status]; ?></div>
                        <div class="text-<?php echo $config['color']; ?>-200 text-sm"><?php echo $config['label']; ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
renderFooter();
?>