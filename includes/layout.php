<?php
function renderHeader($title = 'SpectraHost - Premium Hosting Solutions', $description = 'Professionelle Hosting-Lösungen mit erstklassigem Support und modernster Technologie.') {
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
    <meta name="robots" content="index, follow">
    
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
<body class="bg-gray-900 text-white transition-colors duration-300">
    
    <!-- Navigation -->
    <nav class="bg-gray-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="text-gray-300 hover:text-blue-400 transition-colors">Home</a>
                    
                    <!-- Services Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center text-gray-300 hover:text-blue-400 transition-colors">
                            Services
                            <i class="fas fa-chevron-down ml-1 text-sm group-hover:rotate-180 transition-transform"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute left-0 top-full mt-1 w-56 bg-gray-800 rounded-lg shadow-lg border border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="py-2">
                                <a href="/products/webhosting" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-globe text-blue-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Webhosting</div>
                                        <div class="text-xs text-gray-400">WordPress, PHP & mehr</div>
                                    </div>
                                </a>
                                
                                <a href="/products/vps" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-green-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-server text-green-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">vServer / VPS</div>
                                        <div class="text-xs text-gray-400">Root-Zugriff & volle Kontrolle</div>
                                    </div>
                                </a>
                                
                                <a href="/products/gameserver" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-gamepad text-purple-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">GameServer</div>
                                        <div class="text-xs text-gray-400">Minecraft, CS2 & mehr</div>
                                    </div>
                                </a>
                                
                                <a href="/products/domains" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-orange-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-link text-orange-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Domains</div>
                                        <div class="text-xs text-gray-400">.de, .com & 500+ TLDs</div>
                                    </div>
                                </a>
                                
                                <hr class="my-2 border-gray-700">
                                
                                <a href="/order" class="flex items-center px-4 py-3 text-blue-400 hover:bg-blue-900 transition-colors">
                                    <i class="fas fa-shopping-cart mr-3"></i>
                                    <div class="font-medium">Alle Services bestellen</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="/contact" class="text-gray-300 hover:text-blue-400 transition-colors">Kontakt</a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/dashboard" class="text-gray-300 hover:text-blue-400 transition-colors">
                            <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                        </a>
                        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <a href="/admin" class="text-gray-300 hover:text-purple-400 transition-colors">
                                <i class="fas fa-cog mr-1"></i>Admin Panel
                            </a>
                        <?php endif; ?>
                        <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                        </button>
                    <?php else: ?>
                        <a href="/login" class="text-gray-300 hover:text-blue-400 transition-colors">Anmelden</a>
                        <a href="/register" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Registrieren
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="p-2 rounded-lg hover:bg-gray-700 transition-colors text-gray-300">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden bg-gray-800 border-t border-gray-700">
            <div class="px-4 py-2 space-y-2">
                <a href="/" class="block py-2 text-gray-300 hover:text-blue-400">Home</a>
                
                <!-- Mobile Services Dropdown -->
                <div class="py-2">
                    <button onclick="toggleMobileServices()" class="flex items-center justify-between w-full text-left text-gray-300 hover:text-blue-400">
                        Services
                        <i class="fas fa-chevron-down text-sm transition-transform" id="mobile-services-icon"></i>
                    </button>
                    <div id="mobile-services-menu" class="hidden pl-4 mt-2 space-y-2">
                        <a href="/products/webhosting" class="flex items-center py-2 text-gray-400 hover:text-blue-400">
                            <i class="fas fa-globe text-blue-400 mr-2"></i>
                            Webhosting
                        </a>
                        <a href="/products/vps" class="flex items-center py-2 text-gray-400 hover:text-blue-400">
                            <i class="fas fa-server text-green-400 mr-2"></i>
                            vServer / VPS
                        </a>
                        <a href="/products/gameserver" class="flex items-center py-2 text-gray-400 hover:text-blue-400">
                            <i class="fas fa-gamepad text-purple-400 mr-2"></i>
                            GameServer
                        </a>
                        <a href="/products/domains" class="flex items-center py-2 text-gray-400 hover:text-blue-400">
                            <i class="fas fa-link text-orange-400 mr-2"></i>
                            Domains
                        </a>
                        <a href="/order" class="flex items-center py-2 text-blue-400">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Alle Services bestellen
                        </a>
                    </div>
                </div>
                
                <a href="/contact" class="block py-2 text-gray-300 hover:text-blue-400">Kontakt</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="block py-2 text-gray-300 hover:text-blue-400">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                        <a href="/admin" class="block py-2 text-gray-300 hover:text-purple-400">
                            <i class="fas fa-cog mr-1"></i>Admin Panel
                        </a>
                    <?php endif; ?>
                    <button onclick="logout()" class="w-full text-left py-2 text-red-400 hover:text-red-300">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                <?php else: ?>
                    <a href="/login" class="block py-2 text-gray-300 hover:text-blue-400">Anmelden</a>
                    <a href="/register" class="block py-2 text-blue-400 hover:text-blue-300">Registrieren</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
<?php
}

