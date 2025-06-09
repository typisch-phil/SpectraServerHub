<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Test user login
$email = 'kunde@test.de';
$password = 'kunde123';

$auth = new Auth($db);
$user = $auth->login($email, $password);

if ($user) {
    echo "Login successful for: " . $user['email'] . "\n";
    echo "Session ID: " . session_id() . "\n";
    echo "User in session: " . print_r($_SESSION['user'], true) . "\n";
} else {
    echo "Login failed\n";
}
?>