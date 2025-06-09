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

$title = 'Rechnungsverwaltung - SpectraHost Admin';
$description = 'Verwalten Sie Rechnungen und Zahlungen';
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
                        <a href="/admin/invoices" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Rechnungen</a>
                        <a href="/admin/integrations" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Integrationen</a>
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
        <div class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Rechnungsverwaltung</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Rechnungen und Zahlungen</p>
            </div>
            <button onclick="openCreateInvoiceModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>Neue Rechnung erstellen
            </button>
        </div>

        <!-- Statistics Cards -->
        <div id="statisticsCards" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Loading placeholders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <i class="fas fa-euro-sign text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Gesamtumsatz</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">Lade...</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bezahlt</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">Lade...</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ausstehend</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">Lade...</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <i class="fas fa-file-invoice text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Anzahl Rechnungen</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">Lade...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Status</option>
                        <option value="pending">Ausstehend</option>
                        <option value="paid">Bezahlt</option>
                        <option value="failed">Fehlgeschlagen</option>
                        <option value="cancelled">Storniert</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zahlungsart</label>
                    <select id="methodFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Arten</option>
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Stripe</option>
                        <option value="manual">Manuell</option>
                        <option value="bank_transfer">Überweisung</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Von Datum</label>
                    <input type="date" id="startDateFilter" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bis Datum</label>
                    <input type="date" id="endDateFilter" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="flex items-end space-x-2">
                    <button onclick="filterInvoices()" class="btn-primary">
                        <i class="fas fa-search mr-1"></i>Filtern
                    </button>
                    <button onclick="resetFilters()" class="btn-outline">
                        <i class="fas fa-times mr-1"></i>Zurücksetzen
                    </button>
                </div>
                <div class="flex items-end justify-end">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span id="invoicesCount">Lade...</span> Rechnungen gefunden
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Rechnung
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Kunde
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Service
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Betrag
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Zahlungsart
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Datum
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Loading row -->
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex justify-center items-center">
                                    <div class="loading mr-3"></div>
                                    <span class="text-gray-500 dark:text-gray-400">Rechnungen werden geladen...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Invoice Modal -->
<div id="invoiceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Rechnung erstellen</h2>
            </div>
            
            <form id="invoiceForm" class="p-6 space-y-6">
                <input type="hidden" id="invoiceId" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kunde *</label>
                        <select id="userId" name="user_id" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Kunde wählen...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service</label>
                        <select id="serviceId" name="service_id" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Kein Service</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Betrag (€) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Währung</label>
                        <select id="currency" name="currency" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select id="status" name="status" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="pending">Ausstehend</option>
                            <option value="paid">Bezahlt</option>
                            <option value="failed">Fehlgeschlagen</option>
                            <option value="cancelled">Storniert</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zahlungsart</label>
                        <select id="paymentMethod" name="payment_method" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="manual">Manuell</option>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                            <option value="bank_transfer">Überweisung</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zahlungs-ID</label>
                        <input type="text" id="paymentId" name="payment_id" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeInvoiceModal()" class="btn-outline">Abbrechen</button>
                    <button type="submit" id="submitButton" class="btn-primary">Erstellen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Rechnung stornieren</h3>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400" id="confirmMessage">
                        Sind Sie sicher, dass Sie diese Rechnung stornieren möchten?
                    </p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeConfirmModal()" class="btn-outline">Abbrechen</button>
                    <button type="button" onclick="confirmAction()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">Stornieren</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentInvoiceId = null;
let invoices = [];
let users = [];
let services = [];

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadInvoices();
    loadUsers();
    loadServices();
});

// API request helper
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
        throw new Error(`HTTP ${response.status}: ${errorText}`);
    }
    
    return await response.json();
}