function renderFooter() {
?>
    <!-- Footer -->
    <footer class="bg-gray-800 border-t border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Professionelle Hosting-Lösungen mit erstklassigem Support und modernster Technologie. 
                        Vertrauen Sie auf über 10 Jahre Erfahrung im Hosting-Bereich.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Services -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-white">Services</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">Webhosting</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">VPS Server</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">Game Server</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">Domains</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-white">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="/contact" class="text-gray-400 hover:text-blue-400 transition-colors">Kontakt</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">Dokumentation</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition-colors">FAQ</a></li>
                        <li><a href="/impressum" class="text-gray-400 hover:text-blue-400 transition-colors">Impressum</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    © <?php echo date('Y'); ?> SpectraHost. Alle Rechte vorbehalten.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Inline JavaScript -->
    <script>
        // Mobile menu and navigation
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            // Mobile menu
            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
                
                document.addEventListener('click', function(e) {
                    if (!mobileMenuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                        mobileMenu.classList.add('hidden');
                    }
                });
            }
            
            // Update navigation based on login status
            updateNavigation();
        });
        
        // Check login status and update navigation
        async function updateNavigation() {
            try {
                const response = await fetch('/api/user/status.php');
                const data = await response.json();
                
                const desktopNav = document.querySelector('.hidden.md\\:flex .space-x-8');
                const mobileNav = document.querySelector('#mobile-menu .space-y-2');
                
                if (!desktopNav || !mobileNav) return;
                
                // Clear existing auth-related navigation
                const authElements = desktopNav.querySelectorAll('[href*="login"], [href*="register"], [href*="dashboard"], [href*="admin"], button[onclick*="logout"]');
                authElements.forEach(el => el.remove());
                
                const mobileAuthElements = mobileNav.querySelectorAll('[href*="login"], [href*="register"], [href*="dashboard"], [href*="admin"], button[onclick*="logout"]');
                mobileAuthElements.forEach(el => el.remove());
                
                if (data.isLoggedIn) {
                    // Add authenticated navigation
                    const dashboardLink = document.createElement('a');
                    dashboardLink.href = '/dashboard';
                    dashboardLink.className = 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors';
                    dashboardLink.innerHTML = '<i class="fas fa-tachometer-alt mr-1"></i>Dashboard';
                    
                    if (data.user && data.user.role === 'admin') {
                        const adminLink = document.createElement('a');
                        adminLink.href = '/admin';
                        adminLink.className = 'text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors';
                        adminLink.innerHTML = '<i class="fas fa-cog mr-1"></i>Admin Panel';
                        desktopNav.appendChild(adminLink);
                        
                        const mobileAdminLink = adminLink.cloneNode(true);
                        mobileAdminLink.className = 'block py-2 text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400';
                        mobileNav.appendChild(mobileAdminLink);
                    }
                    
                    const logoutBtn = document.createElement('button');
                    logoutBtn.onclick = logout;
                    logoutBtn.className = 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors';
                    logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i>Abmelden';
                    
                    desktopNav.appendChild(dashboardLink);
                    desktopNav.appendChild(logoutBtn);
                    
                    const mobileDashboardLink = dashboardLink.cloneNode(true);
                    mobileDashboardLink.className = 'block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400';
                    
                    const mobileLogoutBtn = document.createElement('button');
                    mobileLogoutBtn.onclick = logout;
                    mobileLogoutBtn.className = 'w-full text-left py-2 text-red-500 hover:text-red-600';
                    mobileLogoutBtn.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i>Abmelden';
                    
                    mobileNav.appendChild(mobileDashboardLink);
                    mobileNav.appendChild(mobileLogoutBtn);
                } else {
                    // Add guest navigation
                    const loginLink = document.createElement('a');
                    loginLink.href = '/login';
                    loginLink.className = 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors';
                    loginLink.textContent = 'Anmelden';
                    
                    const registerLink = document.createElement('a');
                    registerLink.href = '/register';
                    registerLink.className = 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors';
                    registerLink.textContent = 'Registrieren';
                    
                    desktopNav.appendChild(loginLink);
                    desktopNav.appendChild(registerLink);
                    
                    const mobileLoginLink = loginLink.cloneNode(true);
                    mobileLoginLink.className = 'block py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400';
                    
                    const mobileRegisterLink = registerLink.cloneNode(true);
                    mobileRegisterLink.className = 'block py-2 text-blue-500 hover:text-blue-600';
                    
                    mobileNav.appendChild(mobileLoginLink);
                    mobileNav.appendChild(mobileRegisterLink);
                }
            } catch (error) {
                console.error('Failed to update navigation:', error);
            }
        }

        // Logout function
        async function logout() {
            try {
                const response = await fetch('/api/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                if (response.ok) {
                    window.location.href = '/';
                }
            } catch (error) {
                console.error('Logout failed:', error);
            }
        }
        
        // Mobile services dropdown toggle
        function toggleMobileServices() {
            const menu = document.getElementById('mobile-services-menu');
            const icon = document.getElementById('mobile-services-icon');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }
        
        // Utility functions
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${getAlertClass(type)}`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        function getAlertClass(type) {
            const classes = {
                'success': 'bg-green-100 border border-green-400 text-green-700',
                'error': 'bg-red-100 border border-red-400 text-red-700',
                'warning': 'bg-yellow-100 border border-yellow-400 text-yellow-700',
                'info': 'bg-blue-100 border border-blue-400 text-blue-700'
            };
            return classes[type] || classes.info;
        }
        
        async function apiRequest(endpoint, method = 'GET', data = null) {
            const options = {
                method,
                headers: { 'Content-Type': 'application/json' }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            const response = await fetch(endpoint, options);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.error || 'Request failed');
            }
            
            return result;
        }
        
        function setLoading(element, loading = true) {
            if (loading) {
                element.disabled = true;
                element.originalText = element.textContent;
                element.textContent = 'Lädt...';
            } else {
                element.disabled = false;
                element.textContent = element.originalText || element.textContent;
            }
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        }

        function formatDate(dateString) {
            return new Intl.DateTimeFormat('de-DE', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(dateString));
        }

        function formatDateTime(dateString) {
            return new Intl.DateTimeFormat('de-DE', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(dateString));
        }
        
        function getStatusClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800',
                'pending': 'bg-yellow-100 text-yellow-800',
                'suspended': 'bg-red-100 text-red-800',
                'terminated': 'bg-gray-100 text-gray-800',
                'paid': 'bg-green-100 text-green-800',
                'failed': 'bg-red-100 text-red-800',
                'cancelled': 'bg-gray-100 text-gray-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
        
        function getStatusText(status) {
            const texts = {
                'active': 'Aktiv',
                'pending': 'Ausstehend',
                'suspended': 'Gesperrt',
                'terminated': 'Beendet',
                'paid': 'Bezahlt',
                'failed': 'Fehlgeschlagen',
                'cancelled': 'Storniert'
            };
            return texts[status] || status;
        }
        
        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }
        
        // Close modal with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Close modal when clicking overlay
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });
    </script>
</body>
</html>
<?php
}
?>