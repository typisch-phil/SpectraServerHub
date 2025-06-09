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
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 'n' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'vor ' . implode(', ', $string) : 'gerade eben';
}

function getSupportStatus() {
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT setting_value FROM support_settings WHERE setting_key = 'support_online'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['setting_value'] : true;
    } catch (Exception $e) {
        return true; // Default to online if can't determine
    }
}

function getSupportHours() {
    $defaultHours = [
        'monday' => ['start' => '09:00', 'end' => '18:00'],
        'tuesday' => ['start' => '09:00', 'end' => '18:00'],
        'wednesday' => ['start' => '09:00', 'end' => '18:00'],
        'thursday' => ['start' => '09:00', 'end' => '18:00'],
        'friday' => ['start' => '09:00', 'end' => '18:00'],
        'saturday' => ['start' => '10:00', 'end' => '16:00'],
        'sunday' => ['start' => '', 'end' => '']
    ];
    
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        $stmt = $db->query("SELECT setting_key, setting_value FROM support_settings WHERE setting_key LIKE '%_start' OR setting_key LIKE '%_end'");
        $settings = $stmt->fetchAll();
        
        $hours = $defaultHours;
        foreach ($settings as $setting) {
            $parts = explode('_', $setting['setting_key']);
            if (count($parts) == 2) {
                $day = $parts[0];
                $type = $parts[1];
                if (isset($hours[$day])) {
                    $hours[$day][$type] = $setting['setting_value'];
                }
            }
        }
        
        return $hours;
    } catch (Exception $e) {
        return $defaultHours;
    }
}

function isCurrentlyInSupportHours() {
    $hours = getSupportHours();
    $currentDay = strtolower(date('l'));
    $currentTime = date('H:i');
    
    if (!isset($hours[$currentDay]) || empty($hours[$currentDay]['start']) || empty($hours[$currentDay]['end'])) {
        return false; // Closed on this day
    }
    
    $startTime = $hours[$currentDay]['start'];
    $endTime = $hours[$currentDay]['end'];
    
    return $currentTime >= $startTime && $currentTime <= $endTime;
}

?>