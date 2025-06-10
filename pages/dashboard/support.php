<?php
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/dashboard-layout.php';

// Benutzer-Authentifizierung prüfen
if (!isLoggedIn()) {
    header("Location: /login");
    exit;
}

$user = getCurrentUser();
if (!$user) {
    header("Location: /login");
    exit;
}

// Datenbankverbindung verwenden
global $db;
$user_tickets = [];
$ticket_stats = [];
$user_services = [];

if ($db) {
    try {
        // Benutzer-Tickets abrufen
        $stmt = $db->prepare("
            SELECT t.*, 
                   (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id) as message_count,
                   (SELECT tm.created_at FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) as last_activity
            FROM support_tickets t 
            WHERE t.user_id = ? 
            ORDER BY t.created_at DESC
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $user_tickets[] = $row;
        }
        $stmt->close();

        // Ticket-Statistiken
        $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM support_tickets WHERE user_id = ? GROUP BY status");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $ticket_stats[$row['status']] = $row['count'];
        }
        $stmt->close();

        // Benutzer-Services für Ticket-Erstellung
        $stmt = $db->prepare("
            SELECT id, name
            FROM services 
            WHERE user_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $user_services[] = $row;
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Support page error: " . $e->getMessage());
        $user_tickets = [];
        $ticket_stats = [];
        $user_services = [];
    }
}

// Standard-Werte für Statistiken
$total_tickets = array_sum($ticket_stats);
$open_tickets = $ticket_stats['open'] ?? 0;
$closed_tickets = $ticket_stats['closed'] ?? 0;
$resolved_tickets = $ticket_stats['resolved'] ?? 0;

startDashboardPage('Support', 'support');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Support</h1>
            <p class="text-gray-400 mt-1">Verwalten Sie Ihre Support-Tickets und erhalten Sie Hilfe</p>
        </div>
        <button onclick="showCreateTicketModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            <i class="fas fa-plus mr-2"></i>Neues Ticket
        </button>
    </div>

    <!-- Statistiken -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-ticket-alt text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Gesamt</p>
                    <p class="text-xl font-semibold text-white"><?php echo $total_tickets; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-green-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-clock text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Offen</p>
                    <p class="text-xl font-semibold text-white"><?php echo $open_tickets; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-check text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Gelöst</p>
                    <p class="text-xl font-semibold text-white"><?php echo $resolved_tickets; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-gray-700 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-archive text-gray-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Geschlossen</p>
                    <p class="text-xl font-semibold text-white"><?php echo $closed_tickets; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Hauptbereich -->
        <div class="lg:col-span-2">
            <!-- Tickets-Liste -->
            <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white">Meine Tickets</h3>
                    <div class="flex items-center space-x-2">
                        <select class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg px-3 py-1">
                            <option value="all">Alle</option>
                            <option value="open">Offen</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="resolved">Gelöst</option>
                            <option value="closed">Geschlossen</option>
                        </select>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-700">
                    <?php if (empty($user_tickets)): ?>
                        <div class="p-8 text-center">
                            <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-ticket-alt text-gray-400 text-xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-white mb-2">Keine Tickets vorhanden</h4>
                            <p class="text-gray-400 mb-4">Sie haben noch keine Support-Tickets erstellt.</p>
                            <button onclick="showCreateTicketModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                Erstes Ticket erstellen
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_tickets as $ticket): ?>
                            <div class="p-6 hover:bg-gray-750 cursor-pointer transition-colors" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($ticket['subject'] ?? 'Ohne Betreff'); ?></h4>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                <?php 
                                                switch($ticket['status'] ?? 'open') {
                                                    case 'open': echo 'bg-green-900 text-green-400'; break;
                                                    case 'in_progress': echo 'bg-blue-900 text-blue-400'; break;
                                                    case 'waiting_customer': echo 'bg-yellow-900 text-yellow-400'; break;
                                                    case 'resolved': echo 'bg-purple-900 text-purple-400'; break;
                                                    case 'closed': echo 'bg-gray-700 text-gray-300'; break;
                                                    default: echo 'bg-blue-900 text-blue-400';
                                                }
                                                ?>">
                                                <?php 
                                                $status_labels = [
                                                    'open' => 'Offen',
                                                    'in_progress' => 'In Bearbeitung',
                                                    'waiting_customer' => 'Wartet auf Kunden',
                                                    'resolved' => 'Gelöst',
                                                    'closed' => 'Geschlossen'
                                                ];
                                                echo $status_labels[$ticket['status'] ?? 'open'] ?? ucfirst($ticket['status'] ?? 'open');
                                                ?>
                                            </span>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                <?php 
                                                switch($ticket['priority'] ?? 'medium') {
                                                    case 'urgent': echo 'bg-red-900 text-red-400'; break;
                                                    case 'high': echo 'bg-orange-900 text-orange-400'; break;
                                                    case 'medium': echo 'bg-yellow-900 text-yellow-400'; break;
                                                    case 'low': echo 'bg-green-900 text-green-400'; break;
                                                    default: echo 'bg-gray-700 text-gray-300';
                                                }
                                                ?>">
                                                <?php echo ucfirst($ticket['priority'] ?? 'medium'); ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-400 mb-2"><?php echo nl2br(htmlspecialchars(substr($ticket['description'] ?? '', 0, 200))); ?><?php echo strlen($ticket['description'] ?? '') > 200 ? '...' : ''; ?></p>
                                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'] ?? 'now')); ?></span>
                                            <span><i class="fas fa-comments mr-1"></i><?php echo $ticket['message_count'] ?? 0; ?> Nachrichten</span>
                                            <span><i class="fas fa-tag mr-1"></i><?php echo ucfirst($ticket['category'] ?? 'Allgemein'); ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <button class="text-blue-400 hover:text-blue-300">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Seitenleiste -->
        <div class="space-y-6">
            <!-- Schnellaktionen -->
            <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Schnellaktionen</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button onclick="showCreateTicketModal()" class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left transition-colors">
                            <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-plus text-blue-400"></i>
                            </div>
                            <span class="text-sm font-medium text-white">Neues Ticket erstellen</span>
                        </button>
                        <a href="/kb" class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left transition-colors">
                            <div class="w-8 h-8 bg-green-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-book text-green-400"></i>
                            </div>
                            <span class="text-sm font-medium text-white">Wissensdatenbank</span>
                        </a>
                        <div class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left">
                            <div class="w-8 h-8 bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-headset text-purple-400"></i>
                            </div>
                            <span class="text-sm font-medium text-white">Live-Chat</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support-Informationen -->
            <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Support-Kontakt</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-white mb-1">E-Mail Support</p>
                            <p class="text-sm text-gray-400">support@spectrahost.de</p>
                            <p class="text-xs text-gray-500 mt-1">Antwortzeit: 2-4 Stunden</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white mb-1">Telefon Support</p>
                            <p class="text-sm text-gray-400">+49 123 456 789</p>
                            <p class="text-xs text-gray-500 mt-1">Mo-Fr: 9:00 - 18:00 Uhr</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white mb-1">Notfall-Hotline</p>
                            <p class="text-sm text-gray-400">+49 123 456 790</p>
                            <p class="text-xs text-gray-500 mt-1">24/7 verfügbar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ticket-Erstellungs-Modal -->
