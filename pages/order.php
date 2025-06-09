<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require authentication
$auth = new Auth($db);
$auth->requireLogin();
$user = $auth->getCurrentUser();

$selectedService = isset($_GET['service']) ? (int)$_GET['service'] : null;

renderHeader('Service bestellen - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Service bestellen</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Wählen Sie den perfekten Service für Ihre Anforderungen
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Service Selection -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-6">Verfügbare Services</h2>
            <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Services will be loaded dynamically -->
                <div class="text-center py-8">
                    <div class="loading mx-auto mb-4"></div>
                    <p class="text-gray-500">Services werden geladen...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div id="orderModal" class="modal hidden">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-panel max-w-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Service bestellen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="orderForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="serviceId" id="selected-service-id">
                
                <!-- Service Details -->
                <div class="mb-6">
                    <div id="service-details" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <!-- Service details will be populated -->
                    </div>
                </div>
                
                <!-- Billing Period -->
                <div class="mb-6">
                    <label class="form-label">Abrechnungszeitraum</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                        <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="radio" name="billingPeriod" value="monthly" checked class="mr-3">
                            <div class="flex-1">
                                <div class="font-medium">Monatlich</div>
                                <div class="text-sm text-gray-500" id="monthly-price">€0.00/Monat</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="radio" name="billingPeriod" value="quarterly" class="mr-3">
                            <div class="flex-1">
                                <div class="font-medium">Vierteljährlich</div>
                                <div class="text-sm text-gray-500" id="quarterly-price">€0.00 (5% Rabatt)</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="radio" name="billingPeriod" value="yearly" class="mr-3">
                            <div class="flex-1">
                                <div class="font-medium">Jährlich</div>
                                <div class="text-sm text-gray-500" id="yearly-price">€0.00 (15% Rabatt)</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Server Name (for VPS/Game Servers) -->
                <div class="mb-6" id="server-name-field" style="display: none;">
                    <label for="serverName" class="form-label">Server Name</label>
                    <input type="text" id="serverName" name="serverName" class="form-input" 
                           placeholder="Geben Sie einen Namen für Ihren Server ein">
                    <p class="mt-1 text-sm text-gray-500">Nur Buchstaben, Zahlen und Bindestriche erlaubt</p>
                </div>
                
                <!-- Domain Name (for domains) -->
                <div class="mb-6" id="domain-name-field" style="display: none;">
                    <label for="domainName" class="form-label">Domain Name</label>
                    <div class="flex">
                        <input type="text" id="domainName" name="domainName" class="form-input rounded-r-none" 
                               placeholder="beispiel">
                        <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500" id="domain-extension">
                            .de
                        </span>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="mb-6 bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                    <h4 class="font-semibold mb-2">Bestellübersicht</h4>
                    <div class="flex justify-between items-center">
                        <span>Gesamtbetrag:</span>
                        <span class="text-xl font-bold text-blue-600" id="total-amount">€0.00</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Inkl. 19% MwSt. • Erste Rechnung sofort fällig
                    </p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="btn-outline">
                        Abbrechen
                    </button>
                    <button type="submit" id="order-btn" class="btn-primary">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Jetzt bestellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentService = null;

document.addEventListener('DOMContentLoaded', function() {
    loadServices();
    
    // Billing period change handler
    document.querySelectorAll('input[name="billingPeriod"]').forEach(radio => {
        radio.addEventListener('change', updatePricing);
    });
    
    // Order form submit
    document.getElementById('orderForm').addEventListener('submit', handleOrderSubmit);
    
    // Pre-select service if specified in URL
    const urlParams = new URLSearchParams(window.location.search);
    const serviceId = urlParams.get('service');
    if (serviceId) {
        setTimeout(() => selectService(parseInt(serviceId)), 1000);
    }
});

async function loadServices() {
    try {
        const response = await apiRequest('/api/services');
        if (response.success) {
            renderServices(response.services);
        }
    } catch (error) {
        console.error('Error loading services:', error);
        showNotification('Fehler beim Laden der Services', 'error');
    }
}

function renderServices(services) {
    const grid = document.getElementById('services-grid');
    const serviceTypes = {
        'webhosting': { icon: 'fas fa-globe', color: 'blue' },
        'vps': { icon: 'fas fa-server', color: 'green' },
        'gameserver': { icon: 'fas fa-gamepad', color: 'purple' },
        'domain': { icon: 'fas fa-link', color: 'orange' }
    };
    
    grid.innerHTML = services.map(service => {
        const type = serviceTypes[service.type] || { icon: 'fas fa-cog', color: 'gray' };
        
        return `
            <div class="card hover-lift cursor-pointer" onclick="selectService(${service.id})">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-${type.color}-100 dark:bg-${type.color}-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="${type.icon} text-xl text-${type.color}-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold">${service.name}</h3>
                        <div class="text-2xl font-bold text-${type.color}-600">€${service.price}/Monat</div>
                    </div>
                </div>
                
                <p class="text-gray-600 dark:text-gray-400 mb-4">${service.description}</p>
                
                <div class="space-y-2 mb-6">
                    ${service.cpu_cores > 0 ? `<div class="flex items-center text-sm">
                        <i class="fas fa-microchip w-4 mr-2 text-gray-400"></i>
                        ${service.cpu_cores} CPU Core${service.cpu_cores > 1 ? 's' : ''}
                    </div>` : ''}
                    ${service.memory_gb > 0 ? `<div class="flex items-center text-sm">
                        <i class="fas fa-memory w-4 mr-2 text-gray-400"></i>
                        ${service.memory_gb} GB RAM
                    </div>` : ''}
                    ${service.storage_gb > 0 ? `<div class="flex items-center text-sm">
                        <i class="fas fa-hdd w-4 mr-2 text-gray-400"></i>
                        ${service.storage_gb} GB Storage
                    </div>` : ''}
                    ${service.bandwidth_gb > 0 ? `<div class="flex items-center text-sm">
                        <i class="fas fa-network-wired w-4 mr-2 text-gray-400"></i>
                        ${service.bandwidth_gb} GB Traffic
                    </div>` : ''}
                </div>
                
                <button class="w-full bg-${type.color}-600 hover:bg-${type.color}-700 text-white py-2 px-4 rounded-lg transition-colors">
                    Jetzt bestellen
                </button>
            </div>
        `;
    }).join('');
    
    // Store services globally
    window.services = services;
}

function selectService(serviceId) {
    const service = window.services.find(s => s.id === serviceId);
    if (!service) return;
    
    currentService = service;
    
    // Populate service details
    document.getElementById('selected-service-id').value = serviceId;
    
    const serviceTypes = {
        'webhosting': { icon: 'fas fa-globe', color: 'blue' },
        'vps': { icon: 'fas fa-server', color: 'green' },
        'gameserver': { icon: 'fas fa-gamepad', color: 'purple' },
        'domain': { icon: 'fas fa-link', color: 'orange' }
    };
    
    const type = serviceTypes[service.type] || { icon: 'fas fa-cog', color: 'gray' };
    
    document.getElementById('service-details').innerHTML = `
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-${type.color}-100 dark:bg-${type.color}-900 rounded-lg flex items-center justify-center mr-4">
                <i class="${type.icon} text-xl text-${type.color}-600"></i>
            </div>
            <div>
                <h4 class="text-lg font-semibold">${service.name}</h4>
                <p class="text-gray-600 dark:text-gray-400">${service.description}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            ${service.cpu_cores > 0 ? `<div>
                <span class="text-gray-500">CPU:</span>
                <span class="font-medium">${service.cpu_cores} Core${service.cpu_cores > 1 ? 's' : ''}</span>
            </div>` : ''}
            ${service.memory_gb > 0 ? `<div>
                <span class="text-gray-500">RAM:</span>
                <span class="font-medium">${service.memory_gb} GB</span>
            </div>` : ''}
            ${service.storage_gb > 0 ? `<div>
                <span class="text-gray-500">Storage:</span>
                <span class="font-medium">${service.storage_gb} GB</span>
            </div>` : ''}
            ${service.bandwidth_gb > 0 ? `<div>
                <span class="text-gray-500">Traffic:</span>
                <span class="font-medium">${service.bandwidth_gb} GB</span>
            </div>` : ''}
        </div>
    `;
    
    // Show/hide conditional fields
    document.getElementById('server-name-field').style.display = 
        ['vps', 'gameserver'].includes(service.type) ? 'block' : 'none';
    
    document.getElementById('domain-name-field').style.display = 
        service.type === 'domain' ? 'block' : 'none';
    
    if (service.type === 'domain') {
        const extension = service.name.includes('.de') ? '.de' : '.com';
        document.getElementById('domain-extension').textContent = extension;
    }
    
    updatePricing();
    openModal('orderModal');
}

function updatePricing() {
    if (!currentService) return;
    
    const basePrice = parseFloat(currentService.price);
    const billingPeriod = document.querySelector('input[name="billingPeriod"]:checked').value;
    
    let multiplier = 1;
    let discount = 0;
    
    switch (billingPeriod) {
        case 'quarterly':
            multiplier = 3;
            discount = 0.05; // 5% discount
            break;
        case 'yearly':
            multiplier = 12;
            discount = 0.15; // 15% discount
            break;
    }
    
    const totalAmount = basePrice * multiplier * (1 - discount);
    
    // Update price displays
    document.getElementById('monthly-price').textContent = `€${basePrice.toFixed(2)}/Monat`;
    document.getElementById('quarterly-price').textContent = 
        `€${(basePrice * 3 * 0.95).toFixed(2)} (5% Rabatt)`;
    document.getElementById('yearly-price').textContent = 
        `€${(basePrice * 12 * 0.85).toFixed(2)} (15% Rabatt)`;
    
    document.getElementById('total-amount').textContent = formatCurrency(totalAmount);
}

async function handleOrderSubmit(e) {
    e.preventDefault();
    
    const btn = document.getElementById('order-btn');
    
    // Validation
    if (currentService.type === 'vps' || currentService.type === 'gameserver') {
        const serverName = document.getElementById('serverName').value.trim();
        if (!serverName) {
            showNotification('Bitte geben Sie einen Server-Namen ein', 'error');
            return;
        }
        if (!/^[a-zA-Z0-9-]+$/.test(serverName)) {
            showNotification('Server-Name darf nur Buchstaben, Zahlen und Bindestriche enthalten', 'error');
            return;
        }
    }
    
    if (currentService.type === 'domain') {
        const domainName = document.getElementById('domainName').value.trim();
        if (!domainName) {
            showNotification('Bitte geben Sie einen Domain-Namen ein', 'error');
            return;
        }
        if (!/^[a-zA-Z0-9-]+$/.test(domainName)) {
            showNotification('Domain-Name darf nur Buchstaben, Zahlen und Bindestriche enthalten', 'error');
            return;
        }
    }
    
    setLoading(btn, true);
    
    try {
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        const result = await apiRequest('/api/order', 'POST', data);
        
        if (result.paymentUrl) {
            // Redirect to payment
            window.location.href = result.paymentUrl;
        } else {
            showNotification('Bestellung erfolgreich erstellt!', 'success');
            closeModal();
            
            // Redirect to dashboard after success
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
        }
        
    } catch (error) {
        showNotification(error.message, 'error');
    }
    
    setLoading(btn, false);
}
</script>

<?php renderFooter(); ?>