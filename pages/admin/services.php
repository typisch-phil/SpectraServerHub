<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$title = 'Service-Verwaltung - SpectraHost Admin';
$description = 'Verwalten Sie Hosting-Services und Pakete';
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
                        <a href="/admin/services" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Services</a>
                        <a href="/admin/invoices" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Rechnungen</a>
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
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Service-Verwaltung</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Hosting-Services und Pakete</p>
            </div>
            <button onclick="openCreateServiceModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>Neuen Service erstellen
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service-Typ</label>
                    <select id="typeFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Typen</option>
                        <option value="webspace">Webspace</option>
                        <option value="vserver">vServer</option>
                        <option value="gameserver">Gameserver</option>
                        <option value="domain">Domain</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select id="activeFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Status</option>
                        <option value="true">Aktiv</option>
                        <option value="false">Inaktiv</option>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button onclick="filterServices()" class="btn-primary">
                        <i class="fas fa-search mr-1"></i>Filtern
                    </button>
                    <button onclick="resetFilters()" class="btn-outline">
                        <i class="fas fa-times mr-1"></i>Zurücksetzen
                    </button>
                </div>
                <div class="flex items-end justify-end">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span id="servicesCount">Lade...</span> Services gefunden
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Grid -->
        <div id="servicesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading state -->
            <div class="col-span-full flex justify-center py-12">
                <div class="text-center">
                    <div class="loading mx-auto mb-4"></div>
                    <p class="text-gray-500 dark:text-gray-400">Services werden geladen...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Service Modal -->
<div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Service erstellen</h2>
            </div>
            
            <form id="serviceForm" class="p-6 space-y-6">
                <input type="hidden" id="serviceId" name="id">
                
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service-Name *</label>
                        <input type="text" id="serviceName" name="name" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service-Typ *</label>
                        <select id="serviceType" name="type" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Bitte wählen</option>
                            <option value="webspace">Webspace</option>
                            <option value="vserver">vServer</option>
                            <option value="gameserver">Gameserver</option>
                            <option value="domain">Domain</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Beschreibung</label>
                    <textarea id="serviceDescription" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                
                <!-- Pricing and Resources -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preis (€) *</label>
                        <input type="number" id="servicePrice" name="price" step="0.01" min="0" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CPU Kerne</label>
                        <input type="number" id="cpuCores" name="cpu_cores" min="1" value="1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">RAM (GB)</label>
                        <input type="number" id="memoryGb" name="memory_gb" min="1" value="1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Speicher (GB)</label>
                        <input type="number" id="storageGb" name="storage_gb" min="1" value="10"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bandbreite (GB)</label>
                        <input type="number" id="bandwidthGb" name="bandwidth_gb" min="1" value="1000"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="serviceActive" name="active" checked 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktiv</span>
                        </label>
                    </div>
                </div>
                
                <!-- Features -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Features</label>
                    <div class="space-y-2">
                        <div id="featuresContainer">
                            <div class="flex items-center space-x-2 feature-item">
                                <input type="text" placeholder="Feature eingeben..." 
                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <button type="button" onclick="removeFeature(this)" class="text-red-600 hover:text-red-800 p-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="addFeature()" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-plus mr-1"></i>Feature hinzufügen
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeServiceModal()" class="btn-outline">Abbrechen</button>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Service deaktivieren</h3>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400" id="confirmMessage">
                        Sind Sie sicher, dass Sie diesen Service deaktivieren möchten?
                    </p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeConfirmModal()" class="btn-outline">Abbrechen</button>
                    <button type="button" onclick="confirmAction()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">Deaktivieren</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentServiceId = null;
let services = [];

