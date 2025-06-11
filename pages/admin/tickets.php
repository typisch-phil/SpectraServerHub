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

<!-- Ticket Detail Modal -->
<div id="ticketModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Ticket Details</h3>
                <button onclick="closeTicketModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="ticketContent" class="p-6 overflow-y-auto max-h-[70vh]">
                <!-- Ticket content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-2xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Antwort senden</h3>
                <button onclick="closeReplyModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="replyForm">
                    <input type="hidden" id="replyTicketId" value="">
                    <div class="mb-4">
                        <label class="block text-gray-300 text-sm font-medium mb-2">Nachricht</label>
                        <textarea id="replyMessage" rows="6" class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500" placeholder="Ihre Antwort..." required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 text-sm font-medium mb-2">Status ändern (optional)</label>
                        <select id="replyStatus" class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                            <option value="waiting_customer">Warten auf Kunde (Standard)</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="resolved">Gelöst</option>
                            <option value="closed">Geschlossen</option>
                            <option value="">Status nicht ändern</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeReplyModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Abbrechen</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-paper-plane mr-2"></i>Senden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Status ändern</h3>
                <button onclick="closeStatusModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="statusForm">
                    <input type="hidden" id="statusTicketId" value="">
                    <div class="mb-4">
                        <label class="block text-gray-300 text-sm font-medium mb-2">Neuer Status</label>
                        <select id="newStatus" class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500" required>
                            <option value="">Status wählen</option>
                            <option value="open">Offen</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="waiting_customer">Warten auf Kunde</option>
                            <option value="resolved">Gelöst</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 text-sm font-medium mb-2">Priorität</label>
                        <select id="newPriority" class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                            <option value="">Priorität nicht ändern</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Abbrechen</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function viewTicket(ticketId) {
    try {
        const response = await fetch(`/api/admin/tickets.php?id=${ticketId}`);
        const ticket = await response.json();
        
        if (!response.ok) {
            throw new Error(ticket.error || 'Fehler beim Laden des Tickets');
        }
        
        const modalContent = document.getElementById('ticketContent');
        const statusLabels = {
            'open': 'Offen',
            'in_progress': 'In Bearbeitung',
            'waiting_customer': 'Warten auf Kunde',
            'resolved': 'Gelöst',
            'closed': 'Geschlossen'
        };
        
        const priorityLabels = {
            'urgent': 'Urgent',
            'high': 'Hoch',
            'medium': 'Mittel',
            'low': 'Niedrig'
        };
        
        modalContent.innerHTML = `
            <div class="mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h4 class="text-2xl font-bold text-white mb-2">#${ticket.id} - ${ticket.subject}</h4>
                        <p class="text-gray-300">Von: ${ticket.user_name} (${ticket.user_email})</p>
                        <p class="text-gray-400 text-sm">Erstellt: ${new Date(ticket.created_at).toLocaleString('de-DE')}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-blue-900 text-blue-200 mb-2">
                            ${statusLabels[ticket.status]}
                        </span>
                        <br>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-yellow-900 text-yellow-200">
                            ${priorityLabels[ticket.priority]}
                        </span>
                    </div>
                </div>
                <div class="bg-gray-700 rounded-lg p-4 mb-6">
                    <p class="text-gray-300">${ticket.description}</p>
                </div>
            </div>
            
            <div class="border-t border-gray-600 pt-6">
                <h5 class="text-lg font-bold text-white mb-4">Nachrichten (${ticket.messages.length})</h5>
                <div class="space-y-4 max-h-64 overflow-y-auto">
                    ${ticket.messages.map(message => `
                        <div class="bg-${message.is_admin_reply ? 'blue-900/30 border-blue-700' : 'gray-700'} rounded-lg p-4 border">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-medium text-white">
                                    ${message.author_name} ${message.is_admin_reply ? '(Admin)' : ''}
                                </span>
                                <span class="text-gray-400 text-sm">
                                    ${new Date(message.created_at).toLocaleString('de-DE')}
                                </span>
                            </div>
                            <p class="text-gray-300">${message.message}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <div class="border-t border-gray-600 pt-4 mt-6">
                <div class="flex space-x-3">
                    <button onclick="replyTicket(${ticket.id})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-reply mr-2"></i>Antworten
                    </button>
                    <button onclick="changeStatus(${ticket.id})" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                        <i class="fas fa-edit mr-2"></i>Status ändern
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById('ticketModal').classList.remove('hidden');
    } catch (error) {
        alert('Fehler beim Laden des Tickets: ' + error.message);
    }
}

function closeTicketModal() {
    document.getElementById('ticketModal').classList.add('hidden');
}

function replyTicket(ticketId) {
    document.getElementById('replyTicketId').value = ticketId;
    document.getElementById('replyMessage').value = '';
    document.getElementById('replyStatus').value = 'waiting_customer';
    document.getElementById('replyModal').classList.remove('hidden');
    closeTicketModal();
}

function closeReplyModal() {
    document.getElementById('replyModal').classList.add('hidden');
}

function changeStatus(ticketId) {
    document.getElementById('statusTicketId').value = ticketId;
    document.getElementById('newStatus').value = '';
    document.getElementById('newPriority').value = '';
    document.getElementById('statusModal').classList.remove('hidden');
    closeTicketModal();
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

// Reply Form Handler
document.getElementById('replyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const ticketId = document.getElementById('replyTicketId').value;
    const message = document.getElementById('replyMessage').value;
    const status = document.getElementById('replyStatus').value;
    
    const data = {
        ticket_id: parseInt(ticketId),
        message: message
    };
    
    if (status) {
        data.status = status;
    }
    
    try {
        const response = await fetch('/api/admin/tickets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Fehler beim Senden der Antwort');
        }
        
        alert('Antwort erfolgreich gesendet!');
        closeReplyModal();
        location.reload(); // Seite neu laden um Updates zu zeigen
    } catch (error) {
        alert('Fehler beim Senden der Antwort: ' + error.message);
    }
});

// Status Form Handler
document.getElementById('statusForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const ticketId = document.getElementById('statusTicketId').value;
    const status = document.getElementById('newStatus').value;
    const priority = document.getElementById('newPriority').value;
    
    const data = {
        id: parseInt(ticketId),
        status: status
    };
    
    if (priority) {
        data.priority = priority;
    }
    
    try {
        const response = await fetch('/api/admin/tickets.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Fehler beim Ändern des Status');
        }
        
        alert('Status erfolgreich geändert!');
        closeStatusModal();
        location.reload(); // Seite neu laden um Updates zu zeigen
    } catch (error) {
        alert('Fehler beim Ändern des Status: ' + error.message);
    }
});

// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.querySelector('select[class*="bg-gray-700"]:first-of-type');
    const priorityFilter = document.querySelector('select[class*="bg-gray-700"]:last-of-type');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('status', this.value);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        });
    }
    
    if (priorityFilter) {
        priorityFilter.addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('priority', this.value);
            } else {
                url.searchParams.delete('priority');
            }
            window.location.href = url.toString();
        });
    }
});
</script>

<?php
renderFooter();
?>