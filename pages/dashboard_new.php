<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';

requireLogin();

$title = 'Dashboard - SpectraHost';
$description = 'Verwalten Sie Ihre Services und Bestellungen';
renderHeader($title, $description);

$user_id = $_SESSION['user']['id'];

// Get user balance and stats
$database = Database::getInstance();
$user = getCurrentUser();
$user_id = $user['id'];

try {
    $stmt = $database->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_balance = $stmt->fetchColumn() ?: 0.00;
    
    $stmt = $database->prepare("SELECT COUNT(*) FROM user_services WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_services = $stmt->fetchColumn();
    
    $stmt = $database->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_orders = $stmt->fetchColumn();
    
    $stmt = $database->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status IN ('open', 'answered')");
    $stmt->execute([$user_id]);
    $open_tickets = $stmt->fetchColumn();
} catch (Exception $e) {
    $user_balance = 0.00;
    $active_services = $pending_orders = $open_tickets = 0;
}
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/dashboard" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Dashboard</a>
                        <a href="#" onclick="showSection('services')" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Meine Services</a>
                        <a href="#" onclick="showSection('invoices')" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Rechnungen</a>
                        <a href="#" onclick="showSection('tickets')" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Support</a>
                        <a href="#" onclick="showSection('profile')" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Profil</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Guthaben:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400" id="user-balance"><?= number_format($user_balance, 2) ?> €</span>
                        <button onclick="openModal('addBalanceModal')" class="ml-2 text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">
                            <i class="fas fa-plus mr-1"></i>Aufladen
                        </button>
                    </div>
                    <span class="text-gray-700 dark:text-gray-300">Willkommen, <?= htmlspecialchars($_SESSION['user']['first_name']) ?></span>
                    <?php if ($_SESSION['user']['is_admin']): ?>
                        <a href="/admin" class="btn-outline">Admin Panel</a>
                    <?php endif; ?>
                    <a href="/api/logout" class="btn-outline">Abmelden</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Overview Section -->
        <div id="overview-section">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-server text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktive Services</h3>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $active_services ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-shopping-cart text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Offene Bestellungen</h3>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $pending_orders ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                            <i class="fas fa-ticket-alt text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Offene Tickets</h3>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $open_tickets ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                            <i class="fas fa-wallet text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Guthaben</h3>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= number_format($user_balance, 2) ?> €</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-plus mr-2"></i>Neuen Service bestellen
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Webspace, VPS, Gameserver oder Domain bestellen</p>
                    <a href="/order" class="btn-primary w-full">Jetzt bestellen</a>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-headset mr-2"></i>Support kontaktieren
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Technische Hilfe oder Fragen zu Services</p>
                    <button onclick="openTicketModal()" class="btn-primary w-full">Ticket erstellen</button>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-credit-card mr-2"></i>Guthaben aufladen
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Guthaben für Services und Rechnungen</p>
                    <button onclick="openModal('addBalanceModal')" class="btn-primary w-full">Guthaben hinzufügen</button>
                </div>
            </div>

            <!-- Recent Services -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Meine Services</h3>
                </div>
                <div class="p-6">
                    <div id="recent-services">
                        <div class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                            <p class="text-gray-500 mt-2">Lade Services...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div id="services-section" class="hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Meine Services</h3>
                        <a href="/order" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i>Neuen Service bestellen
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <div id="all-services">
                        <div class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                            <p class="text-gray-500 mt-2">Lade alle Services...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add other sections (invoices, tickets, profile) here -->
    </div>
</div>

<!-- Add Balance Modal -->
<div id="addBalanceModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-panel">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Guthaben aufladen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addBalanceForm" onsubmit="handleAddBalance(event)">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Betrag
                    </label>
                    <div class="relative">
                        <input type="number" name="amount" step="0.01" min="5" max="1000" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md pr-12">
                        <span class="absolute right-3 top-2 text-gray-500">€</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Mindestbetrag: 5,00 € - Maximalbetrag: 1.000,00 €</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Zahlungsmethode
                    </label>
                    <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Bitte wählen...</option>
                        <option value="ideal">iDEAL</option>
                        <option value="creditcard">Kreditkarte</option>
                        <option value="banktransfer">Banküberweisung</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="btn-outline">Abbrechen</button>
                    <button type="submit" id="addBalanceBtn" class="btn-primary">Guthaben aufladen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentSection = 'overview';

document.addEventListener('DOMContentLoaded', function() {
    loadRecentServices();
});

function showSection(section) {
    // Hide all sections
    document.getElementById('overview-section').classList.add('hidden');
    document.getElementById('services-section').classList.add('hidden');
    
    // Show selected section
    if (section === 'overview') {
        document.getElementById('overview-section').classList.remove('hidden');
    } else if (section === 'services') {
        document.getElementById('services-section').classList.remove('hidden');
        if (currentSection !== 'services') {
            loadAllServices();
        }
    }
    
    currentSection = section;
}

async function loadRecentServices() {
    try {
        const response = await apiRequest('/api/user/services?limit=5');
        if (response.success) {
            renderRecentServices(response.services);
        }
    } catch (error) {
        document.getElementById('recent-services').innerHTML = 
            '<p class="text-red-500 text-center">Fehler beim Laden der Services</p>';
    }
}

async function loadAllServices() {
    try {
        const response = await apiRequest('/api/user/services');
        if (response.success) {
            renderAllServices(response.services);
        }
    } catch (error) {
        document.getElementById('all-services').innerHTML = 
            '<p class="text-red-500 text-center">Fehler beim Laden der Services</p>';
    }
}

function renderRecentServices(services) {
    const container = document.getElementById('recent-services');
    
    if (services.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-server text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Noch keine Services</h3>
                <p class="text-gray-500 mb-4">Bestellen Sie Ihren ersten Service und starten Sie durch!</p>
                <a href="/order" class="btn-primary">Service bestellen</a>
            </div>
        `;
        return;
    }
    
    const html = services.slice(0, 3).map(service => `
        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg mb-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg ${getServiceTypeColor(service.type)}">
                    <i class="fas ${getServiceTypeIcon(service.type)} text-white"></i>
                </div>
                <div class="ml-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">${service.server_name}</h4>
                    <p class="text-sm text-gray-500">${service.service_name}</p>
                    ${service.ip_address ? `<p class="text-xs text-gray-400">IP: ${service.ip_address}</p>` : ''}
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="px-2 py-1 text-xs rounded-full ${getStatusClass(service.status)}">
                    ${getStatusText(service.status)}
                </span>
                <button onclick="manageService(${service.id})" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html + (services.length > 3 ? 
        '<div class="text-center mt-4"><button onclick="showSection(\'services\')" class="text-blue-600 hover:text-blue-800">Alle Services anzeigen</button></div>' : 
        '');
}

function renderAllServices(services) {
    const container = document.getElementById('all-services');
    
    if (services.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-server text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Noch keine Services</h3>
                <p class="text-gray-500 mb-4">Bestellen Sie Ihren ersten Service und starten Sie durch!</p>
                <a href="/order" class="btn-primary">Service bestellen</a>
            </div>
        `;
        return;
    }
    
    const html = services.map(service => `
        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg mb-4">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg ${getServiceTypeColor(service.type)}">
                        <i class="fas ${getServiceTypeIcon(service.type)} text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">${service.server_name}</h4>
                        <p class="text-gray-600 dark:text-gray-300">${service.service_name}</p>
                    </div>
                </div>
                <span class="px-3 py-1 text-sm rounded-full ${getStatusClass(service.status)}">
                    ${getStatusText(service.status)}
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                ${service.ip_address ? `
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400">IP-Adresse</label>
                    <p class="font-mono text-sm">${service.ip_address}</p>
                </div>
                ` : ''}
                ${service.os_installed ? `
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400">Betriebssystem</label>
                    <p class="text-sm">${service.os_installed}</p>
                </div>
                ` : ''}
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400">Erstellt</label>
                    <p class="text-sm">${formatDate(service.created_at)}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400">Läuft ab</label>
                    <p class="text-sm">${formatDate(service.expires_at)}</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2">
                ${service.status === 'active' && service.type === 'vps' ? `
                    <button onclick="serverAction(${service.id}, 'start')" class="btn-sm bg-green-600 text-white hover:bg-green-700">
                        <i class="fas fa-play mr-1"></i>Start
                    </button>
                    <button onclick="serverAction(${service.id}, 'stop')" class="btn-sm bg-red-600 text-white hover:bg-red-700">
                        <i class="fas fa-stop mr-1"></i>Stop
                    </button>
                    <button onclick="serverAction(${service.id}, 'restart')" class="btn-sm bg-yellow-600 text-white hover:bg-yellow-700">
                        <i class="fas fa-redo mr-1"></i>Restart
                    </button>
                    <button onclick="resetPassword(${service.id})" class="btn-sm bg-blue-600 text-white hover:bg-blue-700">
                        <i class="fas fa-key mr-1"></i>Passwort zurücksetzen
                    </button>
                ` : ''}
                <button onclick="manageService(${service.id})" class="btn-sm bg-gray-600 text-white hover:bg-gray-700">
                    <i class="fas fa-cog mr-1"></i>Verwalten
                </button>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

function getServiceTypeColor(type) {
    const colors = {
        'webhosting': 'bg-green-600',
        'vps': 'bg-blue-600',
        'gameserver': 'bg-purple-600',
        'domain': 'bg-orange-600'
    };
    return colors[type] || 'bg-gray-600';
}

function getServiceTypeIcon(type) {
    const icons = {
        'webhosting': 'fa-globe',
        'vps': 'fa-server',
        'gameserver': 'fa-gamepad',
        'domain': 'fa-link'
    };
    return icons[type] || 'fa-server';
}

async function serverAction(serviceId, action) {
    try {
        const response = await apiRequest(`/api/user/services/${serviceId}/${action}`, 'POST');
        if (response.success) {
            showNotification(`Server-Aktion "${action}" erfolgreich ausgeführt`, 'success');
            loadAllServices(); // Reload to show updated status
        } else {
            showNotification(response.message || 'Fehler bei Server-Aktion', 'error');
        }
    } catch (error) {
        showNotification('Fehler bei Server-Aktion', 'error');
    }
}

async function resetPassword(serviceId) {
    if (!confirm('Möchten Sie das Root-Passwort wirklich zurücksetzen?')) return;
    
    try {
        const response = await apiRequest(`/api/user/services/${serviceId}/reset-password`, 'POST');
        if (response.success) {
            showNotification('Neues Passwort: ' + response.new_password, 'success');
        } else {
            showNotification(response.message || 'Fehler beim Passwort zurücksetzen', 'error');
        }
    } catch (error) {
        showNotification('Fehler beim Passwort zurücksetzen', 'error');
    }
}

async function handleAddBalance(event) {
    event.preventDefault();
    const btn = document.getElementById('addBalanceBtn');
    setLoading(btn, true);
    
    try {
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);
        
        const response = await apiRequest('/api/user/add-balance', 'POST', data);
        
        if (response.paymentUrl) {
            window.location.href = response.paymentUrl;
        } else {
            showNotification('Guthaben erfolgreich hinzugefügt!', 'success');
            closeModal();
            location.reload(); // Reload to show updated balance
        }
    } catch (error) {
        showNotification(error.message || 'Fehler beim Hinzufügen des Guthabens', 'error');
    }
    
    setLoading(btn, false);
}

function manageService(serviceId) {
    // Implement service management modal
    showNotification('Service-Verwaltung wird geöffnet...', 'info');
}

function openTicketModal() {
    // Implement ticket creation modal
    showNotification('Ticket-Erstellung wird geöffnet...', 'info');
}
</script>

<?php renderFooter(); ?>