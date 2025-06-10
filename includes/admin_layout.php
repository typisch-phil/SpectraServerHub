<?php
function renderAdminHeader($title, $description = '') {
?>
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <?php if ($description): ?>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-sidebar a.active {
            @apply bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 border-r-2 border-blue-600;
        }
    </style>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">
    <div class="flex h-full">
        <!-- Sidebar -->
        <div class="w-64 bg-white dark:bg-gray-800 shadow-lg flex-shrink-0">
            <!-- Logo -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <a href="/admin" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">S</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Admin Panel</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="p-4">
                <div class="space-y-2">
                    <a href="/admin" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin') ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="/admin/users" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin/users') ? 'active' : '' ?>">
                        <i class="fas fa-users mr-3"></i>Benutzer
                    </a>
                    <a href="/admin/services" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin/services') ? 'active' : '' ?>">
                        <i class="fas fa-server mr-3"></i>Services
                    </a>
                    <a href="/admin/tickets" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin/tickets') ? 'active' : '' ?>">
                        <i class="fas fa-ticket-alt mr-3"></i>Tickets
                    </a>
                    <a href="/admin/invoices" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin/invoices') ? 'active' : '' ?>">
                        <i class="fas fa-file-invoice mr-3"></i>Rechnungen
                    </a>
                    <a href="/admin/integrations" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin/integrations') ? 'active' : '' ?>">
                        <i class="fas fa-plug mr-3"></i>Integrationen
                    </a>
                    <a href="/admin/statistics" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin/statistics') ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar mr-3"></i>Statistiken
                    </a>
                </div>

                <!-- User Actions -->
                <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="/dashboard" class="admin-nav-item">
                        <i class="fas fa-user mr-3"></i>Zum Dashboard
                    </a>
                    <a href="/logout" class="admin-nav-item text-red-500 hover:text-red-600">
                        <i class="fas fa-sign-out-alt mr-3"></i>Abmelden
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($title) ?></h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Angemeldet als: <?= htmlspecialchars($_SESSION['user']['email']) ?>
                        </span>
                        <button onclick="toggleDarkMode()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:block"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
<?php
}

function renderAdminFooter() {
?>
            </main>
        </div>
    </div>

    <style>
        .admin-nav-item {
            @apply flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
        }
        .admin-nav-item.active {
            @apply bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300;
        }
    </style>

    <script>
        // API Request helper function for admin pages
        async function apiRequest(url, method = 'GET', data = null) {
            const options = {
                method: method,
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            };
            
            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }
            
            const response = await fetch(url, options);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        }

        // Dark mode toggle
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // Initialize dark mode
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
</body>
</html>
<?php
}
?>