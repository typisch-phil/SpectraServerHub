<?php
require_once '../includes/layout.php';
require_once '../includes/auth.php';

// Require authentication
$auth->requireLogin();
$user = $auth->getCurrentUser();

renderHeader('Dashboard - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Willkommen zurück, <?php echo htmlspecialchars($user['first_name']); ?>!
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-server text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Aktive Services</p>
                        <p class="text-2xl font-bold" id="active-services-count">-</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-credit-card text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Offene Rechnungen</p>
                        <p class="text-2xl font-bold" id="pending-orders-count">-</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-ticket-alt text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Support Tickets</p>
                        <p class="text-2xl font-bold" id="support-tickets-count">-</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-calendar text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Nächste Zahlung</p>
                        <p class="text-lg font-bold" id="next-payment">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Services -->
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold">Meine Services</h2>
                            <a href="/order" class="btn-primary text-sm">
                                <i class="fas fa-plus mr-1"></i> Neuer Service
                            </a>
                        </div>
                    </div>
                    
                    <div id="services-list">
                        <div class="text-center py-8">
                            <div class="loading mx-auto mb-4"></div>
                            <p class="text-gray-500">Services werden geladen...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="card">
                    <h3 class="text-lg font-semibold mb-4">Schnellzugriff</h3>
                    <div class="space-y-2">
                        <a href="/order" class="block w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-shopping-cart mr-2"></i> Service bestellen
                        </a>
                        <a href="/contact" class="block w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-ticket-alt mr-2"></i> Support Ticket
                        </a>
                        <a href="#" class="block w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-file-alt mr-2"></i> Rechnungen
                        </a>
                        <a href="#" class="block w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-user-cog mr-2"></i> Profil bearbeiten
                        </a>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <h3 class="text-lg font-semibold mb-4">Letzte Bestellungen</h3>
                    <div id="recent-orders">
                        <div class="text-center py-4">
                            <div class="loading mx-auto mb-2"></div>
                            <p class="text-sm text-gray-500">Lädt...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Server Control Modal -->
<div id="serverModal" class="modal hidden">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-panel">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" id="modal-title">Server Verwaltung</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="modal-content">
                <!-- Content will be loaded dynamically -->
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeModal()" class="btn-outline">Abbrechen</button>
                <button id="modal-action-btn" class="btn-primary">Ausführen</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

async function loadDashboardData() {
    try {
        // Load user services
        const servicesResponse = await apiRequest('/api/user/services');
        if (servicesResponse.success) {
            renderUserServices(servicesResponse.services);
            updateStats(servicesResponse.services);
        }

        // Load recent orders
        const ordersResponse = await apiRequest('/api/user/orders');
        if (ordersResponse.success) {
            renderRecentOrders(ordersResponse.orders);
        }

    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showNotification('Fehler beim Laden der Dashboard-Daten', 'error');
    }
}

function renderUserServices(services) {
    const container = document.getElementById('services-list');
    
    if (services.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-server text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Keine Services</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Sie haben noch keine Services bestellt.</p>
                <a href="/order" class="btn-primary">Ersten Service bestellen</a>
            </div>
        `;
        return;
    }

    container.innerHTML = services.map(service => `
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-4">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h4 class="text-lg font-semibold">${service.server_name}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">${service.service_name}</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusClass(service.status)}">${getStatusText(service.status)}</span>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    ${service.status === 'active' ? `
                        <button onclick="controlServer(${service.id}, 'restart')" 
                                class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-sm">
                            <i class="fas fa-redo mr-1"></i> Restart
                        </button>
                        <button onclick="controlServer(${service.id}, 'stop')" 
                                class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                            <i class="fas fa-stop mr-1"></i> Stop
                        </button>
                    ` : service.status === 'suspended' ? `
                        <button onclick="controlServer(${service.id}, 'start')" 
                                class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm">
                            <i class="fas fa-play mr-1"></i> Start
                        </button>
                    ` : ''}
                </div>
            </div>
            
            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">CPU:</span>
                    <span class="font-medium">${service.cpu_cores} Core${service.cpu_cores > 1 ? 's' : ''}</span>
                </div>
                <div>
                    <span class="text-gray-500">RAM:</span>
                    <span class="font-medium">${service.memory_gb} GB</span>
                </div>
                <div>
                    <span class="text-gray-500">Storage:</span>
                    <span class="font-medium">${service.storage_gb} GB</span>
                </div>
                <div>
                    <span class="text-gray-500">Läuft ab:</span>
                    <span class="font-medium">${formatDate(service.expires_at)}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function renderRecentOrders(orders) {
    const container = document.getElementById('recent-orders');
    
    if (orders.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500">Keine Bestellungen</p>';
        return;
    }

    container.innerHTML = orders.slice(0, 5).map(order => `
        <div class="border-b border-gray-200 dark:border-gray-700 pb-3 mb-3 last:border-b-0 last:mb-0">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium text-sm">${order.service_name}</p>
                    <p class="text-xs text-gray-500">${formatDateTime(order.created_at)}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium">${formatCurrency(order.total_amount)}</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusClass(order.status)}">${getStatusText(order.status)}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function updateStats(services) {
    const activeServices = services.filter(s => s.status === 'active').length;
    const pendingOrders = services.filter(s => s.status === 'pending').length;
    
    document.getElementById('active-services-count').textContent = activeServices;
    document.getElementById('pending-orders-count').textContent = pendingOrders;
    document.getElementById('support-tickets-count').textContent = '0'; // TODO: Implement
    
    // Find next expiration date
    const activeSvcs = services.filter(s => s.status === 'active');
    if (activeSvcs.length > 0) {
        const nextExpiry = activeSvcs.reduce((earliest, service) => {
            return new Date(service.expires_at) < new Date(earliest.expires_at) ? service : earliest;
        });
        document.getElementById('next-payment').textContent = formatDate(nextExpiry.expires_at);
    } else {
        document.getElementById('next-payment').textContent = '-';
    }
}

async function controlServer(serviceId, action) {
    const actionTexts = {
        'start': 'starten',
        'stop': 'stoppen',
        'restart': 'neustarten'
    };
    
    if (!confirm(`Server wirklich ${actionTexts[action]}?`)) {
        return;
    }
    
    try {
        const result = await apiRequest(`/api/servers/${serviceId}/${action}`, 'POST');
        showNotification(result.message, 'success');
        
        // Reload services after action
        setTimeout(() => {
            loadDashboardData();
        }, 2000);
        
    } catch (error) {
        showNotification(error.message, 'error');
    }
}
</script>

<?php renderFooter(); ?>