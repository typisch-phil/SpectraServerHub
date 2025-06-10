<?php
require_once __DIR__ . '/includes/database.php';

$email = 'test@spectrahost.de';
$password = 'test123';

echo "Testing with email: $email\n";
echo "Testing with password: $password\n";

try {
    $database = Database::getInstance();
    $connection = $database->getConnection();
    
    $stmt = $connection->prepare("SELECT id, email, password, first_name, last_name, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User found: " . ($user ? 'YES' : 'NO') . "\n";
    
    if ($user) {
        echo "Email: " . $user['email'] . "\n";
        echo "Password hash: " . $user['password'] . "\n";
        echo "Password verify: " . (password_verify($password, $user['password']) ? 'SUCCESS' : 'FAILED') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>