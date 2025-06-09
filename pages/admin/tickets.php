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
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">SpectraHost Admin</h1>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="/admin/dashboard" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="/admin/tickets" class="text-blue-600 dark:text-blue-400 px-3 py-2 text-sm font-medium border-b-2 border-blue-600">Tickets</a>
                        <a href="/admin/users" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Benutzer</a>
                        <a href="/admin/services" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Services</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-moon text-gray-600 dark:text-gray-300"></i>
                    </button>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </div>
                    <button onclick="logout()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                        Abmelden
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Support-Tickets</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Verwalten Sie Kundenanfragen und Support-Tickets</p>
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <input type="text" id="searchFilter" placeholder="Tickets durchsuchen..." 
                           class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Status</option>
                        <option value="open">Offen</option>
                        <option value="in_progress">In Bearbeitung</option>
                        <option value="waiting_customer">Wartet auf Kunde</option>
                        <option value="closed">Geschlossen</option>
                    </select>
                    
                    <select id="priorityFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Prioritäten</option>
                        <option value="critical">Kritisch</option>
                        <option value="high">Hoch</option>
                        <option value="medium">Mittel</option>
                        <option value="low">Niedrig</option>
                    </select>
                </div>
                
                <div class="flex space-x-2">
                    <span id="selectedCount" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">0 ausgewählt</span>
                    <button id="bulkActionBtn" onclick="showBulkActions()" disabled 
                            class="px-4 py-2 text-sm bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg">
                        Bulk-Aktionen
                    </button>
                    <button onclick="refreshTickets()" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <i class="fas fa-sync-alt mr-1"></i>Aktualisieren
                    </button>
                </div>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kunde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Betreff</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priorität</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Antworten</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Erstellt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="ticketsTableBody">
                        <?php foreach ($tickets as $ticket): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 ticket-row" data-ticket-id="<?php echo $ticket['id']; ?>">
                            <td class="px-6 py-4">
                                <input type="checkbox" value="<?php echo $ticket['id']; ?>" 
                                       class="ticket-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                       onchange="updateSelectedCount()">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                #<?php echo $ticket['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($ticket['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars(substr($ticket['message'], 0, 100)) . (strlen($ticket['message']) > 100 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'open' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'waiting_customer' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                                ];
                                $statusTexts = [
                                    'open' => 'Offen',
                                    'in_progress' => 'In Bearbeitung',
                                    'waiting_customer' => 'Wartet auf Kunde',
                                    'closed' => 'Geschlossen'
                                ];
                                $colorClass = $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                $statusText = $statusTexts[$ticket['status']] ?? ucfirst($ticket['status']);
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $priorityColors = [
                                    'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                ];
                                $priorityTexts = [
                                    'critical' => 'Kritisch',
                                    'high' => 'Hoch',
                                    'medium' => 'Mittel',
                                    'low' => 'Niedrig'
                                ];
                                $priorityColor = $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800';
                                $priorityText = $priorityTexts[$ticket['priority']] ?? ucfirst($ticket['priority']);
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $priorityColor; ?>">
                                    <?php echo $priorityText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo $ticket['reply_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewTicket(<?php echo $ticket['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="replyToTicket(<?php echo $ticket['id']; ?>)" 
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, '<?php echo $ticket['status']; ?>')" 
                                            class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Ticket Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="viewTicketTitle" class="text-lg font-medium text-gray-900 dark:text-white">Ticket Details</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="viewTicketContent" class="space-y-4">
                    <!-- Content will be loaded here -->
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="closeViewModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full mx-4">
            <form id="replyForm" onsubmit="handleReplySubmit(event)">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Ticket beantworten</h3>
                        <button type="button" onclick="closeReplyModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <input type="hidden" id="replyTicketId" name="ticket_id">
                    
                    <div class="mb-4">
                        <label for="replyMessage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Antwort</label>
                        <textarea id="replyMessage" name="message" rows="6" required
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Ihre Antwort..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="isInternalNote" name="is_internal" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Interne Notiz</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="markResolved" name="mark_resolved" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Als gelöst markieren</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="statusAfterReply" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status nach Antwort</label>
                        <select id="statusAfterReply" name="status_after_reply" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Unverändert</option>
                            <option value="waiting_customer">Wartet auf Kunde</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                    <button type="button" onclick="closeReplyModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Antwort senden
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4">
            <form id="statusForm" onsubmit="handleStatusSubmit(event)">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Status ändern</h3>
                        <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <input type="hidden" id="statusTicketId" name="ticket_id">
                    
                    <div class="mb-4">
                        <label for="newStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Neuer Status</label>
                        <select id="newStatus" name="status" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Status auswählen</option>
                            <option value="open">Offen</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="waiting_customer">Wartet auf Kunde</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="newPriority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priorität</label>
                        <select id="newPriority" name="priority" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Unverändert</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="critical">Kritisch</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="statusNote" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Interne Notiz (optional)</label>
                        <textarea id="statusNote" name="note" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Grund für Statusänderung..."></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Status aktualisieren
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div id="bulkActionsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Bulk-Aktionen</h3>
                    <button onclick="closeBulkActionsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <button onclick="bulkUpdateStatus('closed')" class="w-full px-4 py-2 text-left text-sm bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-800 dark:text-green-200 rounded-lg">
                        <i class="fas fa-check mr-2"></i>Als gelöst markieren
                    </button>
                    <button onclick="bulkUpdateStatus('in_progress')" class="w-full px-4 py-2 text-left text-sm bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-lg">
                        <i class="fas fa-clock mr-2"></i>In Bearbeitung
                    </button>
                    <button onclick="bulkAssignToMe()" class="w-full px-4 py-2 text-left text-sm bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:hover:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded-lg">
                        <i class="fas fa-user mr-2"></i>Mir zuweisen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50">
        <div class="max-w-4xl max-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                    <h3 id="imageModalTitle" class="text-lg font-medium text-gray-900 dark:text-white">Bild anzeigen</h3>
                    <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <img id="imageModalImg" src="" alt="" class="max-w-full max-h-96 mx-auto">
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4">
            <form id="assignmentForm" onsubmit="handleAssignmentSubmit(event)">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Ticket zuweisen</h3>
                        <button type="button" onclick="closeAssignmentModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <input type="hidden" id="assignTicketId" name="ticket_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zuweisung</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="assignment" value="self" checked class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Mir zuweisen</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="assignment" value="unassign" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Zuweisung entfernen</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="assignmentNote" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notiz (optional)</label>
                        <textarea id="assignmentNote" name="note" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Grund für Zuweisung..."></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignmentModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Zuweisen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Global Variables
let selectedTickets = [];

// Core Functions
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.ticket-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.ticket-checkbox:checked');
    const count = checkboxes.length;
    
    document.getElementById('selectedCount').textContent = `${count} ausgewählt`;
    document.getElementById('bulkActionBtn').disabled = count === 0;
    
    selectedTickets = Array.from(checkboxes).map(cb => cb.value);
}

