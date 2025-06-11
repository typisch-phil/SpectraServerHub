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

// Tickets laden mit Benutzerinformationen
$tickets = [];
try {
    $stmt = $db->query("
        SELECT st.*, 
               CONCAT(u.first_name, ' ', u.last_name) as user_name,
               u.email as user_email,
               (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = st.id) as message_count,
               (SELECT tm.created_at FROM ticket_messages tm WHERE tm.ticket_id = st.id ORDER BY tm.created_at DESC LIMIT 1) as last_message_at
        FROM support_tickets st 
        LEFT JOIN users u ON st.user_id = u.id 
        ORDER BY 
            CASE st.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END,
            st.created_at DESC
    ");
    $tickets = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading tickets: " . $e->getMessage());
}

// Statistiken berechnen
$statusCounts = [
    'open' => 0,
    'in_progress' => 0, 
    'waiting_customer' => 0,
    'resolved' => 0,
    'closed' => 0
];

foreach ($tickets as $ticket) {
    $statusCounts[$ticket['status']]++;
}

$pageTitle = "Ticket-System - SpectraHost Admin";
$pageDescription = "Verwaltung von Support-Tickets und Kundenanfragen";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 to-blue-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Ticket-System</h1>
                    <p class="text-gray-200">Verwaltung von Support-Tickets und Kundenanfragen</p>
                </div>
                <div class="hidden md:block">
                    <div class="text-right">
                        <div class="text-gray-300 text-sm">Offene Tickets</div>
                        <div class="text-white font-semibold text-2xl"><?php echo $statusCounts['open'] + $statusCounts['in_progress']; ?></div>
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

        <!-- Ticket Status Übersicht -->
        <div class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-6">
            <div class="bg-gradient-to-br from-red-800 to-red-900 rounded-2xl p-6 border border-red-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $statusCounts['open']; ?></div>
                        <div class="text-red-200 text-sm">Offen</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-800 to-yellow-900 rounded-2xl p-6 border border-yellow-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $statusCounts['in_progress']; ?></div>
                        <div class="text-yellow-200 text-sm">In Bearbeitung</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-800 to-orange-900 rounded-2xl p-6 border border-orange-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-user-clock text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $statusCounts['waiting_customer']; ?></div>
                        <div class="text-orange-200 text-sm">Warten auf Kunde</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-800 to-blue-900 rounded-2xl p-6 border border-blue-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $statusCounts['resolved']; ?></div>
                        <div class="text-blue-200 text-sm">Gelöst</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-800 to-green-900 rounded-2xl p-6 border border-green-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-archive text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $statusCounts['closed']; ?></div>
                        <div class="text-green-200 text-sm">Geschlossen</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Übersicht -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-ticket-alt mr-3"></i>Support Tickets
                </h2>
                <div class="flex space-x-3">
                    <select class="bg-gray-700 text-white px-3 py-2 rounded-lg border border-gray-600">
                        <option value="">Alle Status</option>
                        <option value="open">Offen</option>
                        <option value="in_progress">In Bearbeitung</option>
                        <option value="waiting_customer">Warten auf Kunde</option>
                        <option value="resolved">Gelöst</option>
                        <option value="closed">Geschlossen</option>
                    </select>
                    <select class="bg-gray-700 text-white px-3 py-2 rounded-lg border border-gray-600">
                        <option value="">Alle Prioritäten</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">Hoch</option>
                        <option value="medium">Mittel</option>
                        <option value="low">Niedrig</option>
                    </select>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">ID</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Betreff</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Kunde</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Kategorie</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Status</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Priorität</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Nachrichten</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Erstellt</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-8 text-gray-400">
                                Keine Tickets vorhanden
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-700/30">
                            <td class="py-4 px-4">
                                <div class="text-white font-medium">#<?php echo $ticket['id']; ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-white font-medium max-w-xs truncate"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                <div class="text-gray-400 text-sm max-w-xs truncate"><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)) . '...'; ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-white"><?php echo htmlspecialchars($ticket['user_name'] ?? 'Unbekannt'); ?></div>
                                <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($ticket['user_email'] ?? ''); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    switch($ticket['category']) {
                                        case 'technical': echo 'bg-blue-900 text-blue-200'; break;
                                        case 'billing': echo 'bg-green-900 text-green-200'; break;
                                        case 'general': echo 'bg-gray-900 text-gray-200'; break;
                                        case 'abuse': echo 'bg-red-900 text-red-200'; break;
                                        default: echo 'bg-gray-900 text-gray-200';
                                    }
                                    ?>">
                                    <?php echo ucfirst($ticket['category']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <?php
                                $statusColors = [
                                    'open' => 'bg-red-900 text-red-200',
                                    'in_progress' => 'bg-yellow-900 text-yellow-200',
                                    'waiting_customer' => 'bg-orange-900 text-orange-200',
                                    'resolved' => 'bg-blue-900 text-blue-200',
                                    'closed' => 'bg-green-900 text-green-200'
                                ];
                                $statusLabels = [
                                    'open' => 'Offen',
                                    'in_progress' => 'In Bearbeitung',
                                    'waiting_customer' => 'Warten auf Kunde',
                                    'resolved' => 'Gelöst',
                                    'closed' => 'Geschlossen'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColors[$ticket['status']]; ?>">
                                    <?php echo $statusLabels[$ticket['status']]; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <?php
                                $priorityColors = [
                                    'urgent' => 'bg-red-900 text-red-200',
                                    'high' => 'bg-orange-900 text-orange-200',
                                    'medium' => 'bg-yellow-900 text-yellow-200',
                                    'low' => 'bg-green-900 text-green-200'
                                ];
                                $priorityLabels = [
                                    'urgent' => 'Urgent',
                                    'high' => 'Hoch',
                                    'medium' => 'Mittel',
                                    'low' => 'Niedrig'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $priorityColors[$ticket['priority']]; ?>">
                                    <?php echo $priorityLabels[$ticket['priority']]; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-white font-medium"><?php echo $ticket['message_count']; ?></div>
                                <?php if ($ticket['last_message_at']): ?>
                                <div class="text-gray-400 text-sm"><?php echo date('d.m.Y H:i', strtotime($ticket['last_message_at'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-300"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-blue-400 hover:text-blue-300 p-1" title="Anzeigen" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-green-400 hover:text-green-300 p-1" title="Antworten" onclick="replyTicket(<?php echo $ticket['id']; ?>)">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button class="text-yellow-400 hover:text-yellow-300 p-1" title="Status ändern" onclick="changeStatus(<?php echo $ticket['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function viewTicket(ticketId) {
    // TODO: Implementiere Ticket-Detail-View
    console.log('View ticket:', ticketId);
}

function replyTicket(ticketId) {
    // TODO: Implementiere Reply-Funktion
    console.log('Reply to ticket:', ticketId);
}

function changeStatus(ticketId) {
    // TODO: Implementiere Status-Änderung
    console.log('Change status for ticket:', ticketId);
}
</script>

<?php
renderFooter();
?>