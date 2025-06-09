<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Nicht autorisiert';
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Attachment-ID erforderlich';
    exit;
}

$database = Database::getInstance();
$attachmentId = (int)$_GET['id'];

// Get attachment and check permissions
$stmt = $database->prepare("
    SELECT a.*, t.user_id as ticket_user_id, u.role
    FROM ticket_attachments a
    JOIN tickets t ON a.ticket_id = t.id
    JOIN users u ON u.id = ?
    WHERE a.id = ? AND (t.user_id = ? OR u.role IN ('admin', 'support'))
");
$stmt->execute([$_SESSION['user_id'], $attachmentId, $_SESSION['user_id']]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    echo 'Datei nicht gefunden';
    exit;
}

$filePath = $attachment['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'Datei nicht auf Server gefunden';
    exit;
}

// Set headers for file download
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
header('Content-Length: ' . $attachment['file_size']);
header('Cache-Control: no-cache, must-revalidate');

// Output file
readfile($filePath);
exit;
?>