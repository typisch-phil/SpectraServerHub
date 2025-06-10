<?php
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

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header("Location: /dashboard/support");
    exit;
}

// MySQL-Datenbankverbindung
$host = $_ENV['MYSQL_HOST'] ?? 'localhost';
$username = $_ENV['MYSQL_USER'] ?? 'root';
$password = $_ENV['MYSQL_PASSWORD'] ?? '';
$database = $_ENV['MYSQL_DATABASE'] ?? 'spectrahost';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die("Datenbankverbindung fehlgeschlagen: " . $mysqli->connect_error);
}

// Ticket-Details laden
try {
    // Ticket abrufen und prüfen ob es dem User gehört
    $stmt = $mysqli->prepare("
        SELECT t.*, s.name as service_name 
        FROM support_tickets t 
        LEFT JOIN services s ON t.service_id = s.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->bind_param("ii", $ticket_id, $user['id']);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    
    if (!$ticket) {
        header("Location: /dashboard/support");
        exit;
    }
    
    // Nachrichten abrufen
    $stmt = $mysqli->prepare("
        SELECT tm.*, 
               CASE 
                   WHEN tm.is_admin_reply = 1 THEN 'SpectraHost Support' 
                   ELSE CONCAT(u.first_name, ' ', u.last_name)
               END as sender_name,
               tm.is_admin_reply
        FROM ticket_messages tm
        LEFT JOIN users u ON tm.user_id = u.id
        WHERE tm.ticket_id = ?
        ORDER BY tm.created_at ASC
    ");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    
    $messages = [];
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Anhänge abrufen
    $stmt = $mysqli->prepare("
        SELECT ta.*, u.first_name as uploaded_by_name
        FROM ticket_attachments ta
        LEFT JOIN users u ON ta.uploaded_by = u.id
        WHERE ta.ticket_id = ?
        ORDER BY ta.created_at ASC
    ");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $attachments_result = $stmt->get_result();
    
    $attachments = [];
    while ($row = $attachments_result->fetch_assoc()) {
        $attachments[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Ticket view error: " . $e->getMessage());
    header("Location: /dashboard/support");
    exit;
}

renderDashboardHeader('Ticket #' . $ticket_id . ' - Dashboard');
?>

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
                        <a href="/dashboard" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard/support" class="text-gray-300 hover:text-blue-400 px-3 py-1 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Zurück zu Support
                    </a>
                    <a href="/" class="text-gray-300 hover:text-blue-400 px-3 py-1 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Zur Website
                    </a>
                    <button onclick="logout()" class="text-gray-300 hover:text-red-400 px-3 py-1 rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Ticket #<?php echo $ticket['id']; ?></h1>
                    <p class="mt-2 text-gray-400"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <?php
                    $status_colors = [
                        'open' => 'bg-green-500',
                        'in_progress' => 'bg-blue-500',
                        'waiting_customer' => 'bg-yellow-500',
                        'resolved' => 'bg-purple-500',
                        'closed' => 'bg-gray-500'
                    ];
                    $status_labels = [
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_customer' => 'Wartet auf Kunden',
                        'resolved' => 'Gelöst',
                        'closed' => 'Geschlossen'
                    ];
                    $priority_colors = [
                        'urgent' => 'bg-red-500',
                        'high' => 'bg-orange-500',
                        'medium' => 'bg-yellow-500',
                        'low' => 'bg-green-500'
                    ];
                    ?>
                    <span class="px-3 py-1 text-sm font-medium text-white rounded-full <?php echo $status_colors[$ticket['status']] ?? 'bg-gray-500'; ?>">
                        <?php echo $status_labels[$ticket['status']] ?? ucfirst($ticket['status']); ?>
                    </span>
                    <span class="px-3 py-1 text-sm font-medium text-white rounded-full <?php echo $priority_colors[$ticket['priority']] ?? 'bg-gray-500'; ?>">
                        <?php echo ucfirst($ticket['priority']); ?>
                    </span>
                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <button onclick="closeTicket(<?php echo $ticket['id']; ?>)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">
                            <i class="fas fa-times mr-2"></i>Ticket schließen
                        </button>
                    <?php else: ?>
                        <button onclick="reopenTicket(<?php echo $ticket['id']; ?>)" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                            <i class="fas fa-redo mr-2"></i>Ticket wieder öffnen
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Ticket Details -->
            <div class="lg:col-span-1">
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Ticket-Details</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-400">Erstellt:</span>
                            <p class="text-white"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-400">Kategorie:</span>
                            <p class="text-white"><?php echo ucfirst($ticket['category'] ?? 'Allgemein'); ?></p>
                        </div>
                        <?php if ($ticket['service_name']): ?>
                        <div>
                            <span class="text-sm text-gray-400">Service:</span>
                            <p class="text-white"><?php echo htmlspecialchars($ticket['service_name']); ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-sm text-gray-400">Letztes Update:</span>
                            <p class="text-white"><?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($attachments)): ?>
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 mt-6">
                    <h3 class="text-lg font-medium text-white mb-4">Anhänge</h3>
                    <div class="space-y-2">
                        <?php foreach ($attachments as $attachment): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-file text-gray-400"></i>
                                <div>
                                    <p class="text-white text-sm"><?php echo htmlspecialchars($attachment['original_filename']); ?></p>
                                    <p class="text-gray-400 text-xs"><?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Messages -->
            <div class="lg:col-span-3">
                <div class="bg-gray-800 rounded-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Nachrichtenverlauf</h3>
                    </div>
                    <div class="p-6 max-h-96 overflow-y-auto">
                        <div class="space-y-4">
                            <?php foreach ($messages as $message): ?>
                            <div class="<?php echo $message['is_admin_reply'] ? 'bg-blue-900/30 border-l-4 border-blue-500' : 'bg-gray-700'; ?> p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium <?php echo $message['is_admin_reply'] ? 'text-blue-400' : 'text-white'; ?>">
                                            <?php echo htmlspecialchars($message['sender_name']); ?>
                                        </span>
                                        <?php if ($message['is_admin_reply']): ?>
                                        <span class="px-2 py-1 text-xs bg-blue-600 text-white rounded">Support</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-sm text-gray-400"><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></span>
                                </div>
                                <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($ticket['status'] !== 'closed'): ?>
                    <!-- Reply Form -->
                    <div class="px-6 py-4 border-t border-gray-700">
                        <form id="replyForm" onsubmit="sendReply(event)">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Antwort hinzufügen</label>
                                <textarea name="message" rows="4" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ihre Nachricht..." required></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700">
                                    <i class="fas fa-paper-plane mr-2"></i>Antwort senden
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Ticket schließen
async function closeTicket(ticketId) {
    if (!confirm('Möchten Sie dieses Ticket wirklich schließen?')) {
        return;
    }
    
    try {
        const response = await fetch('/dashboard/update-ticket-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ticket_id=${ticketId}&status=closed`
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Schließen des Tickets');
    }
}

// Ticket wieder öffnen
async function reopenTicket(ticketId) {
    if (!confirm('Möchten Sie dieses Ticket wieder öffnen?')) {
        return;
    }
    
    try {
        const response = await fetch('/dashboard/update-ticket-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ticket_id=${ticketId}&status=open`
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Öffnen des Tickets');
    }
}

// Antwort senden
async function sendReply(event) {
    event.preventDefault();
    
    const form = document.getElementById('replyForm');
    const formData = new FormData(form);
    formData.append('ticket_id', <?php echo $ticket['id']; ?>);
    
    try {
        const response = await fetch('/dashboard/add-ticket-reply', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Senden der Antwort');
    }
}

// Logout function
function logout() {
    if (confirm('Möchten Sie sich wirklich abmelden?')) {
        window.location.href = '/api/logout.php';
    }
}
</script>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderDashboardFooter(); ?>