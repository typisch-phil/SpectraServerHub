<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

$database = Database::getInstance();

// Check if file was uploaded
if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Datei-Upload fehlgeschlagen']);
    exit;
}

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : null;
$replyId = isset($_POST['reply_id']) ? (int)$_POST['reply_id'] : null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket-ID erforderlich']);
    exit;
}

// Check if user can access this ticket
$stmt = $database->prepare("
    SELECT t.*, u.role 
    FROM tickets t, users u 
    WHERE t.id = ? AND u.id = ? AND (t.user_id = ? OR u.role IN ('admin', 'support'))
");
$stmt->execute([$ticketId, $_SESSION['user_id'], $_SESSION['user_id']]);
$access = $stmt->fetch();

if (!$access) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung für dieses Ticket']);
    exit;
}

$file = $_FILES['attachment'];
$originalFilename = $file['name'];
$fileSize = $file['size'];
$mimeType = $file['type'];

// Validate file size (max 10MB)
if ($fileSize > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Datei zu groß (max. 10MB)']);
    exit;
}

// Validate file type
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/pdf',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/zip',
    'application/x-zip-compressed'
];

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Dateityp nicht erlaubt']);
    exit;
}

// Generate unique filename
$extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
$uploadPath = '../uploads/tickets/' . $filename;

// Create upload directory if it doesn't exist
if (!is_dir('../uploads/tickets')) {
    mkdir('../uploads/tickets', 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
    exit;
}

// Save attachment to database
$stmt = $database->prepare("
    INSERT INTO ticket_attachments (ticket_id, reply_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $ticketId,
    $replyId,
    $filename,
    $originalFilename,
    $uploadPath,
    $fileSize,
    $mimeType,
    $_SESSION['user_id']
]);

$attachmentId = $database->lastInsertId();

// Update ticket timestamp
$stmt = $database->prepare("UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$ticketId]);

echo json_encode([
    'success' => true,
    'attachment_id' => $attachmentId,
    'filename' => $filename,
    'original_filename' => $originalFilename,
    'file_size' => $fileSize,
    'message' => 'Datei erfolgreich hochgeladen'
]);
?>