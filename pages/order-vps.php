<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/layout.php';

// Benutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login');
    exit;
}

$pageTitle = "VPS Konfigurator - SpectraHost";
$pageDescription = "Konfigurieren Sie Ihren individuellen VPS-Server nach Ihren Anforderungen";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-purple-900 via-blue-900 to-indigo-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    VPS <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Konfigurator</span>
                </h1>
                <p class="text-xl text-gray-200 mb-8 max-w-3xl mx-auto">
                    Stellen Sie Ihren perfekten Virtual Private Server zusammen
                </p>
            </div>
        </div>
    </div>

    <!-- Konfigurator -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Konfiguration -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-8">
                    <h2 class="text-2xl font-bold text-white mb-6">VPS konfigurieren</h2>
                    
                    <form id="vpsConfigForm">
                        <!-- CPU Konfiguration -->
                        <div class="mb-8">
                            <label class="block text-white text-lg font-semibold mb-4">CPU Cores</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="cpu_cores" value="1" data-price="0" class="mr-3" checked>
                                    <div>
                                        <div class="text-white font-medium">1 Core</div>
                                        <div class="text-gray-400 text-sm">+€0</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="cpu_cores" value="2" data-price="10" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">2 Cores</div>
                                        <div class="text-gray-400 text-sm">+€10</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="cpu_cores" value="4" data-price="25" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">4 Cores</div>
                                        <div class="text-gray-400 text-sm">+€25</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="cpu_cores" value="8" data-price="50" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">8 Cores</div>
                                        <div class="text-gray-400 text-sm">+€50</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- RAM Konfiguration -->
                        <div class="mb-8">
                            <label class="block text-white text-lg font-semibold mb-4">Arbeitsspeicher (RAM)</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="ram" value="2" data-price="0" class="mr-3" checked>
                                    <div>
                                        <div class="text-white font-medium">2 GB</div>
                                        <div class="text-gray-400 text-sm">+€0</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="ram" value="4" data-price="8" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">4 GB</div>
                                        <div class="text-gray-400 text-sm">+€8</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="ram" value="8" data-price="20" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">8 GB</div>
                                        <div class="text-gray-400 text-sm">+€20</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="ram" value="16" data-price="45" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">16 GB</div>
                                        <div class="text-gray-400 text-sm">+€45</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Speicher Konfiguration -->
                        <div class="mb-8">
                            <label class="block text-white text-lg font-semibold mb-4">SSD Speicher</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="storage" value="25" data-price="0" class="mr-3" checked>
                                    <div>
                                        <div class="text-white font-medium">25 GB</div>
                                        <div class="text-gray-400 text-sm">+€0</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="storage" value="50" data-price="5" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">50 GB</div>
                                        <div class="text-gray-400 text-sm">+€5</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="storage" value="100" data-price="12" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">100 GB</div>
                                        <div class="text-gray-400 text-sm">+€12</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                                    <input type="radio" name="storage" value="250" data-price="30" class="mr-3">
                                    <div>
                                        <div class="text-white font-medium">250 GB</div>
                                        <div class="text-gray-400 text-sm">+€30</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Betriebssystem -->
                        <div class="mb-8">
                            <label class="block text-white text-lg font-semibold mb-4">Betriebssystem</label>
                            <select name="operating_system" class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500">
                                <option value="ubuntu-22.04">Ubuntu 22.04 LTS</option>
                                <option value="ubuntu-20.04">Ubuntu 20.04 LTS</option>
                                <option value="debian-11">Debian 11</option>
                                <option value="debian-12">Debian 12</option>
                                <option value="centos-8">CentOS 8</option>
                                <option value="rocky-9">Rocky Linux 9</option>
                                <option value="windows-2022" data-price="15">Windows Server 2022 (+€15/Monat)</option>
                            </select>
                        </div>

                        <!-- Server-Details -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-white mb-4">Server-Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Server-Name</label>
                                    <input type="text" name="server_name" placeholder="mein-vps-server" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                </div>
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">Root-Passwort</label>
                                    <input type="password" name="root_password" placeholder="Sicheres Passwort" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                                </div>
                            </div>
                        </div>

                        <!-- Zusätzliche Services -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-white mb-4">Zusätzliche Services</h3>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer">
                                    <input type="checkbox" name="backup_service" value="1" data-price="5" class="mr-3">
                                    <div class="flex-1">
                                        <span class="text-white font-medium">Tägliche Backups</span>
                                        <div class="text-gray-400 text-sm">Automatische tägliche Sicherungen</div>
                                    </div>
                                    <span class="text-gray-400">+€5/Monat</span>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer">
                                    <input type="checkbox" name="monitoring" value="1" data-price="3" class="mr-3">
                                    <div class="flex-1">
                                        <span class="text-white font-medium">24/7 Monitoring</span>
                                        <div class="text-gray-400 text-sm">Kontinuierliche Überwachung</div>
                                    </div>
                                    <span class="text-gray-400">+€3/Monat</span>
                                </label>
                                <label class="flex items-center p-4 bg-gray-700 rounded-lg cursor-pointer">
                                    <input type="checkbox" name="managed_support" value="1" data-price="15" class="mr-3">
                                    <div class="flex-1">
                                        <span class="text-white font-medium">Managed Support</span>
                                        <div class="text-gray-400 text-sm">Proaktive Verwaltung und Support</div>
                                    </div>
                                    <span class="text-gray-400">+€15/Monat</span>
                                </label>
                            </div>
                        </div>

                        <!-- Bestellung abschließen -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-300">
                                <i class="fas fa-shopping-cart mr-2"></i>VPS bestellen
                            </button>
                            <a href="/products/vserver" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 px-6 rounded-lg font-medium text-center transition-colors">
                                Zurück zur Übersicht
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Konfigurationsübersicht -->
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6 sticky top-8">
                    <h3 class="text-xl font-bold text-white mb-4">Ihre Konfiguration</h3>
                    
                    <!-- VPS Details -->
                    <div class="mb-6">
                        <div class="flex items-center mb-3">
                            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-server text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-medium">Custom VPS</h4>
                                <p class="text-gray-400 text-sm">Individuell konfiguriert</p>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-4" id="configSummary">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">CPU:</span>
                                <span class="text-white" id="cpuDisplay">1 Core</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">RAM:</span>
                                <span class="text-white" id="ramDisplay">2 GB</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Speicher:</span>
                                <span class="text-white" id="storageDisplay">25 GB SSD</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">OS:</span>
                                <span class="text-white" id="osDisplay">Ubuntu 22.04</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preisübersicht -->
                    <div class="border-t border-gray-600 pt-4">
                        <div class="space-y-2 mb-4" id="priceBreakdown">
                            <div class="flex justify-between">
                                <span class="text-gray-300">Basis VPS</span>
                                <span class="text-white">€9,99</span>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-600 pt-2">
                            <div class="flex justify-between">
                                <span class="text-white font-medium">Gesamt (monatlich)</span>
                                <span class="text-white font-bold text-lg" id="totalPrice">€9,99</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Features -->
                    <div class="mt-6 p-4 bg-blue-900/20 rounded-lg border border-blue-800">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-check-circle text-blue-400 mr-2"></i>
                            <span class="text-blue-400 font-medium">Inklusive</span>
                        </div>
                        <ul class="text-gray-300 text-sm space-y-1">
                            <li>• Root-Zugriff</li>
                            <li>• 1 Gbit/s Port</li>
                            <li>• DDoS-Schutz</li>
                            <li>• 24/7 Support</li>
                            <li>• Kostenloses Setup</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = 9.99;