// Load all invoices
async function loadInvoices() {
    try {
        const response = await fetch('/api/admin/invoices_simple.php', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }
        
        const data = await response.json();
        invoices = data.invoices || [];
        
        renderInvoices(invoices);
        updateStatistics(data.statistics || {});
        updateInvoicesCount(invoices.length);
        
    } catch (error) {
        console.error('Error loading invoices:', error);
        showError('Fehler beim Laden der Rechnungen: ' + error.message);
        
        // Show fallback table
        document.getElementById('invoicesTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <div class="text-red-500 dark:text-red-400">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Fehler beim Laden der Rechnungen</p>
                        <p class="text-sm text-gray-500 mt-1">${error.message}</p>
                    </div>
                </td>
            </tr>
        `;
    }
}

// Load users for dropdown
async function loadUsers() {
    try {
        const response = await fetch('/api/admin/users_simple.php', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            users = data.users || [];
            
            const userSelect = document.getElementById('userId');
            userSelect.innerHTML = '<option value="">Kunde wählen...</option>';
            
            users.forEach(user => {
                userSelect.innerHTML += `<option value="${user.id}">${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)} (${escapeHtml(user.email)})</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Load services for dropdown
async function loadServices() {
    try {
        const response = await fetch('/api/admin/services_simple.php', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            services = data.services || [];
            
            const serviceSelect = document.getElementById('serviceId');
            serviceSelect.innerHTML = '<option value="">Kein Service</option>';
            
            services.forEach(service => {
                serviceSelect.innerHTML += `<option value="${service.id}">${escapeHtml(service.name)} (€${parseFloat(service.price).toFixed(2)})</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading services:', error);
    }
}

// Render invoices table
function renderInvoices(invoicesToRender) {
    const tbody = document.getElementById('invoicesTableBody');
    
    if (invoicesToRender.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <div class="text-gray-500 dark:text-gray-400">
                        <i class="fas fa-file-invoice text-4xl mb-4"></i>
                        <p>Keine Rechnungen gefunden</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = invoicesToRender.map(invoice => {
        const statusBadge = getStatusBadge(invoice.status);
        const methodBadge = getMethodBadge(invoice.payment_method);
        const date = new Date(invoice.created_at).toLocaleDateString('de-DE');
        
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">#${invoice.id}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(invoice.payment_id || 'N/A')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(invoice.first_name)} ${escapeHtml(invoice.last_name)}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(invoice.email)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">${escapeHtml(invoice.service_name || 'Kein Service')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">€${parseFloat(invoice.amount).toFixed(2)}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(invoice.currency)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${methodBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${date}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="editInvoice(${invoice.id})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${invoice.status !== 'paid' && invoice.status !== 'cancelled' ? `
                        <button onclick="cancelInvoice(${invoice.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                            <i class="fas fa-times"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Status and method badge helpers
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 px-2 py-1 rounded-full text-xs font-medium">Ausstehend</span>',
        'paid': '<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full text-xs font-medium">Bezahlt</span>',
        'failed': '<span class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-2 py-1 rounded-full text-xs font-medium">Fehlgeschlagen</span>',
        'cancelled': '<span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 px-2 py-1 rounded-full text-xs font-medium">Storniert</span>'
    };
    return badges[status] || badges['pending'];
}

function getMethodBadge(method) {
    const badges = {
        'paypal': '<span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full text-xs font-medium">PayPal</span>',
        'stripe': '<span class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-2 py-1 rounded-full text-xs font-medium">Stripe</span>',
        'manual': '<span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 px-2 py-1 rounded-full text-xs font-medium">Manuell</span>',
        'bank_transfer': '<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full text-xs font-medium">Überweisung</span>'
    };
    return badges[method] || badges['manual'];
}

// Update statistics cards
function updateStatistics(stats) {
    const cards = document.getElementById('statisticsCards');
    cards.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-euro-sign text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Gesamtumsatz</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">€${parseFloat(stats.total_amount || 0).toFixed(2)}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bezahlt</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">€${parseFloat(stats.paid_amount || 0).toFixed(2)}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ausstehend</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">€${parseFloat(stats.pending_amount || 0).toFixed(2)}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-file-invoice text-purple-600 dark:text-purple-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Anzahl Rechnungen</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">${stats.total_count || 0}</p>
                </div>
            </div>
        </div>
    `;
}

// Update invoices count
function updateInvoicesCount(count) {
    document.getElementById('invoicesCount').textContent = count;
}

// Open create invoice modal
function openCreateInvoiceModal() {
    document.getElementById('modalTitle').textContent = 'Neue Rechnung erstellen';
    document.getElementById('submitButton').textContent = 'Erstellen';
    document.getElementById('invoiceForm').reset();
    document.getElementById('invoiceId').value = '';
    document.getElementById('currency').value = 'EUR';
    document.getElementById('status').value = 'pending';
    document.getElementById('paymentMethod').value = 'manual';
    
    document.getElementById('invoiceModal').classList.remove('hidden');
}

// Edit invoice
async function editInvoice(invoiceId) {
    try {
        const response = await fetch(`/api/admin/invoices_simple.php?id=${invoiceId}`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(await response.text());
        }
        
        const data = await response.json();
        const invoice = data.invoice;
        
        document.getElementById('modalTitle').textContent = 'Rechnung bearbeiten';
        document.getElementById('submitButton').textContent = 'Aktualisieren';
        
        // Fill form
        document.getElementById('invoiceId').value = invoice.id;
        document.getElementById('userId').value = invoice.user_id;
        document.getElementById('serviceId').value = invoice.service_id || '';
        document.getElementById('amount').value = parseFloat(invoice.amount).toFixed(2);
        document.getElementById('currency').value = invoice.currency;
        document.getElementById('status').value = invoice.status;
        document.getElementById('paymentMethod').value = invoice.payment_method;
        document.getElementById('paymentId').value = invoice.payment_id || '';
        
        document.getElementById('invoiceModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error loading invoice:', error);
        showError('Fehler beim Laden der Rechnung: ' + error.message);
    }
}

// Close invoice modal
function closeInvoiceModal() {
    document.getElementById('invoiceModal').classList.add('hidden');
}

// Handle invoice form submission
document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const invoiceData = {};
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            invoiceData[key] = value;
        }
    }
    
    try {
        const isEdit = invoiceData.id && invoiceData.id !== '';
        const method = isEdit ? 'PUT' : 'POST';
        const url = '/api/admin/invoices_simple.php';
        
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(invoiceData)
        });
        
        if (!response.ok) {
            throw new Error(await response.text());
        }
        
        const result = await response.json();
        
        closeInvoiceModal();
        showSuccess(result.message || 'Rechnung erfolgreich gespeichert');
        await loadInvoices();
        
    } catch (error) {
        console.error('Error saving invoice:', error);
        showError('Fehler beim Speichern: ' + error.message);
    }
});

// Cancel invoice
function cancelInvoice(invoiceId) {
    currentInvoiceId = invoiceId;
    const invoice = invoices.find(i => i.id === invoiceId);
    
    document.getElementById('confirmMessage').textContent = 
        `Sind Sie sicher, dass Sie die Rechnung #${invoice.id} stornieren möchten?`;
    
    document.getElementById('confirmModal').classList.remove('hidden');
}

// Confirm action
async function confirmAction() {
    if (!currentInvoiceId) return;
    
    try {
        const response = await fetch('/api/admin/invoices_simple.php', {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: currentInvoiceId })
        });
        
        if (!response.ok) {
            throw new Error(await response.text());
        }
        
        closeConfirmModal();
        showSuccess('Rechnung erfolgreich storniert');
        await loadInvoices();
    } catch (error) {
        console.error('Error cancelling invoice:', error);
        showError('Fehler beim Stornieren: ' + error.message);
    }
}

// Close confirm modal
function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    currentInvoiceId = null;
}

// Filter invoices
async function filterInvoices() {
    const status = document.getElementById('statusFilter').value;
    const method = document.getElementById('methodFilter').value;
    const startDate = document.getElementById('startDateFilter').value;
    const endDate = document.getElementById('endDateFilter').value;
    
    try {
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (method) params.append('method', method);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        
        const url = '/api/admin/invoices_simple.php' + (params.toString() ? '?' + params.toString() : '');
        const response = await fetch(url, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(await response.text());
        }
        
        const data = await response.json();
        invoices = data.invoices || [];
        
        renderInvoices(invoices);
        updateStatistics(data.statistics || {});
        updateInvoicesCount(invoices.length);
    } catch (error) {
        console.error('Error filtering invoices:', error);
        showError('Fehler beim Filtern: ' + error.message);
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('methodFilter').value = '';
    document.getElementById('startDateFilter').value = '';
    document.getElementById('endDateFilter').value = '';
    loadInvoices();
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    alert('Erfolg: ' + message);
}

function showError(message) {
    alert('Fehler: ' + message);
}

function logout() {
    window.location.href = '/logout';
}

</script>

<?php renderFooter(); ?>