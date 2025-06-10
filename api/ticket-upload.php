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

// MySQL-Datenbankverbindung
$host = $_ENV['MYSQL_HOST'] ?? 'localhost';
$username = $_ENV['MYSQL_USER'] ?? 'root';
$password = $_ENV['MYSQL_PASSWORD'] ?? '';
$database = $_ENV['MYSQL_DATABASE'] ?? 'spectrahost';

$mysqli = new mysqli($host, $username, $password, $database);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

try {
    $ticket_id = $_POST['ticket_id'] ?? null;
    $message_id = $_POST['message_id'] ?? null;
    
    if (!$ticket_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket ID erforderlich']);
        exit;
    }
    
    // Prüfen ob Ticket dem User gehört
    $stmt = $mysqli->prepare("SELECT id FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Zugriff verweigert']);
        exit;
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Keine gültige Datei hochgeladen']);
        exit;
    }
    
    $file = $_FILES['file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dateityp nicht erlaubt']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'Datei zu groß (max. 10MB)']);
        exit;
    }
    
    // Upload-Verzeichnis erstellen
    $upload_dir = '../uploads/tickets/' . $ticket_id . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Eindeutigen Dateinamen generieren
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Datei-Info in Datenbank speichern
        $stmt = $mysqli->prepare("
            INSERT INTO ticket_attachments (ticket_id, message_id, filename, original_filename, file_size, mime_type, file_path, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissisis", $ticket_id, $message_id, $filename, $file['name'], $file['size'], $file['type'], $file_path, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'attachment_id' => $mysqli->insert_id,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_size' => $file['size']
            ]);
        } else {
            unlink($file_path); // Datei löschen bei DB-Fehler
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Speichern der Datei-Info']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Hochladen der Datei']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server-Fehler: ' . $e->getMessage()]);
}
?>