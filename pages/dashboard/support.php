<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/unread-notifications.php';
require_once __DIR__ . '/../../includes/timezone-helper.php';
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get current user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    header("Location: /login");
    exit;
}

// Support-Tickets laden mit zentraler DB
try {
    // Benutzer-Tickets mit erweiterten Informationen abrufen
    $user_tickets = $db->fetchAll("
        SELECT t.*, 
               (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id) as message_count,
               (SELECT tm.created_at FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) as last_reply,
               (SELECT tm.message FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) as last_message,
               (SELECT u2.first_name FROM ticket_messages tm 
                LEFT JOIN users u2 ON tm.user_id = u2.id 
                WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) as last_reply_by,
               (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id AND tm.created_at > t.user_last_seen) as unread_replies
        FROM support_tickets t 
        WHERE t.user_id = ? 
        ORDER BY t.updated_at DESC
    ", [$user['id']]);
    
    // Ticket-Statistiken
    $stats = $db->fetchAll("SELECT status, COUNT(*) as count FROM support_tickets WHERE user_id = ? GROUP BY status", [$user['id']]);
    $ticket_stats = [];
    foreach ($stats as $stat) {
        $ticket_stats[$stat['status']] = $stat['count'];
    }
    
    // Benutzer-Services für Ticket-Erstellung
    $user_services = $db->fetchAll("
        SELECT id, name
        FROM services 
        WHERE user_id = ? AND status = 'active'
        ORDER BY name ASC
    ", [$user['id']]);
    
} catch (Exception $e) {
    error_log("Support page error: " . $e->getMessage());
    $user_tickets = [];
    $ticket_stats = [];
    $user_services = [];
}

// FAQ-Einträge
$faq_items = [
    [
        'question' => 'Wie erstelle ich ein Support-Ticket?',
        'answer' => 'Klicken Sie auf "Neues Ticket erstellen" und füllen Sie das Formular mit Ihrem Anliegen aus.'
    ],
    [
        'question' => 'Wie lange dauert die Bearbeitung?',
        'answer' => 'Wir bearbeiten Tickets normalerweise innerhalb von 24 Stunden. Dringende Anfragen werden priorisiert.'
    ],
    [
        'question' => 'Kann ich Dateien anhängen?',
        'answer' => 'Ja, Sie können Screenshots und andere relevante Dateien zu Ihrem Ticket hinzufügen.'
    ]
];
?>

<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - SpectraHost Dashboard</title>
    <meta name="description" content="SpectraHost Support - Verwalten Sie Ihre Support-Tickets">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            750: '#374151',
                            850: '#1f2937'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">

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
                        <a href="/dashboard/support" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium flex items-center">
                            Support
                            <?php echo getTicketNotificationBadge($db, $user_id); ?>
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-300">
                        Guthaben: <span class="font-bold text-green-400">€<?php echo number_format($user['balance'] ?? 0, 2); ?></span>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-300 hover:text-white focus:outline-none">
                            <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium"><?php echo strtoupper(substr($user['email'] ?? 'U', 0, 1)); ?></span>
                            </div>
                            <span class="text-sm"><?php echo htmlspecialchars($user['email'] ?? 'Benutzer'); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <a href="/dashboard/profile" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profil bearbeiten</a>
                            <a href="/dashboard/settings" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Einstellungen</a>
                            <div class="border-t border-gray-700"></div>
                            <a href="/logout" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Abmelden</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Hauptbereich -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header mit Statistiken -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h1 class="text-2xl font-bold text-white">Support-Center</h1>
                            <button onclick="showCreateTicketModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Neues Ticket
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-ticket-alt text-blue-400 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-white"><?php echo count($user_tickets); ?></p>
                                        <p class="text-sm text-gray-400">Gesamt Tickets</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-900 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-clock text-green-400 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-white"><?php echo ($ticket_stats['open'] ?? 0) + ($ticket_stats['in_progress'] ?? 0); ?></p>
                                        <p class="text-sm text-gray-400">Offene Tickets</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-purple-900 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-check text-purple-400 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-white"><?php echo ($ticket_stats['resolved'] ?? 0) + ($ticket_stats['closed'] ?? 0); ?></p>
                                        <p class="text-sm text-gray-400">Gelöste Tickets</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket-Liste -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h2 class="text-lg font-medium text-white">Meine Tickets</h2>
                    </div>
                    
                    <div class="divide-y divide-gray-700">
                        <?php if (empty($user_tickets)): ?>
                            <div class="p-6 text-center">
                                <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-ticket-alt text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-white mb-2">Keine Tickets vorhanden</h3>
                                <p class="text-gray-400 mb-4">Sie haben noch keine Support-Tickets erstellt.</p>
                                <button onclick="showCreateTicketModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Erstes Ticket erstellen
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($user_tickets as $ticket): ?>
                                <?php 
                                    $hasUnread = isset($ticket['unread_replies']) && $ticket['unread_replies'] > 0;
                                    $messageCount = $ticket['message_count'] ?? 0;
                                    $lastReply = $ticket['last_reply'] ?? null;
                                    $lastMessage = $ticket['last_message'] ?? null;
                                    $lastReplyBy = $ticket['last_reply_by'] ?? null;
                                ?>
                                <div class="p-6 hover:bg-gray-750 cursor-pointer <?php echo $hasUnread ? 'border-l-4 border-blue-500 bg-gray-750/50' : ''; ?>" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                                
                                                <?php if ($hasUnread): ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-900 text-red-400 animate-pulse">
                                                    <?php echo $ticket['unread_replies']; ?> neue
                                                </span>
                                                <?php endif; ?>
                                                
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php echo $ticket['status'] === 'open' ? 'bg-blue-900 text-blue-400' : 
                                                            ($ticket['status'] === 'in_progress' ? 'bg-yellow-900 text-yellow-400' : 
                                                            ($ticket['status'] === 'closed' ? 'bg-gray-600 text-gray-300' : 'bg-green-900 text-green-400')); ?>">
                                                    <?php echo ucfirst($ticket['status'] ?? 'open'); ?>
                                                </span>
                                                
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php echo $ticket['priority'] === 'urgent' ? 'bg-red-900 text-red-400' : 
                                                            ($ticket['priority'] === 'high' ? 'bg-orange-900 text-orange-400' : 
                                                            ($ticket['priority'] === 'low' ? 'bg-gray-600 text-gray-300' : 'bg-yellow-900 text-yellow-400')); ?>">
                                                    <?php echo ucfirst($ticket['priority'] ?? 'medium'); ?>
                                                </span>
                                            </div>
                                            
                                            <p class="text-sm text-gray-400 mb-3 line-clamp-2"><?php echo nl2br(htmlspecialchars(substr($ticket['description'] ?? '', 0, 150))); ?><?php echo strlen($ticket['description'] ?? '') > 150 ? '...' : ''; ?></p>
                                            
                                            <?php if ($lastMessage && $lastReply): ?>
                                            <div class="bg-gray-700/50 rounded-lg p-3 mb-3 border-l-2 border-gray-600">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-medium text-gray-300">Letzte Antwort:</span>
                                                    <span class="text-xs text-gray-500"><?php echo formatGermanDate($lastReply); ?></span>
                                                </div>
                                                <p class="text-xs text-gray-400 line-clamp-2"><?php echo htmlspecialchars(substr($lastMessage, 0, 100)); ?><?php echo strlen($lastMessage) > 100 ? '...' : ''; ?></p>
                                                <?php if ($lastReplyBy): ?>
                                                <span class="text-xs text-gray-500">von <?php echo htmlspecialchars($lastReplyBy); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                <span><i class="fas fa-calendar mr-1"></i><?php echo formatGermanDate($ticket['created_at'] ?? 'now'); ?></span>
                                                <span class="<?php echo $messageCount > 0 ? 'text-blue-400' : ''; ?>">
                                                    <i class="fas fa-comments mr-1"></i><?php echo $messageCount; ?> Nachrichten
                                                </span>
                                                <?php if ($ticket['updated_at'] && $ticket['updated_at'] !== $ticket['created_at']): ?>
                                                <span><i class="fas fa-clock mr-1"></i>Aktualisiert: <?php echo formatGermanDate($ticket['updated_at']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="ml-4 flex flex-col items-center space-y-2">
                                            <?php if ($hasUnread): ?>
                                            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                            <?php endif; ?>
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

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Schnellzugriff -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Schnellzugriff</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <button onclick="showCreateTicketModal()" class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750 text-left">
                                <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-plus text-blue-400"></i>
                                </div>
                                <span class="text-sm font-medium text-white">Neues Ticket erstellen</span>
                            </button>
                            
                            <a href="/dashboard/services" class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750">
                                <div class="w-8 h-8 bg-green-900 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-server text-green-400"></i>
                                </div>
                                <span class="text-sm font-medium text-white">Meine Services</span>
                            </a>
                            
                            <a href="/dashboard/billing" class="w-full flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750">
                                <div class="w-8 h-8 bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-file-invoice text-purple-400"></i>
                                </div>
                                <span class="text-sm font-medium text-white">Rechnungen</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Häufige Fragen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($faq_items as $index => $faq): ?>
                            <div class="border-b border-gray-700 pb-4 last:border-b-0">
                                <button class="w-full text-left" onclick="toggleFaq(<?php echo $index; ?>)">
                                    <h4 class="text-sm font-medium text-white hover:text-blue-400"><?php echo htmlspecialchars($faq['question']); ?></h4>
                                </button>
                                <div id="faq-<?php echo $index; ?>" class="hidden mt-2">
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($faq['answer']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Kontakt -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Direkter Kontakt</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-gray-400 mr-3"></i>
                                <span class="text-gray-300">support@spectrahost.de</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone text-gray-400 mr-3"></i>
                                <span class="text-gray-300">+49 (0) 123 456 789</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock text-gray-400 mr-3"></i>
                                <span class="text-gray-300">24/7 Support</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal für neues Ticket -->
<div id="createTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-white">Neues Support-Ticket erstellen</h2>
                <button onclick="closeCreateTicketModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <form action="/dashboard/create-ticket" method="POST" class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Betreff *</label>
                <input type="text" name="subject" required 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Kategorie</label>
                    <select name="category" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="general">Allgemein</option>
                        <option value="technical">Technisch</option>
                        <option value="billing">Abrechnung</option>
                        <option value="abuse">Missbrauch</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Priorität</label>
                    <select name="priority" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="low">Niedrig</option>
                        <option value="medium" selected>Mittel</option>
                        <option value="high">Hoch</option>
                        <option value="urgent">Dringend</option>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($user_services)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Betroffener Service (optional)</label>
                <select name="service_id" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="">-- Service auswählen --</option>
                    <?php foreach ($user_services as $service): ?>
                    <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Beschreibung *</label>
                <textarea name="description" rows="6" required
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                          placeholder="Beschreiben Sie Ihr Anliegen detailliert..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeCreateTicketModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Abbrechen
                </button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Ticket erstellen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showCreateTicketModal() {
        document.getElementById('createTicketModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeCreateTicketModal() {
        document.getElementById('createTicketModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function viewTicket(ticketId) {
        window.location.href = `/dashboard/ticket-view?id=${ticketId}`;
    }

    function toggleFaq(index) {
        const faq = document.getElementById(`faq-${index}`);
        faq.classList.toggle('hidden');
    }

    // Modal schließen bei Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateTicketModal();
        }
    });

    // Modal schließen bei Klick außerhalb
    document.getElementById('createTicketModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCreateTicketModal();
        }
    });
</script>

</body>
</html>