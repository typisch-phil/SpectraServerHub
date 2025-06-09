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

function renderHeader($title = 'SpectraHost', $description = 'Premium Hosting Solutions') {
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"de\" class=\"scroll-smooth\">\n";
    echo "<head>\n";
    echo "    <meta charset=\"UTF-8\">\n";
    echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    echo "    <title>" . htmlspecialchars($title) . "</title>\n";
    echo "    <meta name=\"description\" content=\"" . htmlspecialchars($description) . "\">\n";
    echo "    <meta name=\"robots\" content=\"index, follow\">\n";
    echo "    \n";
    echo "    <!-- Favicon -->\n";
    echo "    <link rel=\"icon\" type=\"image/x-icon\" href=\"data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%233b82f6'/><stop offset='100%' stop-color='%236366f1'/></linearGradient></defs><rect width='32' height='32' rx='6' fill='url(%23g)'/><text x='16' y='22' text-anchor='middle' fill='white' font-family='Arial' font-size='18' font-weight='bold'>S</text></svg>\">\n";
    echo "    \n";
    echo "    <!-- Tailwind CSS -->\n";
    echo "    <script src=\"https://cdn.tailwindcss.com\"></script>\n";
    echo "    <script>\n";
    echo "        tailwind.config = {\n";
    echo "            darkMode: 'class',\n";
    echo "            theme: {\n";
    echo "                extend: {\n";
    echo "                    colors: {\n";
    echo "                        primary: {\n";
    echo "                            50: '#eff6ff',\n";
    echo "                            500: '#3b82f6',\n";
    echo "                            600: '#2563eb',\n";
    echo "                            700: '#1d4ed8',\n";
    echo "                        }\n";
    echo "                    }\n";
    echo "                }\n";
    echo "            }\n";
    echo "        }\n";
    echo "    </script>\n";
    echo "    \n";
    echo "    <!-- Font Awesome -->\n";
    echo "    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n";
    echo "</head>\n";
    echo "<body class=\"bg-gray-50 dark:bg-gray-900\">\n";
}

function renderFooter() {
    echo "</body>\n";
    echo "</html>\n";
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