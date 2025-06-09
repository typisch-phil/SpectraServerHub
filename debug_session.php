<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'user_id_set' => isset($_SESSION['user_id']),
    'user_data' => $_SESSION['user'] ?? null
]);
?>