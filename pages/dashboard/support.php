<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Einfache Session-basierte Authentifizierung für Test
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 9; // Testbenutzer
    $_SESSION['user_email'] = 'test@spectrahost.de';
}

// Datenbankverbindung
require_once __DIR__ . '/../../includes/database.php';

$user_tickets = [];
$ticket_stats = [];
$user_services = [];

// Mock-Daten für Demonstration
$user_tickets = [
    [
        'id' => 1,
        'subject' => 'Server Performance Problem',
        'description' => 'Mein Server läuft sehr langsam seit gestern. Können Sie bitte prüfen was los ist?',
        'status' => 'open',
        'priority' => 'high',
        'category' => 'technical',
        'created_at' => '2024-01-15 10:30:00',
        'message_count' => 3
    ],
    [
        'id' => 2,
        'subject' => 'Rechnung für Januar',
        'description' => 'Ich habe noch keine Rechnung für Januar erhalten. Könnten Sie diese bitte zusenden?',
        'status' => 'resolved',
        'priority' => 'medium',
        'category' => 'billing',
        'created_at' => '2024-01-10 14:20:00',
        'message_count' => 5
    ]
];

$ticket_stats = [
    'open' => 1,
    'resolved' => 1,
    'closed' => 0
];

$total_tickets = array_sum($ticket_stats);
$open_tickets = $ticket_stats['open'] ?? 0;
$closed_tickets = $ticket_stats['closed'] ?? 0;
$resolved_tickets = $ticket_stats['resolved'] ?? 0;
?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - SpectraHost Dashboard</title>
    <meta name="description" content="SpectraHost Dashboard - Verwalten Sie Ihre Services">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%233b82f6'/><stop offset='100%' stop-color='%236366f1'/></linearGradient></defs><rect width='32' height='32' rx='6' fill='url(%23g)'/><text x='16' y='22' text-anchor='middle' fill='white' font-family='Arial' font-size='18' font-weight='bold'>S</text></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">

<!-- Dashboard Navigation -->
<nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <a href="/dashboard" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">S</span>
                    </div>
                    <span class="text-xl font-bold text-white">SpectraHost</span>
                </a>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex ml-10 space-x-8">
                    <a href="/dashboard" class="text-gray-300 hover:text-white border-b-2 border-transparent px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="/dashboard/services" class="text-gray-300 hover:text-white border-b-2 border-transparent px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                        <i class="fas fa-server mr-2"></i>Services
                    </a>
                    <a href="/dashboard/billing" class="text-gray-300 hover:text-white border-b-2 border-transparent px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                        <i class="fas fa-credit-card mr-2"></i>Rechnungen
                    </a>
                    <a href="/dashboard/support" class="text-blue-400 border-blue-400 border-b-2 px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                        <i class="fas fa-life-ring mr-2"></i>Support
                    </a>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <div class="hidden md:flex items-center space-x-3">
                    <span class="text-sm text-gray-300">Willkommen, Test User</span>
                    <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-300 text-sm"></i>
                    </div>
                </div>
                
                <!-- Logout Button -->
                <button onclick="logout()" class="text-gray-300 hover:text-white transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

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
                    <?php foreach ($user_tickets as $ticket): ?>
                        <div class="p-6 hover:bg-gray-750 cursor-pointer transition-colors" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
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
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
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
                                    <p class="text-sm text-gray-400 mb-2"><?php echo nl2br(htmlspecialchars(substr($ticket['description'], 0, 200))); ?>...</p>
                                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></span>
                                        <span><i class="fas fa-comments mr-1"></i><?php echo $ticket['message_count']; ?> Nachrichten</span>
                                        <span><i class="fas fa-tag mr-1"></i><?php echo ucfirst($ticket['category']); ?></span>
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
                        </button>
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
        
        <form id="createTicketForm" class="p-6">
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
                
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Beschreibung *</label>
                    <textarea name="description" required rows="6" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400" placeholder="Detaillierte Beschreibung des Problems..."></textarea>
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
}

function viewTicket(ticketId) {
    alert('Ticket #' + ticketId + ' würde geöffnet werden');
}

function logout() {
    window.location.href = '/';
}

// Form submission
document.getElementById('createTicketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Ticket würde erstellt werden');
    hideCreateTicketModal();
});
</script>

</body>
</html>