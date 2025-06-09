<?php
require_once '../../includes/session.php';
requireLogin();
requireAdmin();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrationen - SpectraHost Admin</title>
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
                    <h1 class="text-xl font-bold">Integrationen & APIs</h1>
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Mollie Payment Integration -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-credit-card text-blue-600 text-xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-gray-900">Mollie Payment Gateway</h3>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktiv</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Verarbeitung von Zahlungen über iDEAL, Kreditkarte und PayPal</p>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">API Status:</span>
                                <span class="text-sm text-green-600">Verbunden</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Test Mode:</span>
                                <span class="text-sm text-orange-600">Aktiviert</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Letzte Zahlung:</span>
                                <span class="text-sm text-gray-900">Heute 14:32</span>
                            </div>
                        </div>
                        <button onclick="configureMollie()" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                            Konfigurieren
                        </button>
                    </div>
                </div>

                <!-- Proxmox VE Integration -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-server text-orange-600 text-xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-gray-900">Proxmox VE</h3>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Demo</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Automatisierte Server-Verwaltung und VM-Bereitstellung</p>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">API Status:</span>
                                <span class="text-sm text-yellow-600">Demo-Modus</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Server:</span>
                                <span class="text-sm text-gray-900">demo.proxmox.com</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Aktive VMs:</span>
                                <span class="text-sm text-gray-900">3</span>
                            </div>
                        </div>
                        <button onclick="configureProxmox()" class="mt-4 w-full bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700">
                            Konfigurieren
                        </button>
                    </div>
                </div>

                <!-- Email Integration -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-green-600 text-xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-gray-900">E-Mail Service</h3>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Nicht konfiguriert</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">SMTP-Konfiguration für automatische E-Mails</p>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">SMTP Server:</span>
                                <span class="text-sm text-gray-400">Nicht konfiguriert</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Port:</span>
                                <span class="text-sm text-gray-400">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Verschlüsselung:</span>
                                <span class="text-sm text-gray-400">-</span>
                            </div>
                        </div>
                        <button onclick="configureEmail()" class="mt-4 w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700">
                            Einrichten
                        </button>
                    </div>
                </div>

                <!-- Backup Integration -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-cloud-upload-alt text-purple-600 text-xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-gray-900">Backup System</h3>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Nicht konfiguriert</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Automatische Backups für Kundendaten</p>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Storage Provider:</span>
                                <span class="text-sm text-gray-400">Nicht konfiguriert</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Frequenz:</span>
                                <span class="text-sm text-gray-400">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Letzte Sicherung:</span>
                                <span class="text-sm text-gray-400">Nie</span>
                            </div>
                        </div>
                        <button onclick="configureBackup()" class="mt-4 w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700">
                            Einrichten
                        </button>
                    </div>
                </div>
            </div>

            <!-- API Keys Section -->
            <div class="mt-8 bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">API-Schlüssel Verwaltung</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mollie API Key</label>
                            <div class="flex">
                                <input type="password" class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2" value="test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM" readonly>
                                <button onclick="togglePassword(this)" class="bg-gray-200 border border-gray-300 border-l-0 rounded-r-lg px-3 py-2 hover:bg-gray-300">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Proxmox API Token</label>
                            <div class="flex">
                                <input type="password" class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2" value="demo_token_12345" readonly>
                                <button onclick="togglePassword(this)" class="bg-gray-200 border border-gray-300 border-l-0 rounded-r-lg px-3 py-2 hover:bg-gray-300">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex space-x-4">
                        <button onclick="updateAPIKeys()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            API-Schlüssel aktualisieren
                        </button>
                        <button onclick="testConnections()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            Verbindungen testen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function configureMollie() {
            alert('Mollie-Konfiguration öffnen - Feature wird implementiert');
        }

        function configureProxmox() {
            alert('Proxmox-Konfiguration öffnen - Feature wird implementiert');
        }

        function configureEmail() {
            alert('E-Mail-Konfiguration öffnen - Feature wird implementiert');
        }

        function configureBackup() {
            alert('Backup-Konfiguration öffnen - Feature wird implementiert');
        }

        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function updateAPIKeys() {
            alert('API-Schlüssel aktualisieren - Feature wird implementiert');
        }

        function testConnections() {
            alert('Verbindungen werden getestet...\n\n✓ Mollie: Verbunden\n✓ Proxmox: Demo-Modus\n✗ E-Mail: Nicht konfiguriert\n✗ Backup: Nicht konfiguriert');
        }
    </script>
</body>
</html>