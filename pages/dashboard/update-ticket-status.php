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
$status = $_POST['status'] ?? null;

if (!$ticket_id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID und Status sind erforderlich']);
    exit;
}

// Nur bestimmte Status-Änderungen erlauben
$allowed_statuses = ['open', 'closed'];
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiger Status']);
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
    // Prüfen ob Ticket dem User gehört
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
    
    // Status aktualisieren
    $stmt = $mysqli->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $status, $ticket_id, $user_id);
    
    if ($stmt->execute()) {
        // System-Nachricht hinzufügen
        $message = $status === 'closed' ? 'Ticket wurde vom Kunden geschlossen.' : 'Ticket wurde vom Kunden wieder geöffnet.';
        $stmt2 = $mysqli->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin_reply) 
            VALUES (?, ?, ?, 0)
        ");
        $stmt2->bind_param("iis", $ticket_id, $user_id, $message);
        $stmt2->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket-Status erfolgreich geändert'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Ändern des Status']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server-Fehler: ' . $e->getMessage()]);
}
?>