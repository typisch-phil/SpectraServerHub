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
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Support-Tickets</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Betreff</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kunde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priorität</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Erstellt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
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
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'closed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                    ];
                                    $statusTexts = [
                                        'open' => 'Offen',
                                        'pending' => 'In Bearbeitung',
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
                                        'normal' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    ];
                                    $priorityTexts = [
                                        'low' => 'Niedrig',
                                        'normal' => 'Normal',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' ?>">
                                        <?= $priorityTexts[$ticket['priority']] ?? ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewTicket(<?= $ticket['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3" title="Ticket anzeigen">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="replyToTicket(<?= $ticket['id'] ?>)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3" title="Antworten">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button onclick="updateTicketStatus(<?= $ticket['id'] ?>, '<?= $ticket['status'] ?>')" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Status ändern">
                                        <i class="fas fa-edit"></i>
                                    </button>
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
</div>

<?php renderFooter(); ?>
</body>
</html>