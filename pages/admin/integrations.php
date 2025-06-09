<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

$title = 'Integrationen - SpectraHost Admin';
$description = 'Verwalten Sie externe Integrationen und API-Verbindungen';
renderHeader($title, $description);
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/admin" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Admin Panel</a>
                        <a href="/admin/users" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Benutzer</a>
                        <a href="/admin/tickets" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Tickets</a>
                        <a href="/admin/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Services</a>
                        <a href="/admin/invoices" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Rechnungen</a>
                        <a href="/admin/integrations" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Integrationen</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-user mr-2"></i>Zum Dashboard
                    </a>
                    <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Integrationen</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie externe API-Verbindungen und Integrationen</p>
        </div>

        <!-- Integration Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Proxmox VE Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-server text-orange-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Proxmox VE</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Verbunden
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Automatische Server-Verwaltung und VM-Bereitstellung</p>
                <div class="flex space-x-2">
                    <button onclick="configureProxmox()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testProxmox()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- Mollie Payment Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-credit-card text-blue-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mollie</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Aktiv
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Zahlungsverarbeitung und Abonnement-Management</p>
                <div class="flex space-x-2">
                    <button onclick="configureMollie()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testMollie()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- Email SMTP Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-green-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">E-Mail SMTP</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Konfiguration
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">E-Mail-Versand für Benachrichtigungen und Rechnungen</p>
                <div class="flex space-x-2">
                    <button onclick="configureEmail()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testEmail()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- Backup Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-cloud-upload-alt text-purple-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cloud Backup</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        Nicht verbunden
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Automatische Backups für Kundendaten</p>
                <div class="flex space-x-2">
                    <button onclick="configureBackup()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Einrichten
                    </button>
                    <button onclick="testBackup()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm" disabled>
                        Testen
                    </button>
                </div>
            </div>

            <!-- Monitoring Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-red-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Server Monitoring</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Aktiv
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Überwachung der Server-Performance und Verfügbarkeit</p>
                <div class="flex space-x-2">
                    <button onclick="configureMonitoring()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testMonitoring()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- DNS Management Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-globe text-indigo-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">DNS Management</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Teilweise
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Automatische DNS-Zonenverwaltung für Domains</p>
                <div class="flex space-x-2">
                    <button onclick="configureDNS()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testDNS()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>
        </div>

        <!-- API Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">API Aktivitätslogs</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Mollie Webhook empfangen</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Zahlung #12345 erfolgreich verarbeitet</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">vor 2 Minuten</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-server text-blue-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Proxmox VM erstellt</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">VM-ID: 201 für Kunde kunde@test.de</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">vor 15 Minuten</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">E-Mail Zustellung fehlgeschlagen</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">SMTP Verbindung zu server.example.com unterbrochen</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">vor 1 Stunde</span>
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <button onclick="loadMoreLogs()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Weitere Logs laden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function configureProxmox() {
            alert('Proxmox VE Konfiguration öffnen');
        }

        function testProxmox() {
            alert('Proxmox VE Verbindung testen');
        }

        function configureMollie() {
            alert('Mollie Zahlungseinstellungen öffnen');
        }

        function testMollie() {
            alert('Mollie API-Verbindung testen');
        }

        function configureEmail() {
            alert('E-Mail SMTP-Einstellungen konfigurieren');
        }

        function testEmail() {
            alert('Test-E-Mail senden');
        }

        function configureBackup() {
            alert('Cloud Backup einrichten');
        }

        function testBackup() {
            alert('Backup-Verbindung testen');
        }

        function configureMonitoring() {
            alert('Monitoring-System konfigurieren');
        }

        function testMonitoring() {
            alert('Monitoring-Verbindung testen');
        }

        function configureDNS() {
            alert('DNS Management konfigurieren');
        }

        function testDNS() {
            alert('DNS API-Verbindung testen');
        }

        function loadMoreLogs() {
            alert('Weitere API-Logs laden');
        }

        function logout() {
            window.location.href = '/api/logout';
        }

        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.classList.remove('dark', 'light');
            html.classList.add(newTheme);
            
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const themeToggle = document.getElementById('theme-toggle');
            const isDark = document.documentElement.classList.contains('dark');
            themeToggle.innerHTML = isDark 
                ? '<i class="fas fa-sun text-yellow-500"></i>'
                : '<i class="fas fa-moon text-gray-600"></i>';
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(savedTheme);
            updateThemeIcon();
            
            // Add event listener to theme toggle button
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
        });
    </script>
</div>

<?php renderFooter(); ?>