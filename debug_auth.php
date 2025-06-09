<?php
require_once 'includes/database.php';

$email = 'kunde@test.de';
$password = 'kunde123';

// Test direct database query
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Password hash: " . $user['password'] . "\n";
    echo "Password verification: " . (password_verify($password, $user['password']) ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "User not found\n";
}
?>