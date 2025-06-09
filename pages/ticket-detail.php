<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/layout.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: /dashboard/support');
    exit;
}

$database = Database::getInstance();
$ticketId = (int)$_GET['id'];

// Get ticket details with permissions check
$stmt = $database->prepare("
    SELECT t.*, u.first_name, u.last_name, u.email, u.role as user_role,
           a.first_name as assigned_first_name, a.last_name as assigned_last_name
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN users a ON t.assigned_to = a.id
    WHERE t.id = ? AND (t.user_id = ? OR ? IN (SELECT id FROM users WHERE role IN ('admin', 'support')))
");
$stmt->execute([$ticketId, $_SESSION['user_id'], $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: /dashboard/support');
    exit;
}

// Get current user role
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUserRole = $stmt->fetch()['role'];

// Get replies
$stmt = $database->prepare("
    SELECT r.*, u.first_name, u.last_name, u.role
    FROM ticket_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ? AND (r.is_internal = 0 OR ? IN (SELECT id FROM users WHERE role IN ('admin', 'support')))
    ORDER BY r.created_at ASC
");
$stmt->execute([$ticketId, $_SESSION['user_id']]);
$replies = $stmt->fetchAll();

// Get attachments
$stmt = $database->prepare("
    SELECT a.*, u.first_name, u.last_name
    FROM ticket_attachments a
    JOIN users u ON a.uploaded_by = u.id
    WHERE a.ticket_id = ?
    ORDER BY a.created_at ASC
");
$stmt->execute([$ticketId]);
$attachments = $stmt->fetchAll();

$title = 'Ticket #' . $ticket['id'] . ' - ' . htmlspecialchars($ticket['subject']);
$description = 'Support-Ticket Details und Kommunikation';
renderHeader($title, $description);

$statusColors = [
    'open' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'waiting_customer' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
];

$priorityColors = [
    'low' => 'text-gray-600',
    'medium' => 'text-blue-600',
    'high' => 'text-orange-600',
    'critical' => 'text-red-600'
];
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8">
                        <a href="/dashboard/support" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            <i class="fas fa-arrow-left mr-2"></i>Zurück zu Support
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
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
        <!-- Ticket Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Ticket #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['subject']) ?>
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span>Von: <?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></span>
                            <span>Erstellt: <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></span>
                            <span>Kategorie: <?= ucfirst($ticket['category']) ?></span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                        </span>
                        <span class="px-3 py-1 text-sm font-semibold <?= $priorityColors[$ticket['priority']] ?? 'text-gray-600' ?>">
                            <i class="fas fa-flag mr-1"></i><?= ucfirst($ticket['priority']) ?>
                        </span>
                    </div>
                </div>
                
                <?php if (in_array($currentUserRole, ['admin', 'support'])): ?>
                <!-- Admin Controls -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap gap-4">
                        <select id="status-select" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Offen</option>
                            <option value="waiting_customer" <?= $ticket['status'] === 'waiting_customer' ? 'selected' : '' ?>>Wartet auf Kunde</option>
                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Bearbeitung</option>
                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Geschlossen</option>
                        </select>
                        
                        <select id="priority-select" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Niedrig</option>
                            <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Normal</option>
                            <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>Hoch</option>
                            <option value="critical" <?= $ticket['priority'] === 'critical' ? 'selected' : '' ?>>Kritisch</option>
                        </select>
                        
                        <button onclick="updateTicket()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i>Aktualisieren
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Original Message -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                            <?= strtoupper(substr($ticket['first_name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?>
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
                            </span>
                        </div>
                        <div class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($ticket['message']) ?></div>
                        
                        <!-- Attachments for original ticket -->
                        <?php if (!empty($attachments)): ?>
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Anhänge:</h4>
                            <div class="space-y-2">
                                <?php foreach ($attachments as $attachment): ?>
                                <?php if (!$attachment['reply_id']): ?>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-paperclip text-gray-400 mr-2"></i>
                                    <a href="/api/download-attachment.php?id=<?= $attachment['id'] ?>" 
                                       class="text-blue-600 dark:text-blue-400 hover:underline" 
                                       target="_blank">
                                        <?= htmlspecialchars($attachment['original_filename']) ?>
                                    </a>
                                    <span class="text-gray-500 ml-2">(<?= number_format($attachment['file_size'] / 1024, 1) ?> KB)</span>
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
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 <?= $reply['role'] === 'admin' || $reply['role'] === 'support' ? 'bg-green-500' : 'bg-blue-500' ?> rounded-full flex items-center justify-center text-white font-semibold">
                            <?= strtoupper(substr($reply['first_name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']) ?>
                                <?php if ($reply['role'] === 'admin' || $reply['role'] === 'support'): ?>
                                <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">Support</span>
                                <?php endif; ?>
                                <?php if ($reply['is_internal']): ?>
                                <span class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 rounded-full">Intern</span>
                                <?php endif; ?>
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?= date('d.m.Y H:i', strtotime($reply['created_at'])) ?>
                            </span>
                        </div>
                        <div class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($reply['message']) ?></div>
                        
                        <!-- Reply attachments -->
                        <?php
                        $replyAttachments = array_filter($attachments, function($att) use ($reply) {
                            return $att['reply_id'] == $reply['id'];
                        });
                        ?>
                        <?php if (!empty($replyAttachments)): ?>
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Anhänge:</h4>
                            <div class="space-y-2">
                                <?php foreach ($replyAttachments as $attachment): ?>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-paperclip text-gray-400 mr-2"></i>
                                    <a href="/api/download-attachment.php?id=<?= $attachment['id'] ?>" 
                                       class="text-blue-600 dark:text-blue-400 hover:underline" 
                                       target="_blank">
                                        <?= htmlspecialchars($attachment['original_filename']) ?>
                                    </a>
                                    <span class="text-gray-500 ml-2">(<?= number_format($attachment['file_size'] / 1024, 1) ?> KB)</span>
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

        <!-- Reply Form -->
        <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Antworten</h3>
                
                <form id="reply-form" class="space-y-4">
                    <div>
                        <textarea id="reply-message" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="Ihre Antwort..." required></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anhänge (optional)</label>
                        <input type="file" id="reply-attachment" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.doc,.docx,.zip" multiple>
                    </div>
                    
                    <?php if (in_array($currentUserRole, ['admin', 'support'])): ?>
                    <div class="flex items-center">
                        <input type="checkbox" id="is-internal" class="mr-2">
                        <label for="is-internal" class="text-sm text-gray-700 dark:text-gray-300">Interne Notiz (nur für Support sichtbar)</label>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeTicket()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>Ticket schließen
                        </button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-reply mr-2"></i>Antworten
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const ticketId = <?= $ticketId ?>;
        
        // Reply form submission
        document.getElementById('reply-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = document.getElementById('reply-message').value.trim();
            const isInternal = document.getElementById('is-internal')?.checked || false;
            
            if (!message) {
                alert('Bitte geben Sie eine Nachricht ein.');
                return;
            }
            
            try {
                // Submit reply
                const response = await fetch('/api/ticket-replies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message: message,
                        is_internal: isInternal
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Handle file uploads if any
                    const files = document.getElementById('reply-attachment').files;
                    if (files.length > 0) {
                        for (let file of files) {
                            const formData = new FormData();
                            formData.append('attachment', file);
                            formData.append('ticket_id', ticketId);
                            
                            await fetch('/api/upload-attachment.php', {
                                method: 'POST',
                                body: formData
                            });
                        }
                    }
                    
                    location.reload();
                } else {
                    alert('Fehler: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten.');
            }
        });
        
        // Update ticket (admin only)
        async function updateTicket() {
            const status = document.getElementById('status-select').value;
            const priority = document.getElementById('priority-select').value;
            
            try {
                const response = await fetch(`/api/tickets.php?id=${ticketId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: status,
                        priority: priority
                    })
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
        
        // Close ticket
        async function closeTicket() {
            if (!confirm('Möchten Sie dieses Ticket wirklich schließen?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/tickets.php?id=${ticketId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: 'closed'
                    })
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