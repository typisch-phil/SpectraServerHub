<?php
require_once 'includes/database.php';

// Create test user with proper password hash
$email = 'kunde@test.de';
$password = 'kunde123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if user exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    // Update existing user
    $stmt = $db->prepare("UPDATE users SET password = ?, first_name = ?, last_name = ?, role = ?, balance = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, 'Max', 'Mustermann', 'user', 50.00, $email]);
    echo "Updated existing user: $email\n";
} else {
    // Insert new user
    $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, role, balance) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$email, $hashedPassword, 'Max', 'Mustermann', 'user', 50.00]);
    echo "Created new user: $email\n";
}

// Verify the user was created/updated
$stmt = $db->prepare("SELECT id, email, first_name, last_name, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "User verification successful:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    
    // Test password verification
    $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $userWithPassword = $stmt->fetch();
    echo "Password verification: " . (password_verify($password, $userWithPassword['password']) ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "Error: User not found after creation\n";
}
?>