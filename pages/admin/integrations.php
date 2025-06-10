<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

requireAdmin();

$title = 'Integrationen - SpectraHost Admin';
$description = 'Verwalten Sie externe Integrationen und API-Verbindungen';
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
                        <a href="/admin/integrations" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Integrationen</a>
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Integrationen</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie externe API-Verbindungen und Integrationen</p>
        </div>

        <!-- Integration Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Proxmox VE Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-server text-orange-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Proxmox VE</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Verbunden
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Automatische Server-Verwaltung und VM-Bereitstellung</p>
                <div class="flex space-x-2">
                    <button onclick="configureProxmox()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testProxmox()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- Mollie Payment Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-credit-card text-blue-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mollie</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Aktiv
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Zahlungsverarbeitung und Abonnement-Management</p>
                <div class="flex space-x-2">
                    <button onclick="configureMollie()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testMollie()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- Email SMTP Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-green-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">E-Mail SMTP</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Konfiguration
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">E-Mail-Versand für Benachrichtigungen und Rechnungen</p>
                <div class="flex space-x-2">
                    <button onclick="configureEmail()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testEmail()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- Backup Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-cloud-upload-alt text-purple-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cloud Backup</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        Nicht verbunden
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Automatische Backups für Kundendaten</p>
                <div class="flex space-x-2">
                    <button onclick="configureBackup()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Einrichten
                    </button>
                    <button onclick="testBackup()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm" disabled>
                        Testen
                    </button>
                </div>
            </div>

            <!-- Monitoring Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-red-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Server Monitoring</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Aktiv
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Überwachung der Server-Performance und Verfügbarkeit</p>
                <div class="flex space-x-2">
                    <button onclick="configureMonitoring()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testMonitoring()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>

            <!-- DNS Management Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-globe text-indigo-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">DNS Management</h3>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Teilweise
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Automatische DNS-Zonenverwaltung für Domains</p>
                <div class="flex space-x-2">
                    <button onclick="configureDNS()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                        Konfigurieren
                    </button>
                    <button onclick="testDNS()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                        Testen
                    </button>
                </div>
            </div>
        </div>

        <!-- API Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">API Aktivitätslogs</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Mollie Webhook empfangen</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Zahlung #12345 erfolgreich verarbeitet</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">vor 2 Minuten</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-server text-blue-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Proxmox VM erstellt</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">VM-ID: 201 für Kunde kunde@test.de</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">vor 15 Minuten</span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">E-Mail Zustellung fehlgeschlagen</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">SMTP Verbindung zu server.example.com unterbrochen</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">vor 1 Stunde</span>
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <button onclick="loadMoreLogs()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Weitere Logs laden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div id="configModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900 dark:text-white"></h3>
                    <button onclick="closeConfigModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="modalContent" class="text-sm text-gray-500 dark:text-gray-400">
                    <!-- Dynamic content will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global functions for integration management
        async function testIntegration(integration) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Teste...';
            button.disabled = true;

            try {
                let response;
                if (integration === 'proxmox') {
                    response = await fetch('/api/test-proxmox-direct.php');
                } else {
                    response = await fetch('/api/admin/integrations.php?action=test', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ integration: integration })
                    });
                }

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message);
                    if (result.details) {
                        console.log('Test Details:', result.details);
                    }
                } else {
                    showNotification('error', result.message);
                }
            } catch (error) {
                showNotification('error', 'Verbindungsfehler beim Testen');
                console.error('Test error:', error);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        async function configureIntegration(integration) {
            showConfigModal(integration);
        }

        function showConfigModal(integration) {
            const modal = document.getElementById('configModal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');
            
            title.textContent = getIntegrationTitle(integration);
            content.innerHTML = getConfigForm(integration);
            
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeConfigModal() {
            const modal = document.getElementById('configModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function getIntegrationTitle(integration) {
            const titles = {
                'proxmox': 'Proxmox VE Konfiguration',
                'mollie': 'Mollie Zahlungseinstellungen',
                'email': 'E-Mail SMTP Konfiguration',
                'backup': 'Cloud Backup Einrichtung',
                'monitoring': 'Server Monitoring Konfiguration',
                'dns': 'DNS Management Konfiguration'
            };
            return titles[integration] || 'Integration Konfiguration';
        }

        function getConfigForm(integration) {
            switch (integration) {
                case 'proxmox':
                    return `
                        <form onsubmit="saveProxmoxConfig(event)">
                            <div class="space-y-4">
                                <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg mb-4">
                                    <h4 class="font-medium text-green-800 dark:text-green-200 mb-2">Proxmox VE Produktionsserver</h4>
                                    <p class="text-sm text-green-700 dark:text-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Echte Server-Konfiguration aus Umgebungsvariablen geladen
                                    </p>
                                </div>
                                
                                <input type="hidden" name="config_type" value="production">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Host/IP-Adresse</label>
                                    <input type="text" name="host" value="45.137.68.202" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-600 dark:border-gray-500" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Proxmox Server IP-Adresse</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Benutzername</label>
                                    <input type="text" name="username" value="spectrahost@pve" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-600 dark:border-gray-500" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Proxmox Benutzer</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Passwort</label>
                                    <input type="password" value="••••••••" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-600 dark:border-gray-500" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Passwort ist konfiguriert</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Node-Name</label>
                                    <input type="text" name="node" value="bl1-4" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-600 dark:border-gray-500" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Aktiver Proxmox-Knoten</p>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="ssl_verify" class="mr-2">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">SSL-Zertifikat überprüfen</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">Erweiterte Sicherheitsoption</p>
                                </div>
                                
                                <div class="flex justify-end space-x-3 pt-4">
                                    <button type="button" onclick="closeConfigModal()" class="px-4 py-2 text-gray-600 border rounded hover:bg-gray-50">Abbrechen</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Speichern & Testen</button>
                                </div>
                            </div>
                        </form>
                    `;
                case 'mollie':
                    return `
                        <form onsubmit="saveConfig('mollie', event)">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
                                    <input type="password" name="api_key" placeholder="live_... oder test_..." class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" required>
                                    <p class="text-xs text-gray-500 mt-1">Für Live-Modus: live_... / Für Test-Modus: test_...</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</label>
                                    <input type="url" name="webhook_url" placeholder="https://spectrahost.de/api/mollie/webhook" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" required>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="test_mode" class="mr-2">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Test-Modus aktivieren</span>
                                    </label>
                                </div>
                                <div class="flex justify-end space-x-3 pt-4">
                                    <button type="button" onclick="closeConfigModal()" class="px-4 py-2 text-gray-600 border rounded hover:bg-gray-50">Abbrechen</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Speichern</button>
                                </div>
                            </div>
                        </form>
                    `;
                case 'email':
                    return `
                        <form onsubmit="saveConfig('email', event)">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SMTP Server</label>
                                    <input type="text" name="smtp_host" placeholder="smtp.gmail.com" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port</label>
                                    <input type="number" name="smtp_port" placeholder="587" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Benutzername</label>
                                    <input type="email" name="smtp_username" placeholder="noreply@spectrahost.de" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Passwort</label>
                                    <input type="password" name="smtp_password" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" required>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="smtp_ssl" checked class="mr-2">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">SSL/TLS verwenden</span>
                                    </label>
                                </div>
                                <div class="flex justify-end space-x-3 pt-4">
                                    <button type="button" onclick="closeConfigModal()" class="px-4 py-2 text-gray-600 border rounded hover:bg-gray-50">Abbrechen</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Speichern</button>
                                </div>
                            </div>
                        </form>
                    `;
                default:
                    return `
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Konfiguration für ${integration} wird geladen...</p>
                            <div class="mt-4">
                                <button onclick="closeConfigModal()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Schließen</button>
                            </div>
                        </div>
                    `;
            }
        }

        async function saveProxmoxConfig(event) {
            event.preventDefault();
            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Speichere...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('/api/save-proxmox-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message);
                    closeConfigModal();
                    // Reload the page to update integration status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('error', result.message || 'Fehler beim Speichern der Konfiguration');
                }
            } catch (error) {
                console.error('Speicherfehler:', error);
                showNotification('error', 'Verbindungsfehler beim Speichern');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        async function saveConfig(integration, event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const config = Object.fromEntries(formData.entries());

            // Show saving status
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Speichere...';
            submitBtn.disabled = true;

            try {
                let response;
                if (integration === 'proxmox') {
                    response = await fetch('/api/save-proxmox-config.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                } else {
                    const payload = { integration: integration, action: 'save', ...config };
                    response = await fetch('/api/admin/integrations.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });
                }

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Save result:', result);
                
                if (result.success) {
                    showNotification('success', result.message);
                    closeConfigModal();
                    
                    // Auto-test connection after successful save
                    showNotification('info', 'Teste Verbindung...');
                    setTimeout(async () => {
                        try {
                            await testIntegration(integration);
                        } catch (error) {
                            console.log('Auto-test completed');
                        }
                    }, 1000);
                    
                } else {
                    showNotification('error', result.message || 'Unbekannter Fehler beim Speichern');
                    console.error('Save failed:', result);
                }
            } catch (error) {
                showNotification('error', 'Verbindungsfehler beim Speichern der Konfiguration');
                console.error('Save error:', error);
                console.error('Response status:', error.status);
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        async function loadMoreLogs() {
            try {
                const response = await fetch('/api/admin/integrations.php?action=logs');
                const result = await response.json();
                
                if (result.success) {
                    // Update logs display
                    console.log('API Logs:', result.data);
                    showNotification('success', 'Logs aktualisiert');
                } else {
                    showNotification('error', 'Fehler beim Laden der Logs');
                }
            } catch (error) {
                showNotification('error', 'Verbindungsfehler beim Laden der Logs');
                console.error('Load logs error:', error);
            }
        }



        function showNotification(type, message) {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 
                           type === 'info' ? 'bg-blue-500' : 'bg-gray-500';
            const icon = type === 'success' ? 'check' : 
                        type === 'error' ? 'exclamation-triangle' : 
                        type === 'info' ? 'info-circle' : 'bell';
            
            // Create unique position for multiple notifications
            const existingNotifications = document.querySelectorAll('.notification-item');
            const topOffset = 16 + (existingNotifications.length * 120);
            
            notification.className = `notification-item fixed right-4 p-4 rounded-lg text-white z-50 ${bgColor} max-w-sm shadow-lg`;
            notification.style.top = `${topOffset}px`;
            
            // Format multi-line messages properly
            const formattedMessage = message.replace(/\n/g, '<br>');
            
            notification.innerHTML = `
                <div class="flex items-start">
                    <i class="fas fa-${icon} mr-3 mt-1 flex-shrink-0"></i>
                    <div class="flex-1">
                        <div class="text-sm whitespace-pre-line">${formattedMessage}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200 flex-shrink-0">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove with longer duration for detailed messages
            const duration = message.length > 200 ? 8000 : (type === 'info' ? 6000 : 4000);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
        }

        // Specific integration functions using dedicated APIs
        function configureProxmox() { configureIntegration('proxmox'); }
        function testProxmox() { 
            testIntegration('proxmox');
        }
        function configureMollie() { configureIntegration('mollie'); }
        function testMollie() { 
            testIntegration('mollie');
        }

        async function testSpecificIntegration(integration, button) {
            if (!button) {
                showNotification('error', 'Button-Referenz fehlt');
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Teste...';
            button.disabled = true;

            try {
                const response = await fetch(`/api/integrations/${integration}.php?action=test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message);
                    if (result.details) {
                        console.log(`${integration} Test Details:`, result.details);
                        
                        // Show detailed results for successful tests
                        if (integration === 'proxmox' && result.details.nodes !== undefined) {
                            const detailMsg = `🟢 Proxmox VE Connected
                            Version: ${result.details.version} (${result.details.release})
                            Infrastructure: ${result.details.nodes} Node(s), ${result.details.vms} VM(s)
                            Response Time: ${result.details.response_time}
                            Status: Ready for automated server deployment`;
                            
                            setTimeout(() => {
                                showNotification('info', detailMsg);
                            }, 1000);
                            
                        } else if (integration === 'mollie' && result.details.profile_name) {
                            const mode = result.details.test_mode ? 'Test Mode' : 'Live Mode';
                            const detailMsg = `🟢 Mollie Payment Gateway Connected
                            Profile: ${result.details.profile_name} (${result.details.profile_email})
                            Mode: ${mode}
                            Payment Methods: ${result.details.available_methods.join(', ')}
                            Recent Payments: ${result.details.recent_payments}
                            Response Time: ${result.details.response_time}
                            Status: Ready for payment processing`;
                            
                            setTimeout(() => {
                                showNotification('info', detailMsg);
                            }, 1000);
                            
                        } else if (integration === 'email' && result.details.smtp_host) {
                            const sslStatus = result.details.ssl_enabled ? 'SSL/TLS Enabled' : 'No SSL';
                            const detailMsg = `🟢 E-Mail SMTP Connected
                            SMTP Server: ${result.details.smtp_host}:${result.details.smtp_port}
                            Username: ${result.details.username}
                            Security: ${sslStatus}
                            Response Time: ${result.details.response_time}
                            Status: ${result.details.status}`;
                            
                            setTimeout(() => {
                                showNotification('info', detailMsg);
                            }, 1000);
                        }
                    }
                } else {
                    showNotification('error', result.message);
                    if (result.details) {
                        let troubleshootMsg = '';
                        
                        if (integration === 'proxmox') {
                            troubleshootMsg = `❌ Proxmox VE Connection Failed
                            Error: ${result.details.error || 'Unknown error'}
                            
                            Troubleshooting Steps:
                            1. Verify Proxmox host is reachable
                            2. Check username and password
                            3. Ensure API access is enabled
                            4. Verify SSL certificate or disable SSL verification
                            5. Check firewall settings (Port 8006)`;
                            
                        } else if (integration === 'mollie') {
                            troubleshootMsg = `❌ Mollie API Connection Failed
                            Error: ${result.details.error || 'Unknown error'}
                            
                            Troubleshooting Steps:
                            1. Verify API key is correct (starts with live_ or test_)
                            2. Check API key permissions in Mollie dashboard
                            3. Ensure account is activated for payments
                            4. Verify webhook URL is accessible
                            5. Check network connectivity`;
                            
                        } else if (integration === 'email') {
                            troubleshootMsg = `❌ E-Mail SMTP Connection Failed
                            Error: ${result.details.error || 'Unknown error'}
                            
                            Troubleshooting Steps:
                            1. Verify SMTP server hostname and port
                            2. Check username and password credentials
                            3. Ensure SMTP authentication is enabled
                            4. Verify SSL/TLS settings match server requirements
                            5. Check firewall settings (ports 25, 587, 465)
                            6. Test with different SMTP provider if needed`;
                        }
                        
                        if (troubleshootMsg) {
                            setTimeout(() => {
                                showNotification('error', troubleshootMsg);
                            }, 2000);
                        }
                        
                        if (result.details.suggestion) {
                            setTimeout(() => {
                                showNotification('info', result.details.suggestion);
                            }, 4000);
                        }
                    }
                }
            } catch (error) {
                showNotification('error', `Verbindungsfehler beim Testen von ${integration}`);
                console.error(`${integration} test error:`, error);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        async function saveConfig(integration, event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const config = Object.fromEntries(formData.entries());

            try {
                const response = await fetch(`/api/integrations/${integration}.php?action=configure`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ integration: integration, config: config })
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message);
                    closeConfigModal();
                    // Auto-test after successful configuration
                    setTimeout(() => {
                        if (integration === 'proxmox' || integration === 'mollie') {
                            testSpecificIntegration(integration);
                        }
                    }, 1000);
                } else {
                    showNotification('error', result.message);
                }
            } catch (error) {
                showNotification('error', 'Fehler beim Speichern der Konfiguration');
                console.error('Save error:', error);
            }
        }
        function configureEmail() { configureIntegration('email'); }
        function testEmail() { 
            const btn = event.target;
            testSpecificIntegration('email', btn); 
        }
        function configureBackup() { configureIntegration('backup'); }
        function testBackup() { testIntegration('backup'); }
        function configureMonitoring() { configureIntegration('monitoring'); }
        function testMonitoring() { testIntegration('monitoring'); }
        function configureDNS() { configureIntegration('dns'); }
        function testDNS() { testIntegration('dns'); }

        function logout() {
            window.location.href = '/api/logout.php';
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

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(savedTheme);
            updateThemeIcon();
            
            // Add event listener to theme toggle button
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
        });
    </script>
</div>

<?php renderFooter(); ?>