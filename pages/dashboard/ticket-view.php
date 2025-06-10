<?php
require_once __DIR__ . '/../../includes/dashboard-layout.php';
require_once __DIR__ . '/../../includes/database.php';

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Ticket-ID aus URL abrufen
if (!isset($_GET['id'])) {
    header("Location: /dashboard/support");
    exit;
}

$ticket_id = (int)$_GET['id'];
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Benutzerdaten abrufen
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
if (!$user) {
    header("Location: /login");
    exit;
}

// Ticket-Details abrufen
$ticket = $db->fetchOne("
    SELECT t.*, u.first_name, u.last_name, u.email,
           s.name as service_name
    FROM support_tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN services s ON t.service_id = s.id
    WHERE t.id = ? AND t.user_id = ?
", [$ticket_id, $user_id]);

if (!$ticket) {
    header("Location: /dashboard/support");
    exit;
}

// Nachrichten abrufen
$messages = $db->fetchAll("
    SELECT m.*, u.first_name, u.last_name, u.email
    FROM support_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.ticket_id = ?
    ORDER BY m.created_at ASC
", [$ticket_id]);

$page_title = "Support Ticket #" . $ticket['id'];
?>

<div class="space-y-6">
    <!-- Ticket Header -->
    <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white">Ticket #<?php echo $ticket['id']; ?></h1>
                    <p class="text-gray-400"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 text-sm font-medium rounded-full 
                        <?php 
                        switch($ticket['status']) {
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
                        echo $status_labels[$ticket['status']] ?? ucfirst($ticket['status']);
                        ?>
                    </span>
                    <span class="px-3 py-1 text-sm font-medium rounded-full 
                        <?php 
                        switch($ticket['priority']) {
                            case 'urgent': echo 'bg-red-900 text-red-400'; break;
                            case 'high': echo 'bg-orange-900 text-orange-400'; break;
                            case 'medium': echo 'bg-yellow-900 text-yellow-400'; break;
                            case 'low': echo 'bg-green-900 text-green-400'; break;
                            default: echo 'bg-gray-700 text-gray-300';
                        }
                        ?>">
                        <?php echo ucfirst($ticket['priority']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-300">Erstellt</h3>
                    <p class="text-white"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-300">Kategorie</h3>
                    <p class="text-white"><?php echo ucfirst($ticket['category']); ?></p>
                </div>
                <?php if ($ticket['service_name']): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-300">Service</h3>
                    <p class="text-white"><?php echo htmlspecialchars($ticket['service_name']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Nachrichten -->
    <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-medium text-white">Konversation</h2>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Ursprüngliche Nachricht -->
            <div class="flex space-x-4">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <h4 class="text-sm font-medium text-white">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </h4>
                        <span class="text-xs text-gray-400">
                            <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                        </span>
                    </div>
                    <div class="bg-gray-700 rounded-lg p-4">
                        <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Weitere Nachrichten -->
            <?php foreach ($messages as $message): ?>
            <div class="flex space-x-4">
                <div class="w-10 h-10 <?php echo $message['is_staff'] ? 'bg-green-600' : 'bg-blue-600'; ?> rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">
                        <?php echo strtoupper(substr($message['first_name'], 0, 1)); ?>
                    </span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <h4 class="text-sm font-medium text-white">
                            <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                            <?php if ($message['is_staff']): ?>
                                <span class="text-xs bg-green-900 text-green-400 px-2 py-1 rounded">Support</span>
                            <?php endif; ?>
                        </h4>
                        <span class="text-xs text-gray-400">
                            <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                        </span>
                    </div>
                    <div class="bg-gray-700 rounded-lg p-4">
                        <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Antwort-Formular -->
    <?php if (in_array($ticket['status'], ['open', 'in_progress', 'waiting_customer'])): ?>
    <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-medium text-white">Antworten</h2>
        </div>
        
        <form action="/dashboard/add-ticket-reply" method="POST" class="p-6">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Ihre Nachricht</label>
                    <textarea name="message" rows="4" required
                              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                              placeholder="Schreiben Sie Ihre Antwort..."></textarea>
                </div>
                
                <div class="flex justify-between">
                    <a href="/dashboard/support" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Zurück zur Übersicht
                    </a>
                    <div class="space-x-3">
                        <?php if ($ticket['status'] !== 'closed'): ?>
                        <button type="button" onclick="closeTicket(<?php echo $ticket['id']; ?>)" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Ticket schließen
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Antworten
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function closeTicket(ticketId) {
    if (confirm('Möchten Sie dieses Ticket wirklich schließen?')) {
        fetch('/dashboard/update-ticket-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ticket_id=${ticketId}&status=closed`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Fehler beim Schließen des Tickets');
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>