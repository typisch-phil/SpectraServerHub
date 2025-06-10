<?php
require_once __DIR__ . '/../../includes/dashboard-layout.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];

$ticket_id = $_POST['ticket_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$ticket_id || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID und Nachricht sind erforderlich']);
    exit;
}

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
    // Prüfen ob Ticket dem User gehört und nicht geschlossen ist
    $stmt = $mysqli->prepare("SELECT id, status FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket nicht gefunden']);
        exit;
    }
    
    if ($ticket['status'] === 'closed') {
        http_response_code(400);
        echo json_encode(['error' => 'Geschlossene Tickets können nicht beantwortet werden']);
        exit;
    }
    
    // Nachricht hinzufügen
    $stmt = $mysqli->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin_reply) 
        VALUES (?, ?, ?, 0)
    ");
    $stmt->bind_param("iis", $ticket_id, $user_id, $message);
    
    if ($stmt->execute()) {
        // Ticket-Status auf "waiting_customer" oder "open" setzen
        $new_status = ($ticket['status'] === 'resolved') ? 'open' : 'open';
        $stmt2 = $mysqli->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt2->bind_param("si", $new_status, $ticket_id);
        $stmt2->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Antwort erfolgreich hinzugefügt'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Hinzufügen der Nachricht']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server-Fehler: ' . $e->getMessage()]);
}
?>