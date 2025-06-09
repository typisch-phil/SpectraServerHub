<?php
$password = 'kunde123';
$hash = '$2y$10$wShG8ZDMvEuOIZL6MYdPseN6OIjTS5WZT9fNGJm.sksq.Nu6EuSp.';

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Verification result: " . (password_verify($password, $hash) ? 'true' : 'false') . "\n";
?>