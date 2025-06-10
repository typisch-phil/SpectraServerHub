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
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    $service_id = $_POST['service_id'] ?? null;
    
    if (empty($subject) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Betreff und Beschreibung sind erforderlich']);
        exit;
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
        
        // Datei-Upload verarbeiten falls vorhanden
        if (isset($_FILES['files'])) {
            $upload_dir = __DIR__ . '/../../uploads/tickets/' . $ticket_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $files = $_FILES['files'];
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_name = $files['name'][$i];
                    $file_size = $files['size'][$i];
                    $file_type = $files['type'][$i];
                    $file_tmp = $files['tmp_name'][$i];
                    
                    // Datei-Validierung
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip'];
                    $max_size = 10 * 1024 * 1024; // 10MB
                    
                    if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $filename = uniqid() . '_' . time() . '.' . $extension;
                        $file_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            // Datei-Info in Datenbank speichern
                            $stmt3 = $mysqli->prepare("
                                INSERT INTO ticket_attachments (ticket_id, filename, original_filename, file_size, mime_type, file_path, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt3->bind_param("issisis", $ticket_id, $filename, $file_name, $file_size, $file_type, $file_path, $user_id);
                            $stmt3->execute();
                        }
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'ticket_id' => $ticket_id,
            'message' => 'Ticket erfolgreich erstellt'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Erstellen des Tickets']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server-Fehler: ' . $e->getMessage()]);
}
?>