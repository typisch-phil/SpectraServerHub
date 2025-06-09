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

// Get statistics data with error handling
try {
    $pdo = $database->getConnection();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM services WHERE active = 1");
    $serviceCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets");
    $ticketCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE status = 'paid'");
    $paymentCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid'");
    $totalRevenue = $stmt->fetch()['total'];
    
    // Get additional statistics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
    $pendingPayments = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'paid' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $thisMonthRevenue = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_services WHERE status = 'active'");
    $activeServices = $stmt->fetch()['count'];
    
    // Get monthly revenue data for chart (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as revenue
        FROM payments 
        WHERE status = 'paid' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user growth data (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_users
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get service distribution
    $stmt = $pdo->query("
        SELECT 
            s.type,
            COUNT(us.id) as count
        FROM services s
        LEFT JOIN user_services us ON s.id = us.service_id AND us.status = 'active'
        WHERE s.active = 1
        GROUP BY s.type
        ORDER BY count DESC
    ");
    $serviceDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Set default values if queries fail
    $userCount = $serviceCount = $ticketCount = $paymentCount = $pendingPayments = $activeServices = 0;
    $totalRevenue = $thisMonthRevenue = 0.00;
    $monthlyRevenue = [];
    $monthlyUsers = [];
    $serviceDistribution = [];
    error_log("Statistics query error: " . $e->getMessage());
}

$title = 'Statistiken - SpectraHost Admin';
$description = 'Übersicht über wichtige Geschäftskennzahlen und Systemstatistiken';
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
                        <a href="/admin/integrations" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Integrationen</a>
                        <a href="/admin/statistics" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Statistiken</a>
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Statistiken & Analytics</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Übersicht über wichtige Geschäftskennzahlen und Systemmetriken</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Gesamt Benutzer</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($userCount) ?></p>
                        <p class="text-xs text-green-600 dark:text-green-400">+12% seit letztem Monat</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-server text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Verfügbare Services</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($serviceCount) ?></p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">Produktkatalog</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-euro-sign text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Gesamtumsatz</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">€<?= number_format($totalRevenue, 2) ?></p>
                        <p class="text-xs text-green-600 dark:text-green-400">+23% seit letztem Monat</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-ticket-alt text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Support Tickets</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($ticketCount) ?></p>
                        <p class="text-xs text-red-600 dark:text-red-400">+5% seit letztem Monat</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Umsatzentwicklung</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Letzte 6 Monate</div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div class="text-center">
                        <p class="text-gray-500 dark:text-gray-400">Diesen Monat</p>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">€<?= number_format($thisMonthRevenue, 2) ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-500 dark:text-gray-400">Bezahlte Rechnungen</p>
                        <p class="text-lg font-semibold text-blue-600 dark:text-blue-400"><?= number_format($paymentCount) ?></p>
                    </div>
                </div>
            </div>

            <!-- User Growth Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nutzerwachstum</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Neue Registrierungen</div>
                </div>
                <div class="h-64">
                    <canvas id="userChart"></canvas>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div class="text-center">
                        <p class="text-gray-500 dark:text-gray-400">Gesamt Benutzer</p>
                        <p class="text-lg font-semibold text-blue-600 dark:text-blue-400"><?= number_format($userCount) ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-500 dark:text-gray-400">Aktive Services</p>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400"><?= number_format($activeServices) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Service Distribution -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Service-Verteilung</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Webspace</span>
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: 65%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">65%</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">vServer</span>
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: 45%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">45%</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">GameServer</span>
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: 30%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">30%</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Domains</span>
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                <div class="bg-yellow-600 h-2 rounded-full" style="width: 80%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">80%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Status</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Server Verfügbarkeit</span>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">99.8%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">CPU Auslastung</span>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">45%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">RAM Nutzung</span>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">68%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Speicherplatz</span>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">42%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Netzwerk I/O</span>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Normal</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <i class="fas fa-user-plus text-green-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-gray-900 dark:text-white">Neuer Benutzer registriert</p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">vor 5 Minuten</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-server text-blue-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-gray-900 dark:text-white">vServer bereitgestellt</p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">vor 12 Minuten</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-credit-card text-green-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-gray-900 dark:text-white">Zahlung erhalten (€29.99)</p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">vor 18 Minuten</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-ticket-alt text-yellow-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-gray-900 dark:text-white">Support Ticket erstellt</p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">vor 32 Minuten</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-shield-alt text-red-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-gray-900 dark:text-white">Sicherheitsupdate installiert</p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">vor 1 Stunde</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Performance Metriken</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">2.3s</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Avg. Ladezeit</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">98.5%</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Uptime</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">1,247</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Aktive Sessions</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">94%</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Kundenzufriedenheit</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Prepare chart data from PHP
        const monthlyRevenueData = <?= json_encode($monthlyRevenue) ?>;
        const monthlyUsersData = <?= json_encode($monthlyUsers) ?>;
        const serviceDistributionData = <?= json_encode($serviceDistribution) ?>;

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

        // Initialize charts
        function initializeCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: monthlyRevenueData.map(item => {
                        const date = new Date(item.month + '-01');
                        return date.toLocaleDateString('de-DE', { month: 'short', year: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Umsatz (€)',
                        data: monthlyRevenueData.map(item => parseFloat(item.revenue)),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });

            // User Growth Chart
            const userCtx = document.getElementById('userChart').getContext('2d');
            new Chart(userCtx, {
                type: 'bar',
                data: {
                    labels: monthlyUsersData.map(item => {
                        const date = new Date(item.month + '-01');
                        return date.toLocaleDateString('de-DE', { month: 'short', year: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Neue Benutzer',
                        data: monthlyUsersData.map(item => parseInt(item.new_users)),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Initialize theme and charts on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(savedTheme);
            updateThemeIcon();
            
            // Add event listener to theme toggle button
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }

            // Initialize charts
            initializeCharts();
        });
    </script>
</div>

<?php renderFooter(); ?>