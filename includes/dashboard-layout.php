<?php
require_once __DIR__ . '/database.php';

// Authentifizierungsfunktionen
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $db;
    if (!$db) {
        return null;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

function startDashboardPage($pageTitle = 'Dashboard', $currentPage = 'dashboard') {
    renderDashboardHeader($pageTitle . ' - SpectraHost Dashboard');
    renderDashboardNavigation($currentPage);
}

function endDashboardPage() {
    renderDashboardFooter();
}

function renderDashboardHeader($title = 'Dashboard - SpectraHost', $description = 'SpectraHost Dashboard - Verwalten Sie Ihre Services') {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%233b82f6'/><stop offset='100%' stop-color='%236366f1'/></linearGradient></defs><rect width='32' height='32' rx='6' fill='url(%23g)'/><text x='16' y='22' text-anchor='middle' fill='white' font-family='Arial' font-size='18' font-weight='bold'>S</text></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
<?php
}

function renderDashboardNavigation($currentPage = 'dashboard') {
    $user = getCurrentUser();
    if (!$user) return;
?>
    <!-- Dashboard Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- Logo -->
                    <a href="/dashboard" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost</span>
                    </a>
                    
                    <!-- Navigation Links -->
                    <div class="hidden md:flex ml-10 space-x-8">
                        <a href="/dashboard" class="<?php echo $currentPage === 'dashboard' ? 'text-blue-400 border-blue-400' : 'text-gray-300 hover:text-white'; ?> border-b-2 <?php echo $currentPage === 'dashboard' ? 'border-blue-400' : 'border-transparent'; ?> px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="/dashboard/services" class="<?php echo $currentPage === 'services' ? 'text-blue-400 border-blue-400' : 'text-gray-300 hover:text-white'; ?> border-b-2 <?php echo $currentPage === 'services' ? 'border-blue-400' : 'border-transparent'; ?> px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                            <i class="fas fa-server mr-2"></i>Services
                        </a>
                        <a href="/dashboard/billing" class="<?php echo $currentPage === 'billing' ? 'text-blue-400 border-blue-400' : 'text-gray-300 hover:text-white'; ?> border-b-2 <?php echo $currentPage === 'billing' ? 'border-blue-400' : 'border-transparent'; ?> px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                            <i class="fas fa-credit-card mr-2"></i>Rechnungen
                        </a>
                        <a href="/dashboard/support" class="<?php echo $currentPage === 'support' ? 'text-blue-400 border-blue-400' : 'text-gray-300 hover:text-white'; ?> border-b-2 <?php echo $currentPage === 'support' ? 'border-blue-400' : 'border-transparent'; ?> px-1 pt-1 pb-4 text-sm font-medium transition-colors">
                            <i class="fas fa-life-ring mr-2"></i>Support
                        </a>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-3">
                        <span class="text-sm text-gray-300">Willkommen, <?php echo htmlspecialchars($user['first_name'] ?? $user['email']); ?></span>
                        <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-300 text-sm"></i>
                        </div>
                    </div>
                    
                    <!-- Logout Button -->
                    <button onclick="logout()" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                    
                    <!-- Mobile menu button -->
                    <button class="md:hidden text-gray-300 hover:text-white" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden bg-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/dashboard" class="<?php echo $currentPage === 'dashboard' ? 'bg-gray-600 text-white' : 'text-gray-300 hover:bg-gray-600 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="/dashboard/services" class="<?php echo $currentPage === 'services' ? 'bg-gray-600 text-white' : 'text-gray-300 hover:bg-gray-600 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-server mr-2"></i>Services
                </a>
                <a href="/dashboard/billing" class="<?php echo $currentPage === 'billing' ? 'bg-gray-600 text-white' : 'text-gray-300 hover:bg-gray-600 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-credit-card mr-2"></i>Rechnungen
                </a>
                <a href="/dashboard/support" class="<?php echo $currentPage === 'support' ? 'bg-gray-600 text-white' : 'text-gray-300 hover:bg-gray-600 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-life-ring mr-2"></i>Support
                </a>
            </div>
        </div>
    </nav>
<?php
}

function renderDashboardFooter() {
?>
    <script>
        // Mobile menu and navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Update navigation based on login status
            updateNavigation();
            
            // Logout function
            window.logout = function() {
                fetch('/api/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).then(() => {
                    window.location.href = '/';
                }).catch(() => {
                    window.location.href = '/api/logout.php';
                });
            };
        });
        
        // Check login status and update navigation
        async function updateNavigation() {
            try {
                const response = await fetch('/api/user/status.php');
                if (!response.ok) return;
                
                const data = await response.json();
                // Navigation updates for dashboard could go here
            } catch (error) {
                console.log('Could not check login status');
            }
        }

        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }
        
        // Mobile services dropdown
        function toggleMobileServices() {
            const menu = document.getElementById('mobile-services-menu');
            const icon = document.getElementById('mobile-services-icon');
            
            if (menu && icon) {
                menu.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        }
    </script>
</body>
</html>
<?php
}
?>