<?php
function renderHeader($title = 'SpectraHost - Premium Hosting Solutions', $description = 'Professionelle Hosting-Lösungen mit erstklassigem Support und modernster Technologie.') {
?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    
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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition-colors duration-300">
    
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">SpectraHost</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Home</a>
                    <a href="#services" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Services</a>
                    <a href="/contact" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Kontakt</a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/dashboard" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Dashboard</a>
                        <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Abmelden
                        </button>
                    <?php else: ?>
                        <a href="/login" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Anmelden</a>
                        <a href="/register" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Registrieren
                        </a>
                    <?php endif; ?>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700">
            <div class="px-4 py-2 space-y-2">
                <a href="/" class="block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Home</a>
                <a href="#services" class="block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Services</a>
                <a href="/contact" class="block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Kontakt</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
                    <button onclick="logout()" class="w-full text-left py-2 text-red-500 hover:text-red-600">Abmelden</button>
                <?php else: ?>
                    <a href="/login" class="block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Anmelden</a>
                    <a href="/register" class="block py-2 text-blue-500 hover:text-blue-600">Registrieren</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
<?php
}

function renderFooter() {
?>
    <!-- Footer -->
    <footer class="bg-gray-50 dark:bg-gray-800 border-t dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold">SpectraHost</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Professionelle Hosting-Lösungen mit erstklassigem Support und modernster Technologie. 
                        Vertrauen Sie auf über 10 Jahre Erfahrung im Hosting-Bereich.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-500 transition-colors">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-500 transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-500 transition-colors">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Services -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Services</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">Webhosting</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">VPS Server</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">Game Server</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">Domains</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="/contact" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">Kontakt</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">Dokumentation</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">FAQ</a></li>
                        <li><a href="/impressum" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors">Impressum</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t dark:border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-600 dark:text-gray-400">
                    © <?php echo date('Y'); ?> SpectraHost. Alle Rechte vorbehalten.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="/js/main.js"></script>
</body>
</html>
<?php
}
?>