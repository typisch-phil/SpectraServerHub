<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    if (!isset($_FILES['attachment']) || !isset($_POST['ticket_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'File and ticket ID required']);
        exit;
    }
    
    $ticket_id = intval($_POST['ticket_id']);
    $reply_id = isset($_POST['reply_id']) ? intval($_POST['reply_id']) : null;
    $file = $_FILES['attachment'];
    
    // Check if user has access to this ticket
    global $db;
    $user = getCurrentUser();
    $ticketCheck = $db->prepare("SELECT user_id FROM tickets WHERE id = ?");
    $ticketCheck->execute([$ticket_id]);
    $ticket = $ticketCheck->fetch();
    
    if (!$ticket || ($user['role'] !== 'admin' && $ticket['user_id'] != $user_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload error']);
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large (max 10MB)']);
        exit;
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'File type not allowed']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/tickets/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Store file info in database
        $stmt = $db->prepare("
            INSERT INTO ticket_attachments (ticket_id, reply_id, user_id, filename, original_filename, file_size, file_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$ticket_id, $reply_id, $user_id, $filename, $file['name'], $file['size'], $file['type']])) {
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'message' => 'File uploaded successfully'
            ]);
        } else {
            unlink($filepath); // Remove uploaded file if database insert fails
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file info']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>