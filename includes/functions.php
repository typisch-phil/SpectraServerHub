<?php

function isLoggedIn() {
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id']);
}

function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = $_SESSION['user'];
    return isset($user['role']) && $user['role'] === 'admin' || 
           isset($user['is_admin']) && $user['is_admin'] == 1;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user = $_SESSION['user'];
    // Map is_admin to role for compatibility
    if (isset($user['is_admin'])) {
        $user['role'] = $user['is_admin'] ? 'admin' : 'user';
    }
    
    return $user;
}

function getSupportHours() {
    return [
        'monday' => '09:00 - 18:00',
        'tuesday' => '09:00 - 18:00',
        'wednesday' => '09:00 - 18:00',
        'thursday' => '09:00 - 18:00',
        'friday' => '09:00 - 18:00',
        'saturday' => '10:00 - 16:00',
        'sunday' => '10:00 - 16:00'
    ];
}

function getSupportStatus() {
    return isCurrentlyInSupportHours() ? 'online' : 'offline';
}

function isCurrentlyInSupportHours() {
    $currentHour = (int)date('H');
    $currentDay = strtolower(date('l'));
    
    $hours = getSupportHours();
    if (!isset($hours[$currentDay])) {
        return false;
    }
    
    $timeRange = $hours[$currentDay];
    if ($timeRange === 'Geschlossen') {
        return false;
    }
    
    list($start, $end) = explode(' - ', $timeRange);
    $startHour = (int)explode(':', $start)[0];
    $endHour = (int)explode(':', $end)[0];
    
    return $currentHour >= $startHour && $currentHour < $endHour;
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'Jahr',
        'm' => 'Monat', 
        'w' => 'Woche',
        'd' => 'Tag',
        'h' => 'Stunde',
        'i' => 'Minute',
        's' => 'Sekunde',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            if ($diff->$k > 1) {
                $v .= $diff->$k > 1 ? 'e' : '';
            }
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'vor ' . implode(', ', $string) : 'gerade eben';
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessage($type) {
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

function hasFlashMessage($type) {
    return isset($_SESSION['flash'][$type]);
}

?>