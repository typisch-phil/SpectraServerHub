<?php
function renderDashboardNavigation($current_page = 'dashboard') {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return;
    
    // Get user balance
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $user_balance = $stmt->fetchColumn() ?: 0.00;
    
    $nav_items = [
        'dashboard' => ['url' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
        'services' => ['url' => '/dashboard/services', 'label' => 'Meine Services', 'icon' => 'fas fa-server'],
        'billing' => ['url' => '/dashboard/billing', 'label' => 'Billing', 'icon' => 'fas fa-credit-card'],
        'support' => ['url' => '/dashboard/support', 'label' => 'Support', 'icon' => 'fas fa-life-ring'],
        'order' => ['url' => '/order', 'label' => 'Bestellen', 'icon' => 'fas fa-shopping-cart']
    ];
?>
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">SpectraHost</span>
                    </a>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex ml-8 space-x-6">
                        <?php foreach ($nav_items as $key => $item): ?>
                            <a href="<?php echo $item['url']; ?>" 
                               class="flex items-center px-3 py-2 rounded-lg transition-colors <?php echo $current_page === $key 
                                   ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' 
                                   : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400'; ?>">
                                <i class="<?php echo $item['icon']; ?> mr-2"></i>
                                <?php echo $item['label']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Balance Display -->
                    <div class="hidden sm:flex items-center bg-green-50 dark:bg-green-900 px-3 py-1 rounded-lg">
                        <i class="fas fa-wallet text-green-600 dark:text-green-400 mr-2"></i>
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">
                            €<?php echo number_format($user_balance, 2); ?>
                        </span>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>
                        </span>
                        
                        <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                            <a href="/admin" class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded-lg text-sm transition-colors">
                                <i class="fas fa-cog mr-1"></i>Admin
                            </a>
                        <?php endif; ?>
                        
                        <a href="/api/logout" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-sm transition-colors">
                            <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                        </a>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <button class="md:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobileMenu" class="hidden md:hidden pb-4">
                <div class="space-y-2">
                    <?php foreach ($nav_items as $key => $item): ?>
                        <a href="<?php echo $item['url']; ?>" 
                           class="flex items-center px-3 py-2 rounded-lg transition-colors <?php echo $current_page === $key 
                               ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' 
                               : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400'; ?>">
                            <i class="<?php echo $item['icon']; ?> mr-2"></i>
                            <?php echo $item['label']; ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Mobile Balance -->
                    <div class="flex items-center px-3 py-2 bg-green-50 dark:bg-green-900 rounded-lg">
                        <i class="fas fa-wallet text-green-600 dark:text-green-400 mr-2"></i>
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">
                            Guthaben: €<?php echo number_format($user_balance, 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
    </script>
<?php
}

function renderDashboardHeader($title, $description = '') {
?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - SpectraHost</title>
    <?php if ($description): ?>
        <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <?php endif; ?>
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
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen">
<?php
}

function renderDashboardFooter() {
?>
</body>
</html>
<?php
}
?>