// Modal Functions
function viewTicket(ticketId) {
    fetch(`/api/tickets.php?id=${ticketId}&include_replies=true`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.ticket;
                document.getElementById('viewTicketTitle').textContent = `#${ticket.id} - ${ticket.subject}`;
                
                let content = `
                    <div class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-medium text-gray-900 dark:text-white">${ticket.customer_name}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">${new Date(ticket.created_at).toLocaleString('de-DE')}</span>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${ticket.message}</p>
                        </div>
                `;
                
                if (data.replies && data.replies.length > 0) {
                    data.replies.forEach(reply => {
                        const bgColor = reply.is_internal ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
                        content += `
                            <div class="${bgColor} border p-4 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-medium text-gray-900 dark:text-white">${reply.author_name}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">${new Date(reply.created_at).toLocaleString('de-DE')}</span>
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${reply.message}</p>
                                ${reply.is_internal ? '<span class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Interne Notiz</span>' : ''}
                            </div>
                        `;
                    });
                }
                
                content += '</div>';
                document.getElementById('viewTicketContent').innerHTML = content;
                document.getElementById('viewModal').classList.remove('hidden');
                document.getElementById('viewModal').classList.add('flex');
            } else {
                showNotification('Fehler beim Laden des Tickets: ' + (data.error || 'Unbekannter Fehler'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Fehler beim Laden des Tickets', 'error');
        });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    document.getElementById('viewModal').classList.remove('flex');
}

function replyToTicket(ticketId) {
    document.getElementById('replyTicketId').value = ticketId;
    document.getElementById('replyMessage').value = '';
    document.getElementById('isInternalNote').checked = false;
    document.getElementById('markResolved').checked = false;
    document.getElementById('statusAfterReply').value = '';
    
    document.getElementById('replyModal').classList.remove('hidden');
    document.getElementById('replyModal').classList.add('flex');
}

function closeReplyModal() {
    document.getElementById('replyModal').classList.add('hidden');
    document.getElementById('replyModal').classList.remove('flex');
}

function updateTicketStatus(ticketId, currentStatus) {
    document.getElementById('statusTicketId').value = ticketId;
    document.getElementById('newStatus').value = currentStatus;
    document.getElementById('newPriority').value = '';
    document.getElementById('statusNote').value = '';
    
    document.getElementById('statusModal').classList.remove('hidden');
    document.getElementById('statusModal').classList.add('flex');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    document.getElementById('statusModal').classList.remove('flex');
}

function showBulkActions() {
    document.getElementById('bulkActionsModal').classList.remove('hidden');
    document.getElementById('bulkActionsModal').classList.add('flex');
}

function closeBulkActionsModal() {
    document.getElementById('bulkActionsModal').classList.add('hidden');
    document.getElementById('bulkActionsModal').classList.remove('flex');
}

// Form Handlers
function handleReplySubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        ticket_id: formData.get('ticket_id'),
        message: formData.get('message'),
        is_internal: formData.get('is_internal') ? 1 : 0
    };
    
    fetch('/api/ticket-replies.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Update status if needed
            const statusAfterReply = formData.get('status_after_reply');
            const markResolved = formData.get('mark_resolved');
            
            let statusToSet = statusAfterReply;
            if (markResolved) {
                statusToSet = 'closed';
            }
            
            if (statusToSet) {
                return fetch(`/api/tickets.php?id=${data.ticket_id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ status: statusToSet })
                });
            }
            
            return Promise.resolve({ ok: true, json: () => ({ success: true }) });
        } else {
            throw new Error(result.error || 'Fehler beim Senden der Antwort');
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }
        throw new Error('Fehler beim Aktualisieren des Status');
    })
    .then(() => {
        closeReplyModal();
        showNotification('Antwort erfolgreich gesendet', 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Fehler: ' + error.message, 'error');
    });
}

function handleStatusSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const ticketId = formData.get('ticket_id');
    const data = {
        status: formData.get('status'),
        priority: formData.get('priority') || undefined
    };
    
    // Remove undefined values
    Object.keys(data).forEach(key => data[key] === undefined && delete data[key]);
    
    fetch(`/api/tickets.php?id=${ticketId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Add internal note if provided
            const note = formData.get('note');
            if (note && note.trim()) {
                return fetch('/api/ticket-replies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message: `Status geändert zu: ${data.status}${data.priority ? `, Priorität: ${data.priority}` : ''}\nNotiz: ${note}`,
                        is_internal: 1
                    })
                });
            }
            
            return Promise.resolve({ ok: true, json: () => ({ success: true }) });
        } else {
            throw new Error(result.error || 'Fehler beim Aktualisieren des Status');
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }
        throw new Error('Fehler beim Hinzufügen der Notiz');
    })
    .then(() => {
        closeStatusModal();
        showNotification('Status erfolgreich aktualisiert', 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Fehler: ' + error.message, 'error');
    });
}

