<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo json_encode([
    'authenticated' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'session_id' => session_id()
]);
?>