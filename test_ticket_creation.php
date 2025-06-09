<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Simulate a logged-in user for testing
$_SESSION['user'] = [
    'id' => 1,
    'email' => 'kunde@test.de',
    'first_name' => 'Test',
    'last_name' => 'User',
    'role' => 'user'
];

echo "Session set for user: " . $_SESSION['user']['email'] . "\n";
echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n";
echo "getCurrentUser(): " . print_r(getCurrentUser(), true) . "\n";

// Test ticket creation
$data = [
    'category' => 'technical',
    'priority' => 'medium',
    'subject' => 'Test Ticket',
    'message' => 'This is a test ticket message'
];

// Simulate POST request to ticket API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capture output
ob_start();
$old_input = file_get_contents('php://input');

// Mock input data
file_put_contents('php://temp', json_encode($data));

include 'api/tickets.php';

$output = ob_get_clean();
echo "API Response: " . $output . "\n";
?>