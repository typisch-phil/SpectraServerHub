<?php
session_start();
$_SESSION['user_id'] = 9; // Test user ID
$_SESSION['user_email'] = 'test@spectrahost.de';
echo "Session created for test user. <a href='/dashboard/support'>Go to Support</a>";
?>