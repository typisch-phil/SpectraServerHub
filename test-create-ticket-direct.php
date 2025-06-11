<?php
// Direct test of the actual create-ticket.php logic
session_start();
$_SESSION['user_id'] = 2; // Set test user

// Simulate POST data
$_POST = [
    'subject' => 'Test Direct Call - ' . date('Y-m-d H:i:s'),
    'description' => 'Testing direct call to create ticket logic',
    'priority' => 'high',
    'category' => 'technical'
];
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Testing create-ticket.php logic directly...\n";
echo "POST data: " . print_r($_POST, true) . "\n";

// Include the create-ticket logic
require_once __DIR__ . '/includes/database.php';

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

try {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    
    echo "Parsed data:\n";
    echo "- Subject: $subject\n";
    echo "- Description: $description\n";
    echo "- Priority: $priority\n";
    echo "- Category: $category\n";
    
    if (empty($subject) || empty($description)) {
        throw new Exception('Betreff und Beschreibung sind erforderlich');
    }
    
    $stmt = $db->prepare("
        INSERT INTO support_tickets (user_id, subject, description, priority, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'open', NOW(), NOW())
    ");
    
    $result = $stmt->execute([$user_id, $subject, $description, $priority]);
    
    if ($result) {
        echo "SUCCESS: Ticket created\n";
        
        // Verify it was created
        $ticket = $db->fetchOne("SELECT * FROM support_tickets WHERE subject = ?", [$subject]);
        if ($ticket) {
            echo "Verification: Ticket found with ID " . $ticket['id'] . "\n";
        } else {
            echo "ERROR: Ticket not found after creation\n";
        }
    } else {
        echo "FAILED: Ticket not created\n";
        $errorInfo = $stmt->errorInfo();
        echo "Error info: " . print_r($errorInfo, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
?>