<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "Session ID: " . session_id() . "<br>";
echo "Session Data: ";
print_r($_SESSION);
echo "<br>Cookies: ";
print_r($_COOKIE);
echo "<br>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET');
?>