<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/layout.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    header('Location: /dashboard/support');
    exit;
}

$database = Database::getInstance();

// Get ticket details and verify ownership
$stmt = $database->prepare("
    SELECT t.*, u.first_name, u.last_name, u.email 
    FROM tickets t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: /dashboard/support');
    exit;
}

// Get ticket replies
$stmt = $database->prepare("
    SELECT tr.*, u.first_name, u.last_name, u.email, u.role
    FROM ticket_replies tr
    LEFT JOIN users u ON tr.user_id = u.id
    WHERE tr.ticket_id = ?
    ORDER BY tr.created_at ASC
");
$stmt->execute([$ticket_id]);
$replies = $stmt->fetchAll();

// Get ticket attachments
$stmt = $database->prepare("
    SELECT * FROM ticket_attachments 
    WHERE ticket_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$ticket_id]);
$attachments = $stmt->fetchAll();

renderHeader("Ticket #$ticket_id - $ticket[subject] - SpectraHost");
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
                        <a href="/dashboard/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Meine Services</a>
                        <a href="/dashboard/billing" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Billing</a>
                        <a href="/dashboard/support" class="text-blue-600 dark:text-blue-400 font-medium">Support</a>
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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back to Support -->
        <div class="mb-6">
            <a href="/dashboard/support" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                <i class="fas fa-arrow-left mr-2"></i>
                Zurück zum Support
            </a>
        </div>

        <!-- Ticket Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Ticket #<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['subject']); ?>
                    </h1>
                    <div class="flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                        <span>
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Erstellt: <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                        </span>
                        <span>
                            <i class="fas fa-user mr-1"></i>
                            Von: <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                        </span>
                        <span>
                            <i class="fas fa-tag mr-1"></i>
                            Kategorie: <?php echo ucfirst($ticket['category']); ?>
                        </span>
                        <span class="<?php 
                            $priority_colors = [
                                'low' => 'text-gray-600',
                                'medium' => 'text-blue-600',
                                'high' => 'text-orange-600',
                                'critical' => 'text-red-600'
                            ];
                            echo $priority_colors[$ticket['priority']] ?? 'text-gray-600';
                        ?>">
                            <i class="fas fa-flag mr-1"></i>
                            Priorität: <?php echo ucfirst($ticket['priority']); ?>
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full <?php 
                        $status_colors = [
                            'open' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'waiting_customer' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                        ];
                        echo $status_colors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>">
                        <?php 
                        $status_texts = [
                            'open' => 'Offen',
                            'waiting_customer' => 'Wartet auf Kunde',
                            'in_progress' => 'In Bearbeitung',
                            'closed' => 'Geschlossen'
                        ];
                        echo $status_texts[$ticket['status']] ?? ucfirst($ticket['status']);
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Ticket Conversation -->
        <div class="space-y-6">
            <!-- Original Message -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($ticket['first_name'], 0, 1)); ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                                    </p>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded">
                                    Ursprüngliche Nachricht
                                </span>
                            </div>
                            <div class="prose max-w-none text-gray-700 dark:text-gray-300">
                                <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                            </div>
                            
                            <!-- Original Attachments -->
                            <?php if (!empty($attachments)): ?>
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Anhänge:</h4>
                                <div class="space-y-2">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <?php if (is_null($attachment['reply_id'])): ?>
                                        <div class="flex items-center space-x-2 text-sm">
                                            <i class="fas fa-paperclip text-gray-400"></i>
                                            <a href="/api/download-attachment.php?id=<?php echo $attachment['id']; ?>" 
                                               class="text-blue-600 dark:text-blue-400 hover:underline">
                                                <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                            </a>
                                            <span class="text-gray-500">
                                                (<?php echo round($attachment['file_size'] / 1024, 1); ?> KB)
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Replies -->
            <?php foreach ($replies as $reply): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 <?php echo $reply['role'] === 'admin' ? 'bg-red-500' : 'bg-blue-500'; ?> rounded-full flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($reply['first_name'], 0, 1)); ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?>
                                        <?php if ($reply['role'] === 'admin'): ?>
                                            <span class="text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-2 py-1 rounded ml-2">
                                                Support Team
                                            </span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d.m.Y H:i', strtotime($reply['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="prose max-w-none text-gray-700 dark:text-gray-300">
                                <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                            </div>
                            
                            <!-- Reply Attachments -->
                            <?php 
                            $reply_attachments = array_filter($attachments, function($att) use ($reply) {
                                return $att['reply_id'] == $reply['id'];
                            });
                            if (!empty($reply_attachments)): 
                            ?>
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Anhänge:</h4>
                                <div class="space-y-2">
                                    <?php foreach ($reply_attachments as $attachment): ?>
                                    <div class="flex items-center space-x-2 text-sm">
                                        <i class="fas fa-paperclip text-gray-400"></i>
                                        <a href="/api/download-attachment.php?id=<?php echo $attachment['id']; ?>" 
                                           class="text-blue-600 dark:text-blue-400 hover:underline">
                                            <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                        </a>
                                        <span class="text-gray-500">
                                            (<?php echo round($attachment['file_size'] / 1024, 1); ?> KB)
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Reply Form (only if ticket is not closed) -->
            <?php if ($ticket['status'] !== 'closed'): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Antworten</h3>
                <form id="replyForm" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ihre Nachricht</label>
                        <textarea id="reply-message" rows="6" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Schreiben Sie Ihre Antwort..." required></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anhänge (optional)</label>
                        <input type="file" id="reply-attachments" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.doc,.docx,.zip" multiple>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Unterstützte Dateiformate: Bilder, PDF, Textdateien, Word-Dokumente, ZIP (max. 10MB pro Datei)</p>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-reply mr-2"></i>
                            Antworten
                        </button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mr-3"></i>
                    <p class="text-yellow-800 dark:text-yellow-200">
                        Dieses Ticket ist geschlossen. Um weitere Unterstützung zu erhalten, erstellen Sie bitte ein neues Ticket.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('replyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const message = document.getElementById('reply-message').value.trim();
    const attachments = document.getElementById('reply-attachments').files;
    
    if (!message) {
        alert('Bitte geben Sie eine Nachricht ein.');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ticket_id', '<?php echo $ticket_id; ?>');
        formData.append('message', message);
        
        // Add attachments
        for (let i = 0; i < attachments.length; i++) {
            formData.append('attachments[]', attachments[i]);
        }
        
        const response = await fetch('/api/ticket-replies.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reload the page to show the new reply
            window.location.reload();
        } else {
            alert('Fehler: ' + result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten.');
    }
});
</script>

<?php renderFooter(); ?>