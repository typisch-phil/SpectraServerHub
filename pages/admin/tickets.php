<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

// Create tickets table if not exists and add sample data
$database->getConnection()->exec("
    CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'open',
        priority VARCHAR(20) DEFAULT 'normal',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Check if tickets exist, if not add sample data
$stmt = $database->prepare("SELECT COUNT(*) FROM tickets");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $sampleTickets = [
        [1, 'Server Problem', 'Mein vServer ist nicht erreichbar seit heute morgen.', 'open', 'high'],
        [1, 'Domain Weiterleitung', 'Kann die Domain-Weiterleitung nicht konfigurieren.', 'pending', 'normal'],
        [1, 'Backup Anfrage', 'Benötige ein Backup meiner Website vom letzten Freitag.', 'closed', 'low']
    ];
    
    $stmt = $database->prepare("INSERT INTO tickets (user_id, subject, message, status, priority) VALUES (?, ?, ?, ?, ?)");
    foreach ($sampleTickets as $ticket) {
        $stmt->execute($ticket);
    }
}

global $db;

// Get all tickets with user information and reply count
$stmt = $db->prepare("
    SELECT t.*, u.email, u.first_name, u.last_name,
           COUNT(r.id) as reply_count
    FROM tickets t 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN ticket_replies r ON t.id = r.ticket_id
    GROUP BY t.id
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
                                    <button onclick="viewTicket(<?= $ticket['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateStatus(<?= $ticket['id'] ?>, '<?= $ticket['status'] ?>')" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
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

    <script>
        function viewTicket(ticketId) {
            alert('Ticket Details anzeigen: #' + ticketId);
        }

        async function updateTicketStatus(ticketId, status) {
            try {
                const response = await fetch(`/api/tickets.php?id=${ticketId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: status
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