// Bulk Actions
function bulkUpdateStatus(status) {
    if (selectedTickets.length === 0) {
        showNotification('Keine Tickets ausgewählt', 'error');
        return;
    }
    
    const statusTexts = {
        'closed': 'gelöst',
        'in_progress': 'in Bearbeitung',
        'open': 'offen',
        'waiting_customer': 'wartend auf Kunde'
    };
    
    if (confirm(`${selectedTickets.length} Ticket(s) als "${statusTexts[status]}" markieren?`)) {
        Promise.all(selectedTickets.map(ticketId => 
            fetch(`/api/tickets.php?id=${ticketId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status })
            }).then(response => response.json())
        ))
        .then(results => {
            const successful = results.filter(r => r.success).length;
            closeBulkActionsModal();
            showNotification(`${successful} von ${selectedTickets.length} Tickets aktualisiert`, 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Fehler beim Aktualisieren der Tickets', 'error');
        });
    }
}

function bulkAssignToMe() {
    if (selectedTickets.length === 0) {
        showNotification('Keine Tickets ausgewählt', 'error');
        return;
    }
    
    if (confirm(`${selectedTickets.length} Ticket(s) mir zuweisen?`)) {
        Promise.all(selectedTickets.map(ticketId => 
            fetch(`/api/tickets.php?id=${ticketId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assigned_to: <?php echo $user['id']; ?> })
            }).then(response => response.json())
        ))
        .then(results => {
            const successful = results.filter(r => r.success).length;
            closeBulkActionsModal();
            showNotification(`${successful} von ${selectedTickets.length} Tickets zugewiesen`, 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Fehler beim Zuweisen der Tickets', 'error');
        });
    }
}

