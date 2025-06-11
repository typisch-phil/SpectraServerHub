<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/unread-notifications.php';
require_once __DIR__ . '/../../includes/timezone-helper.php';
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

// Ticket als gelesen markieren
if ($ticket) {
    $stmt = $db->prepare("UPDATE support_tickets SET user_last_seen = NOW() WHERE id = ?");
    $stmt->execute([$ticket_id]);
}

if (!$ticket) {
    header("Location: /dashboard/support");
    exit;
}

// Nachrichten abrufen
$messages = $db->fetchAll("
    SELECT m.*, u.first_name, u.last_name, u.email
    FROM ticket_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.ticket_id = ?
    ORDER BY m.created_at ASC
", [$ticket_id]);

$page_title = "Support Ticket #" . $ticket['id'];
?>

<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SpectraHost Dashboard</title>
    <meta name="description" content="SpectraHost Support Ticket Details">
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
                        <a href="/dashboard/support" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Support</a>
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

    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="/dashboard" class="text-gray-400 hover:text-white">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-600 mx-2"></i>
                        <a href="/dashboard/support" class="text-gray-400 hover:text-white">Support</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-600 mx-2"></i>
                        <span class="text-gray-300">Ticket #<?php echo $ticket['id']; ?></span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

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

    <!-- Konversations-Verlauf -->
    <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-medium text-white">
                <i class="fas fa-comments mr-2"></i>Konversations-Verlauf
            </h2>
            <div class="flex items-center space-x-4 text-sm text-gray-400">
                <span><i class="fas fa-comments mr-1"></i><?php echo count($messages) + 1; ?> Nachrichten</span>
                <?php if (count($messages) > 0): ?>
                    <?php $lastMessage = end($messages); ?>
                    <span><i class="fas fa-clock mr-1"></i>Letzte Antwort: <?php echo formatGermanDate($lastMessage['created_at']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            <div class="p-6 space-y-4">
                <!-- Ursprüngliche Ticket-Nachricht -->
                <div class="flex space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-white text-sm font-bold">
                                <?php echo strtoupper(substr($ticket['first_name'] ?? $user['first_name'], 0, 1)); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="bg-blue-600 rounded-lg rounded-tl-none p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-white">
                                    <?php echo htmlspecialchars(($ticket['first_name'] ?? $user['first_name']) . ' ' . ($ticket['last_name'] ?? $user['last_name'])); ?>
                                    <span class="ml-2 px-2 py-1 bg-blue-800 text-blue-100 text-xs rounded-full">Ticket erstellt</span>
                                </h4>
                            </div>
                            <p class="text-blue-50 leading-relaxed"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                            <div class="mt-3 flex items-center text-xs text-blue-200">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('d.m.Y, H:i \U\h\r', strtotime($ticket['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Antworten/Nachrichten -->
                <?php foreach ($messages as $index => $message): ?>
                    <?php $isStaff = isset($message['is_admin_reply']) && $message['is_admin_reply']; ?>
                    <div class="flex space-x-4 <?php echo $isStaff ? '' : 'flex-row-reverse'; ?>">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 <?php echo $isStaff ? 'bg-green-600' : 'bg-blue-600'; ?> rounded-full flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm font-bold">
                                    <?php echo strtoupper(substr($message['first_name'], 0, 1)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="<?php echo $isStaff ? 'bg-green-600 rounded-tl-none' : 'bg-blue-600 rounded-tr-none'; ?> rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-white">
                                        <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                        <?php if ($isStaff): ?>
                                            <span class="ml-2 px-2 py-1 bg-green-800 text-green-100 text-xs rounded-full">
                                                <i class="fas fa-headset mr-1"></i>Support Team
                                            </span>
                                        <?php else: ?>
                                            <span class="ml-2 px-2 py-1 bg-blue-800 text-blue-100 text-xs rounded-full">
                                                <i class="fas fa-user mr-1"></i>Kunde
                                            </span>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <p class="<?php echo $isStaff ? 'text-green-50' : 'text-blue-50'; ?> leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($message['message']); ?></p>
                                <div class="mt-3 flex items-center text-xs <?php echo $isStaff ? 'text-green-200' : 'text-blue-200'; ?>">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo date('d.m.Y, H:i \U\h\r', strtotime($message['created_at'])); ?>
                                    <?php if ($index === count($messages) - 1): ?>
                                        <span class="ml-2 px-2 py-1 bg-white bg-opacity-20 rounded-full text-xs">
                                            <i class="fas fa-clock mr-1"></i>Neueste Nachricht
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Wenn keine Nachrichten vorhanden -->
                <?php if (empty($messages)): ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-comments text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-400">Noch keine Antworten vorhanden</p>
                        <p class="text-sm text-gray-500 mt-1">Unser Support-Team wird sich bald bei Ihnen melden</p>
                    </div>
                <?php endif; ?>
            </div>
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
    </div>
</div>

<script>
function closeTicket(ticketId) {
    if (confirm('Möchten Sie dieses Ticket wirklich schließen?')) {
        fetch('/dashboard/update-ticket-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin',
            body: `ticket_id=${ticketId}&status=closed&user_id=<?php echo $_SESSION['user_id'] ?? 0; ?>`
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

// Auto-scroll to bottom of conversation
document.addEventListener('DOMContentLoaded', function() {
    const conversation = document.querySelector('.overflow-y-auto');
    if (conversation) {
        conversation.scrollTop = conversation.scrollHeight;
    }
});
</script>

</body>
</html>