function updateConfiguration() {
    let totalPrice = basePrice;
    let priceBreakdown = [
        { name: 'Basis VPS', price: basePrice }
    ];
    
    // CPU
    const cpuElement = document.querySelector('input[name="cpu_cores"]:checked');
    const cpuCores = cpuElement.value;
    const cpuPrice = parseFloat(cpuElement.dataset.price);
    document.getElementById('cpuDisplay').textContent = cpuCores + (cpuCores == 1 ? ' Core' : ' Cores');
    if (cpuPrice > 0) {
        totalPrice += cpuPrice;
        priceBreakdown.push({ name: `CPU Upgrade (${cpuCores} Cores)`, price: cpuPrice });
    }
    
    // RAM
    const ramElement = document.querySelector('input[name="ram"]:checked');
    const ramSize = ramElement.value;
    const ramPrice = parseFloat(ramElement.dataset.price);
    document.getElementById('ramDisplay').textContent = ramSize + ' GB';
    if (ramPrice > 0) {
        totalPrice += ramPrice;
        priceBreakdown.push({ name: `RAM Upgrade (${ramSize} GB)`, price: ramPrice });
    }
    
    // Storage
    const storageElement = document.querySelector('input[name="storage"]:checked');
    const storageSize = storageElement.value;
    const storagePrice = parseFloat(storageElement.dataset.price);
    document.getElementById('storageDisplay').textContent = storageSize + ' GB SSD';
    if (storagePrice > 0) {
        totalPrice += storagePrice;
        priceBreakdown.push({ name: `Storage Upgrade (${storageSize} GB)`, price: storagePrice });
    }
    
    // Operating System
    const osElement = document.querySelector('select[name="operating_system"]');
    const osOption = osElement.options[osElement.selectedIndex];
    const osPrice = parseFloat(osOption.dataset.price || 0);
    document.getElementById('osDisplay').textContent = osOption.text.split(' (')[0];
    if (osPrice > 0) {
        totalPrice += osPrice;
        priceBreakdown.push({ name: 'Windows Lizenz', price: osPrice });
    }
    
    // Zusätzliche Services
    document.querySelectorAll('input[name="backup_service"], input[name="monitoring"], input[name="managed_support"]').forEach(checkbox => {
        if (checkbox.checked) {
            const price = parseFloat(checkbox.dataset.price);
            const serviceName = checkbox.name === 'backup_service' ? 'Backup Service' :
                              checkbox.name === 'monitoring' ? 'Monitoring' : 'Managed Support';
            totalPrice += price;
            priceBreakdown.push({ name: serviceName, price: price });
        }
    });
    
    // Preisaufschlüsselung anzeigen
    const priceBreakdownElement = document.getElementById('priceBreakdown');
    priceBreakdownElement.innerHTML = priceBreakdown.map(item => `
        <div class="flex justify-between">
            <span class="text-gray-300">${item.name}</span>
            <span class="text-white">€${item.price.toFixed(2)}</span>
        </div>
    `).join('');
    
    // Gesamtpreis aktualisieren
    document.getElementById('totalPrice').textContent = '€' + totalPrice.toFixed(2);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Konfiguration bei Änderungen aktualisieren
    document.querySelectorAll('input[type="radio"], input[type="checkbox"], select').forEach(element => {
        element.addEventListener('change', updateConfiguration);
    });
    
    // Formular-Submit
    document.getElementById('vpsConfigForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const config = Object.fromEntries(formData);
        
        try {
            const response = await fetch('/api/create-vps-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(config)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                alert('VPS-Bestellung erfolgreich aufgegeben! Sie werden zur Übersicht weitergeleitet.');
                window.location.href = '/dashboard';
            } else {
                alert('Fehler bei der Bestellung: ' + (result.error || 'Unbekannter Fehler'));
            }
        } catch (error) {
            alert('Fehler bei der Bestellung: ' + error.message);
        }
    });
    
    // Initiale Konfiguration laden
    updateConfiguration();
});
</script>

<?php
renderFooter();
?>