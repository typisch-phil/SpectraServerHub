<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$user = $_SESSION['user'];
if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

$database = Database::getInstance();

// Tables are already created in the database initialization

// Get all tickets with user information and reply count
$stmt = $database->prepare("
    SELECT t.id, t.user_id, t.subject, t.message, t.status, t.priority, t.category, t.assigned_to, t.created_at, t.updated_at,
           u.email, u.first_name, u.last_name,
           COUNT(r.id) as reply_count
    FROM tickets t 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN ticket_replies r ON t.id = r.ticket_id
    GROUP BY t.id, t.user_id, t.subject, t.message, t.status, t.priority, t.category, t.assigned_to, t.created_at, t.updated_at, u.email, u.first_name, u.last_name
    ORDER BY 
        CASE t.status 
            WHEN 'open' THEN 1 
            WHEN 'waiting_customer' THEN 2 
            WHEN 'in_progress' THEN 3 
            WHEN 'closed' THEN 4 
            ELSE 5 
        END,
        CASE t.priority 
            WHEN 'critical' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
            ELSE 5 
        END,
        t.updated_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll();

$title = 'Support-Tickets - SpectraHost Admin';
$description = 'Verwalten Sie Kundenanfragen und Support-Tickets';
renderHeader($title, $description);
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/admin" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Admin Panel</a>
                        <a href="/admin/users" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Benutzer</a>
                        <a href="/admin/tickets" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Tickets</a>
                        <a href="/admin/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Services</a>
                        <a href="/admin/invoices" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Rechnungen</a>
                        <a href="/admin/integrations" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Integrationen</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-user mr-2"></i>Zum Dashboard
                    </a>
                    <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Support-Tickets</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Kundenanfragen und Support-Tickets</p>
        </div>

        <!-- Tickets Table -->
        <!-- Ticket Management Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Support-Tickets (<?= count($tickets) ?>)</h2>
                    
                    <!-- Ticket Statistics -->
                    <div class="flex space-x-4 text-sm">
                        <?php
                        $statusCounts = [];
                        foreach ($tickets as $ticket) {
                            $statusCounts[$ticket['status']] = ($statusCounts[$ticket['status']] ?? 0) + 1;
                        }
                        ?>
                        <div class="flex items-center space-x-1">
                            <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">Offen: <?= $statusCounts['open'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">In Bearbeitung: <?= $statusCounts['in_progress'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">Wartet: <?= $statusCounts['waiting_customer'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">Geschlossen: <?= $statusCounts['closed'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="px-6 py-4">
                <div class="flex flex-wrap gap-4 items-center justify-between">
                    <!-- Filters -->
                    <div class="flex space-x-3">
                        <select id="statusFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">Alle Status</option>
                            <option value="open">Offen</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="waiting_customer">Wartet auf Kunde</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                        
                        <select id="priorityFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">Alle Prioritäten</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="critical">Kritisch</option>
                        </select>
                        
                        <input type="text" id="searchFilter" placeholder="Suchen..." 
                               class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div class="flex space-x-2">
                        <select id="bulkAction" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">Bulk-Aktionen</option>
                            <option value="mark-solved">Als gelöst markieren</option>
                            <option value="mark-progress">In Bearbeitung setzen</option>
                            <option value="mark-waiting">Auf Kunde warten</option>
                            <option value="delete">Löschen</option>
                        </select>
                        <button onclick="executeBulkAction()" 
                                class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:bg-gray-400" 
                                id="bulkActionBtn" disabled>
                            Ausführen
                        </button>
                        <button onclick="refreshTickets()" 
                                class="px-4 py-2 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                            <i class="fas fa-sync-alt mr-1"></i>Aktualisieren
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                               class="mr-3 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-600 dark:text-gray-400" id="selectedCount">0 ausgewählt</span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400" id="filteredCount">
                        Zeige alle <?= count($tickets) ?> Tickets
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <input type="checkbox" id="selectAllTable" onchange="toggleSelectAll()" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Betreff</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kunde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priorität</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Antworten</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Erstellt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($tickets as $ticket): ?>
                        <tr class="ticket-row" data-ticket-id="<?= $ticket['id'] ?>" data-status="<?= $ticket['status'] ?>" data-priority="<?= $ticket['priority'] ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="ticket-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                                       value="<?= $ticket['id'] ?>" onchange="updateSelectedCount()">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">#<?= $ticket['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ticket['subject']) ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars(substr($ticket['message'], 0, 50)) ?>...</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white"><?= htmlspecialchars(trim($ticket['first_name'] . ' ' . $ticket['last_name']) ?: 'Unknown User') ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($ticket['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'open' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'waiting_customer' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'closed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                    ];
                                    $statusTexts = [
                                        'open' => 'Offen',
                                        'in_progress' => 'In Bearbeitung',
                                        'waiting_customer' => 'Wartet auf Kunde',
                                        'closed' => 'Geschlossen'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' ?>">
                                        <?= $statusTexts[$ticket['status']] ?? ucfirst($ticket['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $priorityColors = [
                                        'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'medium' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    ];
                                    $priorityTexts = [
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                        'critical' => 'Kritisch'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' ?>">
                                        <?= $priorityTexts[$ticket['priority']] ?? ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center">
                                        <i class="fas fa-comments mr-1"></i>
                                        <?= $ticket['reply_count'] ?? 0 ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                    <div><?= date('d.m.Y', strtotime($ticket['created_at'])) ?></div>
                                    <div class="text-xs text-gray-500"><?= date('H:i', strtotime($ticket['created_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewTicket(<?= $ticket['id'] ?>)" 
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 p-1 rounded hover:bg-indigo-50" 
                                                title="Ticket anzeigen">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="replyToTicket(<?= $ticket['id'] ?>)" 
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1 rounded hover:bg-blue-50" 
                                                title="Antworten">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <div class="relative">
                                            <button onclick="toggleStatusDropdown(<?= $ticket['id'] ?>)" 
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-1 rounded hover:bg-green-50" 
                                                    title="Status ändern">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div id="statusDropdown<?= $ticket['id'] ?>" 
                                                 class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-600">
                                                <div class="py-1">
                                                    <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'open')" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <i class="fas fa-circle text-red-500 mr-2"></i>Offen
                                                    </button>
                                                    <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'in_progress')" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <i class="fas fa-circle text-blue-500 mr-2"></i>In Bearbeitung
                                                    </button>
                                                    <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'waiting_customer')" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <i class="fas fa-circle text-yellow-500 mr-2"></i>Wartet auf Kunde
                                                    </button>
                                                    <button onclick="changeTicketStatus(<?= $ticket['id'] ?>, 'closed')" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <i class="fas fa-circle text-green-500 mr-2"></i>Geschlossen
                                                    </button>
                                                    <hr class="my-1 border-gray-200 dark:border-gray-600">
                                                    <button onclick="deleteTicket(<?= $ticket['id'] ?>)" 
                                                            class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900">
                                                        <i class="fas fa-trash mr-2"></i>Löschen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Detail Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="ticketModalTitle">Ticket Details</h3>
                    <button onclick="closeTicketModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="ticketContent">
                    <!-- Ticket content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Auf Ticket antworten</h3>
                    <button onclick="closeReplyModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="replyForm">
                    <input type="hidden" id="replyTicketId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Antwort</label>
                        <textarea id="replyMessage" rows="6" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Ihre Antwort..." required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="markResolved" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ticket als gelöst markieren</span>
                        </label>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="isInternalNote" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Interne Notiz (nicht für Kunde sichtbar)</span>
                        </label>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status nach Antwort</label>
                        <select id="statusAfterReply" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Status nicht ändern</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="waiting_customer">Wartet auf Kunde</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeReplyModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Abbrechen
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                            Antwort senden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Status ändern</h3>
                    <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="statusForm">
                    <input type="hidden" id="statusTicketId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Neuer Status</label>
                        <select id="newStatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" required>
                            <option value="open">Offen</option>
                            <option value="waiting_customer">Wartet auf Kunde</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Abbrechen
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                            Status aktualisieren
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div id="quickActionsPanel" class="fixed right-4 top-1/2 transform -translate-y-1/2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 hidden z-40">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Schnellaktionen</h4>
            <button onclick="toggleQuickActions()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-2 min-w-[200px]">
            <button onclick="createQuickReply('Vielen Dank für Ihre Nachricht. Wir bearbeiten Ihr Anliegen und melden uns in Kürze bei Ihnen.')" 
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i class="fas fa-reply mr-2"></i>Standard-Antwort
            </button>
            <button onclick="createQuickReply('Ihr Problem wurde erfolgreich gelöst. Sollten weitere Fragen aufkommen, kontaktieren Sie uns gerne.')" 
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i class="fas fa-check-circle mr-2"></i>Problem gelöst
            </button>
            <button onclick="createQuickReply('Wir benötigen weitere Informationen von Ihnen. Bitte teilen Sie uns folgende Details mit:')" 
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i class="fas fa-question-circle mr-2"></i>Info anfordern
            </button>
            <hr class="my-2">
            <button onclick="bulkMarkAsResolved()" 
                    class="w-full text-left px-3 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900 rounded">
                <i class="fas fa-check-double mr-2"></i>Ausgewählte als gelöst
            </button>
            <button onclick="bulkAssignToMe()" 
                    class="w-full text-left px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900 rounded">
                <i class="fas fa-user mr-2"></i>Mir zuweisen
            </button>
        </div>
    </div>

    <!-- Enhanced Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Ticket-Status verwalten</h3>
                    <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="statusForm">
                    <input type="hidden" id="statusTicketId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Neuer Status</label>
                        <select id="newStatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" required>
                            <option value="">Status wählen</option>
                            <option value="open">🔴 Offen</option>
                            <option value="in_progress">🔵 In Bearbeitung</option>
                            <option value="waiting_customer">🟡 Wartet auf Kunde</option>
                            <option value="closed">🟢 Geschlossen</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priorität ändern</label>
                        <select id="newPriority" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Priorität nicht ändern</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="critical">Kritisch</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Interne Notiz (optional)</label>
                        <textarea id="statusNote" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Grund für Statusänderung..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="notifyCustomer" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" checked>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Kunde über Statusänderung benachrichtigen</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Abbrechen
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                            <i class="fas fa-save mr-1"></i>Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Actions Trigger Button -->
    <button id="quickActionsTrigger" onclick="toggleQuickActions()" 
            class="fixed right-4 top-1/2 transform -translate-y-1/2 bg-purple-600 hover:bg-purple-700 text-white p-3 rounded-l-lg shadow-lg z-30">
        <i class="fas fa-bolt"></i>
    </button>

    <script>
        // View ticket details
        async function viewTicket(ticketId) {
            try {
                const response = await fetch(`/api/tickets.php?id=${ticketId}`, {
                    credentials: 'same-origin'
                });
                const ticket = await response.json();
                
                if (response.ok) {
                    document.getElementById('ticketModalTitle').textContent = `Ticket #${ticket.id} - ${ticket.subject}`;
                    
                    const statusColors = {
                        'open': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                        'waiting_customer': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        'in_progress': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        'closed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                    };
                    
                    const priorityColors = {
                        'low': 'bg-blue-100 text-blue-800',
                        'medium': 'bg-yellow-100 text-yellow-800',
                        'high': 'bg-orange-100 text-orange-800',
                        'critical': 'bg-red-100 text-red-800'
                    };
                    
                    document.getElementById('ticketContent').innerHTML = `
                        <div class="space-y-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex flex-wrap gap-4 mb-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusColors[ticket.status] || 'bg-gray-100 text-gray-800'}">
                                        ${ticket.status.replace('_', ' ').replace(/^\w/, c => c.toUpperCase())}
                                    </span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${priorityColors[ticket.priority] || 'bg-gray-100 text-gray-800'}">
                                        Priorität: ${ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        Erstellt: ${new Date(ticket.created_at).toLocaleDateString('de-DE')} ${new Date(ticket.created_at).toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'})}
                                    </span>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900 dark:text-white">Kunde:</span>
                                    <span class="text-gray-600 dark:text-gray-400">${ticket.first_name} ${ticket.last_name} (${ticket.email})</span>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ursprüngliche Nachricht</h4>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">${ticket.message}</p>
                                </div>
                            </div>
                            
                            <div id="ticketReplies">
                                <!-- Replies will be loaded here -->
                            </div>
                        </div>
                    `;
                    
                    // Load replies
                    loadTicketReplies(ticketId);
                    
                    document.getElementById('ticketModal').classList.remove('hidden');
                    document.getElementById('ticketModal').classList.add('flex');
                } else {
                    alert('Fehler beim Laden des Tickets: ' + ticket.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten.');
            }
        }

        // Load ticket replies
        async function loadTicketReplies(ticketId) {
            try {
                const response = await fetch(`/api/ticket-replies.php?ticket_id=${ticketId}`, {
                    credentials: 'same-origin'
                });
                const replies = await response.json();
                
                if (response.ok && replies.length > 0) {
                    const repliesContainer = document.getElementById('ticketReplies');
                    repliesContainer.innerHTML = `
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Antworten (${replies.length})</h4>
                        <div class="space-y-4">
                            ${replies.map(reply => `
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            ${reply.first_name} ${reply.last_name}
                                            ${reply.is_internal ? '<span class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full">Intern</span>' : ''}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            ${new Date(reply.created_at).toLocaleDateString('de-DE')} ${new Date(reply.created_at).toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'})}
                                        </div>
                                    </div>
                                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">${reply.message}</p>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading replies:', error);
            }
        }

        // Reply to ticket
        function replyToTicket(ticketId) {
            document.getElementById('replyTicketId').value = ticketId;
            document.getElementById('replyMessage').value = '';
            document.getElementById('markResolved').checked = false;
            document.getElementById('replyModal').classList.remove('hidden');
            document.getElementById('replyModal').classList.add('flex');
        }

        // Update ticket status
        function updateTicketStatus(ticketId, currentStatus) {
            document.getElementById('statusTicketId').value = ticketId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusModal').classList.add('flex');
        }

        // Close modals
        function closeTicketModal() {
            document.getElementById('ticketModal').classList.add('hidden');
            document.getElementById('ticketModal').classList.remove('flex');
        }

        function closeReplyModal() {
            document.getElementById('replyModal').classList.add('hidden');
            document.getElementById('replyModal').classList.remove('flex');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusModal').classList.remove('flex');
        }

        // Handle reply form submission
        document.getElementById('replyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const ticketId = document.getElementById('replyTicketId').value;
            const message = document.getElementById('replyMessage').value;
            const markResolved = document.getElementById('markResolved').checked;
            
            try {
                // Send reply
                const replyResponse = await fetch('/api/ticket-replies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message: message
                    })
                });
                
                if (replyResponse.ok) {
                    // Update status if marked as resolved
                    if (markResolved) {
                        await fetch('/api/tickets.php', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                id: ticketId,
                                status: 'closed'
                            })
                        });
                    }
                    
                    alert('Antwort erfolgreich gesendet!');
                    closeReplyModal();
                    location.reload(); // Refresh to show updated data
                } else {
                    const error = await replyResponse.json();
                    alert('Fehler: ' + error.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten.');
            }
        });

        // Handle status form submission
        document.getElementById('statusForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const ticketId = document.getElementById('statusTicketId').value;
            const newStatus = document.getElementById('newStatus').value;
            
            try {
                const response = await fetch('/api/tickets.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        id: ticketId,
                        status: newStatus
                    })
                });
                
                if (response.ok) {
                    alert('Status erfolgreich aktualisiert!');
                    closeStatusModal();
                    location.reload(); // Refresh to show updated data
                } else {
                    const error = await response.json();
                    alert('Fehler: ' + error.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten.');
            }
        });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten.');
            }
        }
        
        function updateStatus(ticketId, currentStatus) {
            const statuses = ['open', 'in_progress', 'waiting_customer', 'closed'];
            const statusTexts = {
                'open': 'Offen', 
                'in_progress': 'In Bearbeitung', 
                'waiting_customer': 'Wartet auf Kunde',
                'closed': 'Geschlossen'
            };
            
            const newStatus = prompt(`Status für Ticket #${ticketId} ändern:\n\nVerfügbare Status: ${Object.values(statusTexts).join(', ')}\n\nNeuen Status eingeben:`, currentStatus);
            if (newStatus && statuses.includes(newStatus)) {
                updateTicketStatus(ticketId, newStatus);
            }
        }

        function logout() {
            window.location.href = '/api/logout';
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

        // Bulk Actions and Filtering
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAllTable');
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');
            const count = checkboxes.length;
            
            document.getElementById('selectedCount').textContent = `${count} ausgewählt`;
            document.getElementById('bulkActionBtn').disabled = count === 0;
            
            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.ticket-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllTable');
            
            if (count === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (count === allCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            }
        }

        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
            
            if (!action || selectedTickets.length === 0) {
                alert('Bitte wählen Sie eine Aktion und mindestens ein Ticket aus');
                return;
            }
            
            let confirmMessage = '';
            let statusUpdate = '';
            
            switch (action) {
                case 'mark-solved':
                    confirmMessage = `${selectedTickets.length} Ticket(s) als gelöst markieren?`;
                    statusUpdate = 'closed';
                    break;
                case 'mark-progress':
                    confirmMessage = `${selectedTickets.length} Ticket(s) in Bearbeitung setzen?`;
                    statusUpdate = 'in_progress';
                    break;
                case 'mark-waiting':
                    confirmMessage = `${selectedTickets.length} Ticket(s) auf Kunde warten setzen?`;
                    statusUpdate = 'waiting_customer';
                    break;
                case 'delete':
                    confirmMessage = `${selectedTickets.length} Ticket(s) PERMANENT löschen? Diese Aktion kann nicht rückgängig gemacht werden!`;
                    break;
            }
            
            if (!confirm(confirmMessage)) return;
            
            if (action === 'delete') {
                // Delete tickets
                Promise.all(selectedTickets.map(ticketId => 
                    fetch(`/api/tickets.php?id=${ticketId}`, { method: 'DELETE' })
                        .then(response => response.json())
                ))
                .then(results => {
                    const successful = results.filter(r => r.success).length;
                    alert(`${successful} von ${selectedTickets.length} Tickets gelöscht`);
                    setTimeout(() => location.reload(), 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Fehler beim Löschen der Tickets');
                });
            } else {
                // Update status
                Promise.all(selectedTickets.map(ticketId => 
                    fetch(`/api/tickets.php?id=${ticketId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: statusUpdate })
                    }).then(response => response.json())
                ))
                .then(results => {
                    const successful = results.filter(r => r.success).length;
                    alert(`${successful} von ${selectedTickets.length} Tickets aktualisiert`);
                    setTimeout(() => location.reload(), 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Fehler beim Aktualisieren der Tickets');
                });
            }
        }

        function refreshTickets() {
            location.reload();
        }

        // Filtering functionality
        function initializeFilters() {
            const statusFilter = document.getElementById('statusFilter');
            const priorityFilter = document.getElementById('priorityFilter');
            const searchFilter = document.getElementById('searchFilter');
            
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);
            if (priorityFilter) priorityFilter.addEventListener('change', applyFilters);
            if (searchFilter) searchFilter.addEventListener('input', applyFilters);
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const priorityFilter = document.getElementById('priorityFilter')?.value || '';
            const searchTerm = document.getElementById('searchFilter')?.value.toLowerCase() || '';
            
            const rows = document.querySelectorAll('.ticket-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const status = row.dataset.status;
                const priority = row.dataset.priority;
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const priorityMatch = !priorityFilter || priority === priorityFilter;
                const searchMatch = !searchTerm || text.includes(searchTerm);
                
                const visible = statusMatch && priorityMatch && searchMatch;
                
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });
            
            const filteredCountEl = document.getElementById('filteredCount');
            if (filteredCountEl) {
                filteredCountEl.textContent = `Zeige ${visibleCount} von <?= count($tickets) ?> Tickets`;
            }
            
            // Reset selections when filtering
            document.querySelectorAll('.ticket-checkbox').forEach(cb => cb.checked = false);
            updateSelectedCount();
        }

        // Enhanced Reply Function
        function replyToTicket(ticketId) {
            document.getElementById('replyTicketId').value = ticketId;
            document.getElementById('replyMessage').value = '';
            document.getElementById('markResolved').checked = false;
            document.getElementById('isInternalNote').checked = false;
            document.getElementById('statusAfterReply').value = '';
            
            document.getElementById('replyModal').classList.remove('hidden');
            document.getElementById('replyModal').classList.add('flex');
            
            setTimeout(() => {
                document.getElementById('replyMessage').focus();
            }, 100);
        }

        function closeReplyModal() {
            document.getElementById('replyModal').classList.add('hidden');
            document.getElementById('replyModal').classList.remove('flex');
        }

        // Handle Reply Form Submission
        function handleReplySubmission() {
            const replyForm = document.getElementById('replyForm');
            if (replyForm) {
                replyForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const ticketId = document.getElementById('replyTicketId').value;
                    const message = document.getElementById('replyMessage').value.trim();
                    const markResolved = document.getElementById('markResolved').checked;
                    const isInternal = document.getElementById('isInternalNote').checked;
                    const statusAfterReply = document.getElementById('statusAfterReply').value;
                    
                    if (!message) {
                        alert('Bitte geben Sie eine Antwort ein');
                        return;
                    }
                    
                    try {
                        // Send reply
                        const replyResponse = await fetch('/api/ticket-replies.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                ticket_id: ticketId,
                                message: message,
                                is_internal: isInternal
                            })
                        });
                        
                        const replyResult = await replyResponse.json();
                        
                        if (!replyResult.success) {
                            throw new Error(replyResult.error || 'Fehler beim Senden der Antwort');
                        }
                        
                        // Update status if needed
                        let statusToSet = statusAfterReply;
                        if (markResolved) {
                            statusToSet = 'closed';
                        }
                        
                        if (statusToSet) {
                            const statusResponse = await fetch(`/api/tickets.php?id=${ticketId}`, {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ status: statusToSet })
                            });
                            
                            const statusResult = await statusResponse.json();
                            if (!statusResult.success) {
                                console.warn('Status update failed:', statusResult.error);
                            }
                        }
                        
                        closeReplyModal();
                        showNotification('Antwort erfolgreich gesendet', 'success');
                        setTimeout(() => location.reload(), 1000);
                        
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('Fehler: ' + error.message, 'error');
                    }
                });
            }
        }

        // Quick Actions
        function toggleQuickActions() {
            const panel = document.getElementById('quickActionsPanel');
            panel.classList.toggle('hidden');
            
            const icon = panel.querySelector('i');
            if (panel.classList.contains('hidden')) {
                icon.className = 'fas fa-chevron-left';
            } else {
                icon.className = 'fas fa-chevron-right';
            }
        }

        function createQuickReply(message) {
            const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked'));
            
            if (selectedTickets.length === 0) {
                alert('Bitte wählen Sie mindestens ein Ticket aus');
                return;
            }
            
            if (selectedTickets.length === 1) {
                const ticketId = selectedTickets[0].value;
                replyToTicket(ticketId);
                document.getElementById('replyMessage').value = message;
                return;
            }
            
            // Multiple tickets - batch reply
            if (confirm(`Schnellantwort an ${selectedTickets.length} Tickets senden?`)) {
                Promise.all(selectedTickets.map(checkbox => {
                    const ticketId = checkbox.value;
                    return fetch('/api/ticket-replies.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            ticket_id: ticketId,
                            message: message,
                            is_internal: false
                        })
                    }).then(response => response.json());
                }))
                .then(results => {
                    const successful = results.filter(r => r.success).length;
                    showNotification(`${successful} von ${selectedTickets.length} Antworten gesendet`, 'success');
                    setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Fehler beim Senden der Antworten', 'error');
                });
            }
        }

        function bulkMarkAsResolved() {
            const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
            
            if (selectedTickets.length === 0) {
                alert('Bitte wählen Sie mindestens ein Ticket aus');
                return;
            }
            
            if (confirm(`${selectedTickets.length} Ticket(s) als gelöst markieren?`)) {
                Promise.all(selectedTickets.map(ticketId => 
                    fetch(`/api/tickets.php?id=${ticketId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'closed' })
                    }).then(response => response.json())
                ))
                .then(results => {
                    const successful = results.filter(r => r.success).length;
                    showNotification(`${successful} von ${selectedTickets.length} Tickets als gelöst markiert`, 'success');
                    setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Fehler beim Aktualisieren der Tickets', 'error');
                });
            }
        }

        function bulkAssignToMe() {
            const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
            
            if (selectedTickets.length === 0) {
                alert('Bitte wählen Sie mindestens ein Ticket aus');
                return;
            }
            
            if (confirm(`${selectedTickets.length} Ticket(s) mir zuweisen?`)) {
                Promise.all(selectedTickets.map(ticketId => 
                    fetch(`/api/tickets.php?id=${ticketId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ assigned_to: 2 }) // Admin User ID
                    }).then(response => response.json())
                ))
                .then(results => {
                    const successful = results.filter(r => r.success).length;
                    showNotification(`${successful} von ${selectedTickets.length} Tickets zugewiesen`, 'success');
                    setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Fehler beim Zuweisen der Tickets', 'error');
                });
            }
        }

        // Enhanced Status Modal Functions
        function updateTicketStatus(ticketId, currentStatus) {
            document.getElementById('statusTicketId').value = ticketId;
            document.getElementById('newStatus').value = '';
            document.getElementById('newPriority').value = '';
            document.getElementById('statusNote').value = '';
            document.getElementById('notifyCustomer').checked = true;
            
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusModal').classList.add('flex');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusModal').classList.remove('flex');
        }

        // Handle Status Form Submission
        function handleStatusSubmission() {
            const statusForm = document.getElementById('statusForm');
            if (statusForm) {
                statusForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const ticketId = document.getElementById('statusTicketId').value;
                    const newStatus = document.getElementById('newStatus').value;
                    const newPriority = document.getElementById('newPriority').value;
                    const statusNote = document.getElementById('statusNote').value;
                    const notifyCustomer = document.getElementById('notifyCustomer').checked;
                    
                    if (!newStatus) {
                        alert('Bitte wählen Sie einen Status aus');
                        return;
                    }
                    
                    try {
                        const updateData = { status: newStatus };
                        if (newPriority) {
                            updateData.priority = newPriority;
                        }
                        
                        const response = await fetch(`/api/tickets.php?id=${ticketId}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(updateData)
                        });
                        
                        const result = await response.json();
                        
                        if (!result.success) {
                            throw new Error(result.error || 'Fehler beim Aktualisieren des Status');
                        }
                        
                        // Add internal note if provided
                        if (statusNote.trim()) {
                            await fetch('/api/ticket-replies.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    ticket_id: ticketId,
                                    message: `Status geändert zu: ${newStatus}\nNotiz: ${statusNote}`,
                                    is_internal: true
                                })
                            });
                        }
                        
                        closeStatusModal();
                        showNotification('Status erfolgreich aktualisiert', 'success');
                        setTimeout(() => location.reload(), 1000);
                        
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('Fehler: ' + error.message, 'error');
                    }
                });
            }
        }

        // Enhanced Ticket Management
        function assignTicket(ticketId) {
            document.getElementById('assignTicketId').value = ticketId;
            document.getElementById('assignToUser').value = '';
            document.getElementById('assignmentNote').value = '';
            
            document.getElementById('assignmentModal').classList.remove('hidden');
            document.getElementById('assignmentModal').classList.add('flex');
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').classList.add('hidden');
            document.getElementById('assignmentModal').classList.remove('flex');
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+R for refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                location.reload();
            }
            
            // Ctrl+A for select all
            if (e.ctrlKey && e.key === 'a' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                document.getElementById('selectAllTable').checked = true;
                toggleSelectAll();
            }
            
            // Delete key for bulk delete
            if (e.key === 'Delete' && document.querySelectorAll('.ticket-checkbox:checked').length > 0) {
                document.getElementById('bulkAction').value = 'delete';
                executeBulkAction();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeTicketModal();
                closeReplyModal();
                closeAssignmentModal();
                closeStatusModal();
                
                // Hide quick actions panel
                document.getElementById('quickActionsPanel').classList.add('hidden');
            }
            
            // Q for quick actions
            if (e.key === 'q' && !e.target.matches('input, textarea')) {
                toggleQuickActions();
            }
        });

        // Auto-refresh every 30 seconds
        let autoRefreshInterval;
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                const now = new Date();
                const timeString = now.toLocaleTimeString('de-DE');
                console.log(`Auto-refresh at ${timeString}`);
                location.reload();
            }, 30000);
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }

        // Search enhancement
        function enhancedSearch() {
            const searchTerm = document.getElementById('searchFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.ticket-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const ticketId = row.dataset.ticketId;
                
                // Search in ticket ID, subject, customer name, email
                const visible = text.includes(searchTerm) || ticketId.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
                
                // Highlight search terms
                if (searchTerm && visible) {
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        if (!cell.querySelector('input, button')) {
                            highlightSearchTerm(cell, searchTerm);
                        }
                    });
                }
            });
        }

        function highlightSearchTerm(element, term) {
            if (!term) return;
            
            const regex = new RegExp(`(${term})`, 'gi');
            const originalText = element.textContent;
            
            if (originalText.toLowerCase().includes(term)) {
                element.innerHTML = originalText.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800">$1</mark>');
            }
        }

        // Initialize everything
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(savedTheme);
            updateThemeIcon();
            
            // Add event listener to theme toggle button
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
            
            // Initialize filters
            initializeFilters();
            updateSelectedCount();
            
            // Start auto-refresh
            startAutoRefresh();
            
            // Add enhanced search
            const searchInput = document.getElementById('searchFilter');
            if (searchInput) {
                searchInput.addEventListener('input', enhancedSearch);
            }
            
            // Add quick actions button
            const quickActionsBtn = document.createElement('button');
            quickActionsBtn.innerHTML = '<i class="fas fa-lightning-bolt mr-1"></i>Schnellaktionen';
            quickActionsBtn.className = 'px-4 py-2 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded-lg';
            quickActionsBtn.onclick = toggleQuickActions;
            
            const actionsContainer = document.querySelector('.flex.space-x-2');
            if (actionsContainer) {
                actionsContainer.appendChild(quickActionsBtn);
            }

            // Initialize form handlers
            handleReplySubmission();
            handleStatusSubmission();

            // Fix selectAll functionality
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.ticket-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAll.checked;
                    });
                    updateSelectedCount();
                });
            }

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    const selectAll = document.getElementById('selectAll');
                    if (selectAll) {
                        selectAll.checked = true;
                        selectAll.dispatchEvent(new Event('change'));
                    }
                }
                
                if (e.key === 'Escape') {
                    closeViewModal();
                    closeReplyModal();
                    closeStatusModal();
                }
                
                if (e.key === 'Delete') {
                    const selected = document.querySelectorAll('.ticket-checkbox:checked');
                    if (selected.length > 0) {
                        bulkAction('delete');
                    }
                }
                
                if (e.key === 'q' || e.key === 'Q') {
                    toggleQuickActions();
                }
            });
        });
    </script>
</div>

<?php renderFooter(); ?>
</body>
</html>