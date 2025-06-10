<?php
require_once '../includes/dashboard-layout.php';

header('Content-Type: application/json');

// Benutzer authentifizierung prüfen
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

// MySQL-Datenbankverbindung
$host = $_ENV['MYSQL_HOST'] ?? 'localhost';
$username = $_ENV['MYSQL_USER'] ?? 'root';
$password = $_ENV['MYSQL_PASSWORD'] ?? '';
$database = $_ENV['MYSQL_DATABASE'] ?? 'spectrahost';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}

try {
    switch($method) {
        case 'GET':
            handleGetTickets($mysqli, $user_id);
            break;
        case 'POST':
            handleCreateTicket($mysqli, $user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Methode nicht erlaubt']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server-Fehler: ' . $e->getMessage()]);
}

function handleGetTickets($mysqli, $user_id) {
    $stmt = $mysqli->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id) as message_count,
               (SELECT tm.created_at FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) as last_activity
        FROM support_tickets t 
        WHERE t.user_id = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    
    echo json_encode($tickets);
}

function handleCreateTicket($mysqli, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['subject']) || !isset($input['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Betreff und Beschreibung sind erforderlich']);
        return;
    }
    
    $subject = trim($input['subject']);
    $description = trim($input['description']);
    $category = $input['category'] ?? 'general';
    $priority = $input['priority'] ?? 'medium';
    $service_id = $input['service_id'] ?? null;
    
    if (empty($subject) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Betreff und Beschreibung dürfen nicht leer sein']);
        return;
    }
    
    // Ticket erstellen
    $stmt = $mysqli->prepare("
        INSERT INTO support_tickets (user_id, subject, description, category, priority, status, service_id) 
        VALUES (?, ?, ?, ?, ?, 'open', ?)
    ");
    $stmt->bind_param("issssi", $user_id, $subject, $description, $category, $priority, $service_id);
    
    if ($stmt->execute()) {
        $ticket_id = $mysqli->insert_id;
        
        // Erste Nachricht hinzufügen
        $stmt2 = $mysqli->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin_reply) 
            VALUES (?, ?, ?, 0)
        ");
        $stmt2->bind_param("iis", $ticket_id, $user_id, $description);
        $stmt2->execute();
        
        echo json_encode([
            'success' => true,
            'ticket_id' => $ticket_id,
            'message' => 'Ticket erfolgreich erstellt'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Erstellen des Tickets']);
    }
}
?>