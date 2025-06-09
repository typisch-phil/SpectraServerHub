<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Login test user
$auth = new Auth($db);
$user = $auth->login('kunde@test.de', 'kunde123');

if (!$user) {
    die('Login failed');
}

echo "<h1>Ticket System Test</h1>";
echo "<p>Eingeloggt als: " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</p>";

// Test ticket creation via API simulation
if (isset($_POST['create_ticket'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    
    $stmt = $db->prepare("
        INSERT INTO tickets (user_id, category, priority, subject, message, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 'open', datetime('now'), datetime('now'))
    ");
    
    if ($stmt->execute([$user['id'], $category, $priority, $subject, $message])) {
        $ticket_id = $db->lastInsertId();
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>
                Ticket #$ticket_id erfolgreich erstellt!
              </div>";
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>
                Fehler beim Erstellen des Tickets.
              </div>";
    }
}

// Display existing tickets for this user
$stmt = $db->prepare("
    SELECT t.*, COUNT(r.id) as reply_count
    FROM tickets t 
    LEFT JOIN ticket_replies r ON t.id = r.ticket_id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

echo "<h2>Meine Tickets (" . count($tickets) . ")</h2>";

if (!empty($tickets)) {
    echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f5f5f5; border: 1px solid #ddd;'>
            <th style='padding: 10px; border: 1px solid #ddd;'>ID</th>
            <th style='padding: 10px; border: 1px solid #ddd;'>Betreff</th>
            <th style='padding: 10px; border: 1px solid #ddd;'>Kategorie</th>
            <th style='padding: 10px; border: 1px solid #ddd;'>Priorität</th>
            <th style='padding: 10px; border: 1px solid #ddd;'>Status</th>
            <th style='padding: 10px; border: 1px solid #ddd;'>Antworten</th>
            <th style='padding: 10px; border: 1px solid #ddd;'>Erstellt</th>
          </tr>";
    
    foreach ($tickets as $ticket) {
        $status_colors = [
            'open' => '#28a745',
            'waiting_customer' => '#ffc107',
            'in_progress' => '#007bff',
            'closed' => '#6c757d'
        ];
        $color = $status_colors[$ticket['status']] ?? '#6c757d';
        
        echo "<tr style='border: 1px solid #ddd;'>
                <td style='padding: 10px; border: 1px solid #ddd;'>#" . $ticket['id'] . "</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($ticket['subject']) . "</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . ucfirst($ticket['category']) . "</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . ucfirst($ticket['priority']) . "</td>
                <td style='padding: 10px; border: 1px solid #ddd; color: $color; font-weight: bold;'>" . ucfirst($ticket['status']) . "</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . $ticket['reply_count'] . "</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . date('d.m.Y H:i', strtotime($ticket['created_at'])) . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Keine Tickets gefunden.</p>";
}

// Create ticket form
echo "<h2>Neues Ticket erstellen</h2>";
echo "<form method='POST' style='border: 1px solid #ddd; padding: 20px; margin: 20px 0;'>
        <div style='margin: 10px 0;'>
            <label>Betreff:</label><br>
            <input type='text' name='subject' required style='width: 100%; padding: 8px; margin: 5px 0;'>
        </div>
        <div style='margin: 10px 0;'>
            <label>Kategorie:</label><br>
            <select name='category' style='width: 100%; padding: 8px; margin: 5px 0;'>
                <option value='general'>Allgemein</option>
                <option value='technical'>Technisch</option>
                <option value='billing'>Abrechnung</option>
                <option value='account'>Account</option>
            </select>
        </div>
        <div style='margin: 10px 0;'>
            <label>Priorität:</label><br>
            <select name='priority' style='width: 100%; padding: 8px; margin: 5px 0;'>
                <option value='low'>Niedrig</option>
                <option value='medium' selected>Mittel</option>
                <option value='high'>Hoch</option>
                <option value='critical'>Kritisch</option>
            </select>
        </div>
        <div style='margin: 10px 0;'>
            <label>Nachricht:</label><br>
            <textarea name='message' rows='6' required style='width: 100%; padding: 8px; margin: 5px 0;'></textarea>
        </div>
        <button type='submit' name='create_ticket' style='background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer;'>
            Ticket erstellen
        </button>
      </form>";

echo "<p><a href='/dashboard/support'>← Zurück zum Dashboard</a></p>";
?>