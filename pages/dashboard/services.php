<?php
// Dashboard Services - wird über index.php geladen
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

renderHeader('Meine Services - Dashboard');
?>

<div class="min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800">Dashboard</h2>
            </div>
            <nav class="mt-6">
                <a href="/dashboard" class="block px-6 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-tachometer-alt mr-3"></i>Übersicht
                </a>
                <a href="/dashboard/services" class="block px-6 py-3 bg-blue-50 text-blue-600 border-r-2 border-blue-600">
                    <i class="fas fa-server mr-3"></i>Meine Services
                </a>
                <a href="/dashboard/orders" class="block px-6 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-shopping-cart mr-3"></i>Bestellungen
                </a>
                <a href="/dashboard/billing" class="block px-6 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-credit-card mr-3"></i>Rechnungen
                </a>
                <a href="/dashboard/support" class="block px-6 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-life-ring mr-3"></i>Support
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Meine Services</h1>
                <p class="text-gray-600 mt-2">Verwalten Sie Ihre aktiven Hosting-Services</p>
            </div>

            <!-- Services Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Webspace Service -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-globe text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="font-semibold text-gray-900">Webspace Pro</h3>
                                <p class="text-sm text-gray-500">webspace-001</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Aktiv</span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Speicher:</span>
                            <span class="font-medium">15 GB / 20 GB</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Domain:</span>
                            <span class="font-medium">beispiel.de</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Läuft ab:</span>
                            <span class="font-medium">15.01.2026</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-blue-700">
                            Verwalten
                        </button>
                        <button class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-200">
                            Verlängern
                        </button>
                    </div>
                </div>

                <!-- VPS Service -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-server text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="font-semibold text-gray-900">VPS Standard</h3>
                                <p class="text-sm text-gray-500">vps-002</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Aktiv</span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">CPU:</span>
                            <span class="font-medium">2 vCPU (45%)</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">RAM:</span>
                            <span class="font-medium">3.2 GB / 4 GB</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 80%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">IP:</span>
                            <span class="font-medium">192.168.1.100</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Läuft ab:</span>
                            <span class="font-medium">28.02.2026</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-green-700">
                            Verwalten
                        </button>
                        <button class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-200">
                            Neustart
                        </button>
                    </div>
                </div>

                <!-- Game Server Service -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-gamepad text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="font-semibold text-gray-900">Minecraft Server</h3>
                                <p class="text-sm text-gray-500">game-003</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Online</span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Spieler:</span>
                            <span class="font-medium">12 / 20</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: 60%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Version:</span>
                            <span class="font-medium">1.20.4</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Uptime:</span>
                            <span class="font-medium">5d 12h</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Läuft ab:</span>
                            <span class="font-medium">10.03.2026</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button class="flex-1 bg-purple-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-purple-700">
                            Verwalten
                        </button>
                        <button class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-200">
                            Neustart
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add New Service -->
            <div class="mt-8">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-plus text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Neuen Service bestellen</h3>
                    <p class="text-gray-600 mb-4">Erweitern Sie Ihr Hosting mit zusätzlichen Services</p>
                    <a href="/products" class="inline-block bg-blue-600 text-white py-2 px-6 rounded-lg font-medium hover:bg-blue-700">
                        Services durchsuchen
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderFooter(); ?>