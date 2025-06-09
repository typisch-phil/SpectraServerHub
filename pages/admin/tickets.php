<?php
require_once '../../includes/session.php';
requireLogin();
requireAdmin();

$db = Database::getInstance();

// Create tickets table if not exists and add sample data
$db->getConnection()->exec("
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
$stmt = $db->prepare("SELECT COUNT(*) FROM tickets");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $sampleTickets = [
        [1, 'Server Problem', 'Mein vServer ist nicht erreichbar seit heute morgen.', 'open', 'high'],
        [1, 'Domain Weiterleitung', 'Kann die Domain-Weiterleitung nicht konfigurieren.', 'pending', 'normal'],
        [1, 'Backup Anfrage', 'Benötige ein Backup meiner Website vom letzten Freitag.', 'closed', 'low']
    ];
    
    $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, message, status, priority) VALUES (?, ?, ?, ?, ?)");
    foreach ($sampleTickets as $ticket) {
        $stmt->execute($ticket);
    }
}

// Get all tickets with user information
$stmt = $db->prepare("
    SELECT t.*, u.first_name, u.last_name, u.email 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket-System - SpectraHost Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="/admin" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-2"></i>Admin Dashboard
                        </a>
                    </div>
                    <h1 class="text-xl font-bold">Ticket-System</h1>
                    <div>
                        <a href="/api/logout" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Support-Tickets</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betreff</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kunde</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priorität</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erstellt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?= $ticket['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ticket['subject']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($ticket['message'], 0, 50)) ?>...</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($ticket['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'open' => 'bg-red-100 text-red-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'closed' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusTexts = [
                                        'open' => 'Offen',
                                        'pending' => 'In Bearbeitung',
                                        'closed' => 'Geschlossen'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= $statusTexts[$ticket['status']] ?? ucfirst($ticket['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $priorityColors = [
                                        'low' => 'bg-blue-100 text-blue-800',
                                        'normal' => 'bg-gray-100 text-gray-800',
                                        'high' => 'bg-orange-100 text-orange-800',
                                        'urgent' => 'bg-red-100 text-red-800'
                                    ];
                                    $priorityTexts = [
                                        'low' => 'Niedrig',
                                        'normal' => 'Normal',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= $priorityTexts[$ticket['priority']] ?? ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewTicket(<?= $ticket['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateStatus(<?= $ticket['id'] ?>, '<?= $ticket['status'] ?>')" class="text-green-600 hover:text-green-900">
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

        function updateStatus(ticketId, currentStatus) {
            const statuses = ['open', 'pending', 'closed'];
            const statusTexts = {'open': 'Offen', 'pending': 'In Bearbeitung', 'closed': 'Geschlossen'};
            
            let options = '';
            statuses.forEach(status => {
                const selected = status === currentStatus ? 'selected' : '';
                options += `<option value="${status}" ${selected}>${statusTexts[status]}</option>`;
            });
            
            const newStatus = prompt(`Status für Ticket #${ticketId} ändern:\n\nNeuen Status wählen:`, currentStatus);
            if (newStatus && statuses.includes(newStatus)) {
                alert(`Ticket #${ticketId} Status geändert zu: ${statusTexts[newStatus]}`);
            }
        }
    </script>
</body>
</html>