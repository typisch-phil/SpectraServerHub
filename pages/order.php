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
                
                <!-- Server Configuration (for VPS/Game Servers) -->
                <div class="mb-6" id="server-config-field" style="display: none;">
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="font-semibold mb-4">Server-Konfiguration</h4>
                        
                        <!-- Server Name -->
                        <div class="mb-4">
                            <label for="serverName" class="form-label">Hostname</label>
                            <input type="text" id="serverName" name="serverName" class="form-input" 
                                   placeholder="mein-server" pattern="[a-zA-Z0-9-]+" required>
                            <p class="mt-1 text-sm text-gray-500">Nur Buchstaben, Zahlen und Bindestriche erlaubt (z.B. web-server-01)</p>
                        </div>
                        
                        <!-- Operating System -->
                        <div class="mb-4">
                            <label for="osType" class="form-label">Betriebssystem</label>
                            <select id="osType" name="osType" class="form-input">
                                <option value="l26">Ubuntu 22.04 LTS</option>
                                <option value="l26">Ubuntu 20.04 LTS</option>
                                <option value="l26">Debian 12</option>
                                <option value="l26">Debian 11</option>
                                <option value="l26">CentOS 9 Stream</option>
                                <option value="w11">Windows Server 2022</option>
                                <option value="w10">Windows Server 2019</option>
                            </select>
                        </div>
                        
                        <!-- Server Specifications Display -->
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="text-sm text-gray-500">CPU Kerne</div>
                                <div class="text-lg font-semibold" id="cpu-display">-</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="text-sm text-gray-500">RAM</div>
                                <div class="text-lg font-semibold" id="memory-display">-</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="text-sm text-gray-500">Storage</div>
                                <div class="text-lg font-semibold" id="storage-display">-</div>
                            </div>
                        </div>
                        
                        <!-- Root Password -->
                        <div class="mb-4">
                            <label for="rootPassword" class="form-label">Root/Administrator Passwort</label>
                            <div class="relative">
                                <input type="password" id="rootPassword" name="rootPassword" class="form-input pr-10" 
                                       placeholder="Starkes Passwort eingeben" minlength="8" required>
                                <button type="button" onclick="togglePassword('rootPassword')" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-eye text-gray-400"></i>
                                </button>
                            </div>
                            <div class="mt-2">
                                <button type="button" onclick="generatePassword()" 
                                        class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-key mr-1"></i>Sicheres Passwort generieren
                                </button>
                            </div>
                        </div>
                        
                        <!-- Auto-Start Option -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="autoStart" name="autoStart" checked class="mr-2">
                                <span class="text-sm">Server automatisch starten nach Erstellung</span>
                            </label>
                        </div>
                        
                        <!-- Deployment Status (hidden initially) -->
                        <div id="deployment-status" class="hidden mt-4 p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                            <div class="flex items-center">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                                <span class="text-sm">Server wird in Proxmox VE erstellt...</span>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                <div>VMID: <span id="vm-id">-</span></div>
                                <div>Node: <span id="vm-node">-</span></div>
                                <div>Status: <span id="vm-status">Wird erstellt</span></div>
                            </div>
                        </div>
                    </div>
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
let allServices = [];

// API Request helper function
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
    
    console.log(`Making ${method} request to ${url}`, options);
    
    const response = await fetch(url, options);
    
    console.log(`Response from ${url}:`, response.status, response.statusText);
    
    if (!response.ok) {
        const errorText = await response.text();
        console.error(`API Error Response: ${errorText}`);
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const result = await response.json();
    console.log(`Response data from ${url}:`, result);
    
    return result;
}

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
            allServices = response.services;
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
}

function selectService(serviceId) {
    const service = allServices.find(s => s.id === serviceId);
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
    document.getElementById('server-config-field').style.display = 
        ['vps', 'gameserver'].includes(service.type) ? 'block' : 'none';
    
    document.getElementById('domain-name-field').style.display = 
        service.type === 'domain' ? 'block' : 'none';
    
    // Update server specifications display
    if (['vps', 'gameserver'].includes(service.type)) {
        document.getElementById('cpu-display').textContent = service.cpu_cores + ' Cores';
        document.getElementById('memory-display').textContent = service.memory_gb + ' GB';
        document.getElementById('storage-display').textContent = service.storage_gb + ' GB';
    }
    
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

async function createServerAutomatically(orderId, orderData) {
    try {
        // Show deployment status
        const deploymentStatus = document.getElementById('deployment-status');
        if (deploymentStatus) {
            deploymentStatus.classList.remove('hidden');
        }
        
        const serverData = {
            action: 'create_server',
            service_id: orderData.serviceId,
            hostname: orderData.serverName,
            cpu: currentService.cpu_cores,
            memory: currentService.memory_gb * 1024, // Convert to MB
            storage: currentService.storage_gb,
            os_type: orderData.osType || 'l26',
            root_password: orderData.rootPassword,
            auto_start: orderData.autoStart === 'on'
        };
        
        const response = await fetch('/api/server/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(serverData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update deployment status with server details
            if (document.getElementById('vm-id')) {
                document.getElementById('vm-id').textContent = result.data.vmid;
                document.getElementById('vm-node').textContent = result.data.node;
                document.getElementById('vm-status').textContent = 'Erfolgreich erstellt';
            }
            
            showNotification(`Server ${result.data.hostname} wird erstellt (VM-ID: ${result.data.vmid})`, 'success');
            
            // Auto-start server if requested
            if (serverData.auto_start) {
                setTimeout(() => startServer(result.data.vmid, result.data.node), 3000);
            }
            
        } else {
            showNotification('Server-Erstellung fehlgeschlagen: ' + result.error, 'error');
            console.error('Server creation failed:', result);
        }
        
    } catch (error) {
        showNotification('Fehler bei der Server-Erstellung: ' + error.message, 'error');
        console.error('Server creation error:', error);
    }
}

async function startServer(vmid, node) {
    try {
        const response = await fetch('/api/server/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'start_server',
                vmid: vmid,
                node: node
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Server VM-${vmid} wird gestartet`, 'info');
            if (document.getElementById('vm-status')) {
                document.getElementById('vm-status').textContent = 'Wird gestartet';
            }
        } else {
            showNotification('Server-Start fehlgeschlagen: ' + result.error, 'error');
        }
        
    } catch (error) {
        console.error('Server start error:', error);
    }
}

// Password utility functions
function generatePassword() {
    const length = 16;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    // Ensure at least one character from each category
    password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
    password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)];
    password += "0123456789"[Math.floor(Math.random() * 10)];
    password += "!@#$%^&*"[Math.floor(Math.random() * 8)];
    
    // Fill the rest randomly
    for (let i = password.length; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // Shuffle the password
    password = password.split('').sort(() => Math.random() - 0.5).join('');
    
    document.getElementById('rootPassword').value = password;
    showNotification('Sicheres Passwort generiert', 'success');
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        button.className = 'fas fa-eye-slash text-gray-400';
    } else {
        field.type = 'password';
        button.className = 'fas fa-eye text-gray-400';
    }
}
</script>

<?php renderFooter(); ?>