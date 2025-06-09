<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Authenticate user
$auth = new Auth($db);
$user = $auth->login('kunde@test.de', 'kunde123');

echo "Authenticated: " . $user['email'] . "\n";

// Create ticket directly in database
$stmt = $db->prepare("INSERT INTO tickets (user_id, subject, message, category, priority, status) VALUES (?, ?, ?, ?, ?, ?)");
$success = $stmt->execute([
    $user['id'],
    'Direct Database Test Ticket',
    'This ticket was created directly in the database to test functionality.',
    'technical',
    'medium',
    'open'
]);

if ($success) {
    $ticketId = $db->lastInsertId();
    echo "Created ticket #$ticketId successfully\n";
    
    // Fetch the ticket to verify
    $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if ($ticket) {
        echo "Ticket verification successful:\n";
        echo "Subject: " . $ticket['subject'] . "\n";
        echo "Status: " . $ticket['status'] . "\n";
        echo "Priority: " . $ticket['priority'] . "\n";
    }
} else {
    echo "Failed to create ticket\n";
}

// Test fetching all tickets for the user
echo "\nFetching all tickets for user:\n";
$stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

foreach ($tickets as $ticket) {
    echo "- Ticket #{$ticket['id']}: {$ticket['subject']} [{$ticket['status']}]\n";
}
?>