// Utility Functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 4000);
}

function refreshTickets() {
    location.reload();
}

function logout() {
    fetch('/api/logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/';
        } else {
            // Fallback: redirect directly
            window.location.href = '/api/logout.php';
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Fallback: redirect directly
        window.location.href = '/api/logout.php';
    });
}

// Theme Functions
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
        : '<i class="fas fa-moon text-gray-600 dark:text-gray-300"></i>';
}

// Filter Functions
function applyFilters() {
    const searchTerm = document.getElementById('searchFilter').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const priorityFilter = document.getElementById('priorityFilter').value;
    
    const rows = document.querySelectorAll('.ticket-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const ticketStatus = row.querySelector('td:nth-child(5) span').textContent.toLowerCase();
        const ticketPriority = row.querySelector('td:nth-child(6) span').textContent.toLowerCase();
        
        const matchesSearch = searchTerm === '' || text.includes(searchTerm);
        const matchesStatus = statusFilter === '' || ticketStatus.includes(statusFilter);
        const matchesPriority = priorityFilter === '' || ticketPriority.includes(priorityFilter);
        
        if (matchesSearch && matchesStatus && matchesPriority) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Image Modal Functions
function showImageModal(imagePath, filename) {
    document.getElementById('imageModalTitle').textContent = filename;
    document.getElementById('imageModalImg').src = imagePath;
    document.getElementById('imageModalImg').alt = filename;
    document.getElementById('imageModal').classList.remove('hidden');
    document.getElementById('imageModal').classList.add('flex');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.getElementById('imageModal').classList.remove('flex');
}

// Assignment Functions
function assignTicket(ticketId) {
    document.getElementById('assignTicketId').value = ticketId;
    document.getElementById('assignmentNote').value = '';
    document.querySelector('input[name="assignment"][value="self"]').checked = true;
    
    document.getElementById('assignmentModal').classList.remove('hidden');
    document.getElementById('assignmentModal').classList.add('flex');
}

function closeAssignmentModal() {
    document.getElementById('assignmentModal').classList.add('hidden');
    document.getElementById('assignmentModal').classList.remove('flex');
}

function handleAssignmentSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const ticketId = formData.get('ticket_id');
    const assignment = formData.get('assignment');
    const note = formData.get('note');
    
    const assignedTo = assignment === 'self' ? <?php echo $user['id']; ?> : null;
    
    fetch(`/api/tickets.php?id=${ticketId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ assigned_to: assignedTo })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Add internal note if provided
            if (note && note.trim()) {
                const actionText = assignment === 'self' ? 'zugewiesen' : 'Zuweisung entfernt';
                return fetch('/api/ticket-replies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message: `Ticket ${actionText}\nNotiz: ${note}`,
                        is_internal: 1
                    })
                });
            }
            
            return Promise.resolve({ ok: true, json: () => ({ success: true }) });
        } else {
            throw new Error(result.error || 'Fehler beim Zuweisen des Tickets');
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }
        throw new Error('Fehler beim Hinzufügen der Notiz');
    })
    .then(() => {
        closeAssignmentModal();
        showNotification('Ticket erfolgreich zugewiesen', 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Fehler: ' + error.message, 'error');
    });
}

// Enhanced viewTicket function with attachments and actions
function viewTicketEnhanced(ticketId) {
    fetch(`/api/tickets.php?id=${ticketId}&include_replies=true`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.ticket;
                document.getElementById('viewTicketTitle').textContent = `#${ticket.id} - ${ticket.subject}`;
                
                let content = `
                    <div class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-medium text-gray-900 dark:text-white">${ticket.customer_name}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">${new Date(ticket.created_at).toLocaleString('de-DE')}</span>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${ticket.message}</p>
                        </div>
                `;
                
                if (data.replies && data.replies.length > 0) {
                    data.replies.forEach(reply => {
                        const bgColor = reply.is_internal ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
                        content += `
                            <div class="${bgColor} border p-4 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-medium text-gray-900 dark:text-white">${reply.author_name}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">${new Date(reply.created_at).toLocaleString('de-DE')}</span>
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${reply.message}</p>
                                ${reply.is_internal ? '<span class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Interne Notiz</span>' : ''}
                            </div>
                        `;
                    });
                }
                
                // Add attachments if any
                if (data.attachments && data.attachments.length > 0) {
                    content += `
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Anhänge</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    `;
                    
                    data.attachments.forEach(attachment => {
                        if (attachment.mime_type && attachment.mime_type.startsWith('image/')) {
                            content += `
                                <div class="relative">
                                    <img src="${attachment.file_path}" alt="${attachment.original_filename}" 
                                         class="w-full h-24 object-cover rounded-lg cursor-pointer"
                                         onclick="showImageModal('${attachment.file_path}', '${attachment.original_filename}')">
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg truncate">
                                        ${attachment.original_filename}
                                    </div>
                                </div>
                            `;
                        } else {
                            content += `
                                <a href="${attachment.file_path}" download="${attachment.original_filename}"
                                   class="flex items-center space-x-2 p-2 bg-white dark:bg-gray-600 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-500">
                                    <i class="fas fa-file text-gray-500"></i>
                                    <span class="text-sm truncate">${attachment.original_filename}</span>
                                </a>
                            `;
                        }
                    });
                    
                    content += `</div></div>`;
                }
                
                content += '</div>';
                document.getElementById('viewTicketContent').innerHTML = content;
                
                // Update modal footer with action buttons
                const modalFooter = document.querySelector('#viewModal .flex.justify-end');
                modalFooter.innerHTML = `
                    <button onclick="assignTicket(${ticket.id})" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 mr-2">
                        <i class="fas fa-user-plus mr-1"></i>Zuweisen
                    </button>
                    <button onclick="replyToTicket(${ticket.id}); closeViewModal();" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 mr-2">
                        <i class="fas fa-reply mr-1"></i>Antworten
                    </button>
                    <button onclick="updateTicketStatus(${ticket.id}, '${ticket.status}'); closeViewModal();" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 mr-2">
                        <i class="fas fa-edit mr-1"></i>Status ändern
                    </button>
                    <button onclick="closeViewModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Schließen
                    </button>
                `;
                
                document.getElementById('viewModal').classList.remove('hidden');
                document.getElementById('viewModal').classList.add('flex');
            } else {
                showNotification('Fehler beim Laden des Tickets: ' + (data.error || 'Unbekannter Fehler'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Fehler beim Laden des Tickets', 'error');
        });
}

// Override the original viewTicket function
function viewTicket(ticketId) {
    viewTicketEnhanced(ticketId);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Theme initialization
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.classList.add(savedTheme);
    updateThemeIcon();
    
    // Event listeners
    document.getElementById('theme-toggle').addEventListener('click', toggleTheme);
    document.getElementById('searchFilter').addEventListener('input', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('priorityFilter').addEventListener('change', applyFilters);
    
    // Update selected count on load
    updateSelectedCount();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeViewModal();
            closeReplyModal();
            closeStatusModal();
            closeBulkActionsModal();
            closeImageModal();
            closeAssignmentModal();
        }
    });
});
</script>

<?php renderFooter(); ?>
</body>
</html>