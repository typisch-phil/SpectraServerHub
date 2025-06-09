<?php
require_once '../includes/session.php';
requireLogin();
requireAdmin();

$db = Database::getInstance();

// Get statistics data
$stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) as total_services FROM services WHERE active = 1");
$stmt->execute();
$totalServices = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) as active_orders FROM user_services WHERE status = 'active'");
$stmt->execute();
$activeOrders = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_revenue FROM payments WHERE status = 'paid'");
$stmt->execute();
$totalRevenue = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken - SpectraHost Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="/admin" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-2"></i>Admin Dashboard
                        </a>
                    </div>
                    <h1 class="text-xl font-bold">Statistiken & Analytics</h1>
                    <div>
                        <a href="/api/logout" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4">
            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Benutzer</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $totalUsers ?></p>
                            <p class="text-xs text-green-600">+12% diesen Monat</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-server text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Aktive Services</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $totalServices ?></p>
                            <p class="text-xs text-green-600">+3% diesen Monat</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shopping-cart text-orange-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Aktive Bestellungen</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $activeOrders ?></p>
                            <p class="text-xs text-green-600">+8% diesen Monat</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-euro-sign text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Gesamtumsatz</p>
                            <p class="text-2xl font-semibold text-gray-900">€<?= number_format($totalRevenue, 2) ?></p>
                            <p class="text-xs text-green-600">+15% diesen Monat</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Revenue Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Umsatz-Entwicklung</h3>
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                        <div class="text-center">
                            <i class="fas fa-chart-line text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500">Chart wird geladen...</p>
                        </div>
                    </div>
                </div>

                <!-- Service Distribution -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Service-Verteilung</h3>
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                        <div class="text-center">
                            <i class="fas fa-chart-pie text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500">Chart wird geladen...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Letzte Aktivitäten</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-plus text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-900">Neuer Benutzer registriert</p>
                                <p class="text-xs text-gray-500">vor 2 Stunden</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shopping-cart text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-900">Neue Service-Bestellung</p>
                                <p class="text-xs text-gray-500">vor 4 Stunden</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-euro-sign text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-900">Zahlung erhalten</p>
                                <p class="text-xs text-gray-500">vor 6 Stunden</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-ticket-alt text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-900">Neues Support-Ticket</p>
                                <p class="text-xs text-gray-500">vor 8 Stunden</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>