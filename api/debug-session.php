<?php
require_once '../includes/session.php';
header('Content-Type: application/json');

startSession();

echo json_encode([
    'session_data' => $_SESSION,
    'session_id' => session_id(),
    'is_logged_in' => isLoggedIn(),
    'current_user' => getCurrentUser()
]);
?>