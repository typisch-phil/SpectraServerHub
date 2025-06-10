<?php
// Dashboard Header Layout - Einheitlicher Header für alle Dashboard-Seiten
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

require_once __DIR__ . '/database.php';
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get current user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    header("Location: /login");
    exit;
}

function renderDashboardLayout($title, $current_page = 'dashboard', $content_callback = null) {
    global $user;
?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="SpectraHost Dashboard - Verwalten Sie Ihre Services">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            750: '#374151',
                            850: '#1f2937'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">

<div class="min-h-screen bg-gray-900">
    <!-- Dashboard Navigation -->
    <nav class="bg-gray-800 shadow-lg border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost Dashboard</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="<?php echo $current_page === 'dashboard' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-300 hover:text-white'; ?> px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="<?php echo $current_page === 'services' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-300 hover:text-white'; ?> px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="<?php echo $current_page === 'billing' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-300 hover:text-white'; ?> px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="<?php echo $current_page === 'support' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-300 hover:text-white'; ?> px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-300">
                        Guthaben: <span class="font-bold text-green-400">€<?php echo number_format($user['balance'] ?? 0, 2); ?></span>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-300 hover:text-white focus:outline-none">
                            <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium"><?php echo strtoupper(substr($user['email'] ?? 'U', 0, 1)); ?></span>
                            </div>
                            <span class="text-sm"><?php echo htmlspecialchars($user['email'] ?? 'Benutzer'); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <a href="/dashboard/profile" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profil bearbeiten</a>
                            <a href="/dashboard/settings" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Einstellungen</a>
                            <div class="border-t border-gray-700"></div>
                            <a href="/logout" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Abmelden</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php 
        if ($content_callback && is_callable($content_callback)) {
            $content_callback();
        }
        ?>
    </div>
</div>

</body>
</html>
<?php
}
?>