// Load services on page load
document.addEventListener('DOMContentLoaded', function() {
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

// Load all services
async function loadServices() {
    try {
        const response = await apiRequest('/api/admin/services.php');
        services = response.services || [];
        renderServices(services);
        updateServicesCount(services.length);
    } catch (error) {
        console.error('Error loading services:', error);
        showError('Fehler beim Laden der Services: ' + error.message);
    }
}

// Render services grid
function renderServices(servicesToRender) {
    const grid = document.getElementById('servicesGrid');
    
    if (servicesToRender.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <div class="text-gray-500 dark:text-gray-400">
                    <i class="fas fa-server text-4xl mb-4"></i>
                    <p>Keine Services gefunden</p>
                </div>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = servicesToRender.map(service => {
        const typeIcon = getTypeIcon(service.type);
        const typeColor = getTypeColor(service.type);
        const statusBadge = service.active 
            ? '<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full text-xs font-medium">Aktiv</span>'
            : '<span class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-2 py-1 rounded-full text-xs font-medium">Inaktiv</span>';
        
        const features = service.features && Array.isArray(service.features) 
            ? service.features.slice(0, 3).map(feature => `<li class="text-sm text-gray-600 dark:text-gray-400">${escapeHtml(feature)}</li>`).join('')
            : '';
        
        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 ${typeColor} rounded-lg flex items-center justify-center mr-3">
                                <i class="${typeIcon} text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">${escapeHtml(service.name)}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(service.type.charAt(0).toUpperCase() + service.type.slice(1))}</p>
                            </div>
                        </div>
                        ${statusBadge}
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-300 mb-4 text-sm">${escapeHtml(service.description || '')}</p>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">€${parseFloat(service.price).toFixed(2)}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">pro Monat</div>
                    </div>
                    
                    ${features ? `
                    <ul class="space-y-1 mb-4">
                        ${features}
                        ${service.features && service.features.length > 3 ? `<li class="text-sm text-gray-500">+${service.features.length - 3} weitere...</li>` : ''}
                    </ul>
                    ` : ''}
                    
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <div>CPU: ${service.cpu_cores} Kerne</div>
                        <div>RAM: ${service.memory_gb} GB</div>
                        <div>Speicher: ${service.storage_gb} GB</div>
                        <div>Traffic: ${service.bandwidth_gb} GB</div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="editService(${service.id})" class="flex-1 btn-outline text-sm">
                            <i class="fas fa-edit mr-1"></i>Bearbeiten
                        </button>
                        <button onclick="toggleService(${service.id}, ${!service.active})" class="btn-outline text-sm ${service.active ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'}">
                            <i class="fas fa-${service.active ? 'pause' : 'play'} mr-1"></i>${service.active ? 'Deaktivieren' : 'Aktivieren'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Service type helpers
function getTypeIcon(type) {
    const icons = {
        'webspace': 'fas fa-globe',
        'vserver': 'fas fa-server',
        'gameserver': 'fas fa-gamepad',
        'domain': 'fas fa-link'
    };
    return icons[type] || 'fas fa-cube';
}

function getTypeColor(type) {
    const colors = {
        'webspace': 'bg-blue-500',
        'vserver': 'bg-green-500',
        'gameserver': 'bg-purple-500',
        'domain': 'bg-orange-500'
    };
    return colors[type] || 'bg-gray-500';
}

// Update services count
function updateServicesCount(count) {
    document.getElementById('servicesCount').textContent = count;
}

// Open create service modal
function openCreateServiceModal() {
    document.getElementById('modalTitle').textContent = 'Neuen Service erstellen';
    document.getElementById('submitButton').textContent = 'Erstellen';
    document.getElementById('serviceForm').reset();
    document.getElementById('serviceId').value = '';
    
    // Reset features
    const container = document.getElementById('featuresContainer');
    container.innerHTML = `
        <div class="flex items-center space-x-2 feature-item">
            <input type="text" placeholder="Feature eingeben..." 
                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            <button type="button" onclick="removeFeature(this)" class="text-red-600 hover:text-red-800 p-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.getElementById('serviceModal').classList.remove('hidden');
}

// Edit service
async function editService(serviceId) {
    try {
        const response = await apiRequest(`/api/admin/services.php?id=${serviceId}`);
        const service = response.service;
        
        document.getElementById('modalTitle').textContent = 'Service bearbeiten';
        document.getElementById('submitButton').textContent = 'Aktualisieren';
        
        // Fill form
        document.getElementById('serviceId').value = service.id;
        document.getElementById('serviceName').value = service.name;
        document.getElementById('serviceType').value = service.type;
        document.getElementById('serviceDescription').value = service.description || '';
        document.getElementById('servicePrice').value = parseFloat(service.price).toFixed(2);
        document.getElementById('cpuCores').value = service.cpu_cores;
        document.getElementById('memoryGb').value = service.memory_gb;
        document.getElementById('storageGb').value = service.storage_gb;
        document.getElementById('bandwidthGb').value = service.bandwidth_gb;
        document.getElementById('serviceActive').checked = service.active;
        
        // Fill features
        const container = document.getElementById('featuresContainer');
        container.innerHTML = '';
        
        if (service.features && Array.isArray(service.features)) {
            service.features.forEach(feature => {
                addFeature(feature);
            });
        }
        
        if (container.children.length === 0) {
            addFeature();
        }
        
        document.getElementById('serviceModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error loading service:', error);
        showError('Fehler beim Laden des Services: ' + error.message);
    }
}

// Features management
function addFeature(value = '') {
    const container = document.getElementById('featuresContainer');
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 feature-item';
    div.innerHTML = `
        <input type="text" placeholder="Feature eingeben..." value="${escapeHtml(value)}"
               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
        <button type="button" onclick="removeFeature(this)" class="text-red-600 hover:text-red-800 p-2">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeFeature(button) {
    const container = document.getElementById('featuresContainer');
    if (container.children.length > 1) {
        button.closest('.feature-item').remove();
    }
}

// Close service modal
function closeServiceModal() {
    document.getElementById('serviceModal').classList.add('hidden');
}

// Handle service form submission
document.getElementById('serviceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const serviceData = {};
    
    for (let [key, value] of formData.entries()) {
        if (key !== 'features' && value.trim() !== '') {
            serviceData[key] = key === 'active' ? true : value;
        }
    }
    
    // Handle checkbox for active status
    serviceData.active = document.getElementById('serviceActive').checked;
    
    // Collect features
    const featureInputs = document.querySelectorAll('#featuresContainer input[type="text"]');
    const features = Array.from(featureInputs)
        .map(input => input.value.trim())
        .filter(value => value !== '');
    serviceData.features = features;
    
    try {
        const isEdit = serviceData.id && serviceData.id !== '';
        const method = isEdit ? 'PUT' : 'POST';
        const url = '/api/admin/services.php';
        
        const response = await apiRequest(url, method, serviceData);
        
        closeServiceModal();
        showSuccess(response.message || 'Service erfolgreich gespeichert');
        await loadServices();
        
    } catch (error) {
        console.error('Error saving service:', error);
        showError('Fehler beim Speichern: ' + error.message);
    }
});

// Toggle service status
function toggleService(serviceId, newStatus) {
    currentServiceId = serviceId;
    const service = services.find(s => s.id === serviceId);
    const action = newStatus ? 'aktivieren' : 'deaktivieren';
    
    document.getElementById('confirmMessage').textContent = 
        `Sind Sie sicher, dass Sie den Service "${service.name}" ${action} möchten?`;
    
    document.querySelector('#confirmModal button[onclick="confirmAction()"]').textContent = 
        newStatus ? 'Aktivieren' : 'Deaktivieren';
    
    document.getElementById('confirmModal').classList.remove('hidden');
}

// Confirm action
async function confirmAction() {
    if (!currentServiceId) return;
    
    try {
        const service = services.find(s => s.id === currentServiceId);
        const newStatus = !service.active;
        
        await apiRequest('/api/admin/services.php', 'PUT', { 
            id: currentServiceId, 
            active: newStatus 
        });
        
        closeConfirmModal();
        showSuccess(`Service erfolgreich ${newStatus ? 'aktiviert' : 'deaktiviert'}`);
        await loadServices();
    } catch (error) {
        console.error('Error updating service:', error);
        showError('Fehler beim Aktualisieren: ' + error.message);
    }
}

// Close confirm modal
function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    currentServiceId = null;
}

// Filter services
async function filterServices() {
    const type = document.getElementById('typeFilter').value;
    const active = document.getElementById('activeFilter').value;
    
    try {
        const params = new URLSearchParams();
        if (type) params.append('type', type);
        if (active !== '') params.append('active', active);
        
        const url = '/api/admin/services.php' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        
        services = response.services || [];
        renderServices(services);
        updateServicesCount(services.length);
    } catch (error) {
        console.error('Error filtering services:', error);
        showError('Fehler beim Filtern: ' + error.message);
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('typeFilter').value = '';
    document.getElementById('activeFilter').value = '';
    loadServices();
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
    window.location.href = '/api/logout';
}

</script>

<?php renderFooter(); ?>