<div id="createTicketModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-xl border border-gray-700 w-full max-w-2xl mx-4">
        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-medium text-white">Neues Support-Ticket erstellen</h3>
            <button onclick="hideCreateTicketModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="createTicketForm" action="/api/create-ticket.php" method="POST" enctype="multipart/form-data" class="p-6">
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Betreff *</label>
                    <input type="text" name="subject" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400" placeholder="Kurze Beschreibung des Problems">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-white mb-2">Kategorie *</label>
                        <select name="category" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
                            <option value="">Kategorie wählen</option>
                            <option value="technical">Technischer Support</option>
                            <option value="billing">Rechnungen & Zahlungen</option>
                            <option value="general">Allgemeine Fragen</option>
                            <option value="abuse">Missbrauch melden</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-white mb-2">Priorität</label>
                        <select name="priority" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
                            <option value="low">Niedrig</option>
                            <option value="medium" selected>Normal</option>
                            <option value="high">Hoch</option>
                            <option value="urgent">Dringend</option>
                        </select>
                    </div>
                </div>
                
                <?php if (!empty($user_services)): ?>
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Betroffener Service</label>
                    <select name="service_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
                        <option value="">Keinen Service zuordnen</option>
                        <?php foreach ($user_services as $service): ?>
                            <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Beschreibung *</label>
                    <textarea name="description" required rows="6" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400" placeholder="Detaillierte Beschreibung des Problems..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Anhänge</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="file-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-700 hover:bg-gray-750">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                <p class="mb-2 text-sm text-gray-400">
                                    <span class="font-semibold">Klicken zum Hochladen</span> oder Dateien hierher ziehen
                                </p>
                                <p class="text-xs text-gray-500">PNG, JPG, PDF bis zu 10MB</p>
                            </div>
                            <input id="file-upload" name="attachments[]" type="file" class="hidden" multiple accept=".png,.jpg,.jpeg,.pdf,.txt,.log">
                        </label>
                    </div>
                    <div id="file-list" class="mt-2 space-y-1"></div>
                </div>
            </div>
            
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-gray-700">
                <button type="button" onclick="hideCreateTicketModal()" class="px-4 py-2 text-gray-300 hover:text-white transition-colors">
                    Abbrechen
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    Ticket erstellen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateTicketModal() {
    document.getElementById('createTicketModal').classList.remove('hidden');
    document.getElementById('createTicketModal').classList.add('flex');
}

function hideCreateTicketModal() {
    document.getElementById('createTicketModal').classList.add('hidden');
    document.getElementById('createTicketModal').classList.remove('flex');
    document.getElementById('createTicketForm').reset();
    document.getElementById('file-list').innerHTML = '';
}

function viewTicket(ticketId) {
    window.location.href = '/dashboard/ticket-view?id=' + ticketId;
}

// File Upload Handler
document.getElementById('file-upload').addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';
    
    Array.from(e.target.files).forEach(function(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between p-2 bg-gray-700 rounded text-sm';
        fileItem.innerHTML = `
            <span class="text-white">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300">
                <i class="fas fa-times"></i>
            </button>
        `;
        fileList.appendChild(fileItem);
    });
});

// Form submission
document.getElementById('createTicketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/api/create-ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideCreateTicketModal();
            window.location.reload();
        } else {
            alert('Fehler beim Erstellen des Tickets: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Erstellen des Tickets.');
    });
});
</script>

<?php endDashboardPage(); ?>