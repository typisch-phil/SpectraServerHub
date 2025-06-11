<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/proxmox-api.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle = 'Services bestellen - SpectraHost';
$pageDescription = 'Wählen Sie aus unseren professionellen Hosting-Services: VPS Server, Webhosting, Gameserver und Domains.';

// This is now a service overview page - no form processing needed

renderHeader($pageTitle, $pageDescription);
?>

<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl">
                Services bestellen
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Wählen Sie aus unseren professionellen Hosting-Services: VPS Server, Webhosting, Gameserver und Domains.
            </p>
        </div>
    </div>
</div>

<!-- Services Grid -->
<div class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- VPS Server -->
            <div class="bg-gray-800 rounded-xl p-8 hover:bg-gray-700 transition-all duration-300 transform hover:scale-105 border border-gray-700">
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-600 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-server text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">VPS Server</h3>
                    <p class="text-gray-300 mb-6">Leistungsstarke Virtual Private Server mit Root-Zugriff und garantierten Ressourcen.</p>
                    <ul class="text-sm text-gray-400 space-y-2 mb-8">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Root-Zugriff</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>SSD-NVMe Speicher</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Proxmox Integration</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Sofort verfügbar</li>
                    </ul>
                    <div class="text-center mb-6">
                        <span class="text-lg text-gray-300">ab</span>
                        <span class="text-3xl font-bold text-white">€9,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <a href="/order-vps" class="block w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        VPS bestellen
                    </a>
                </div>
            </div>

            <!-- Webhosting -->
            <div class="bg-gray-800 rounded-xl p-8 hover:bg-gray-700 transition-all duration-300 transform hover:scale-105 border border-gray-700">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-globe text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Webhosting</h3>
                    <p class="text-gray-300 mb-6">Professionelles Webhosting mit SSD-Speicher und unbegrenzten E-Mail-Postfächern.</p>
                    <ul class="text-sm text-gray-400 space-y-2 mb-8">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>SSD-Speicher</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>SSL-Zertifikat inklusive</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Unbegrenzte Domains</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>MySQL Datenbanken</li>
                    </ul>
                    <div class="text-center mb-6">
                        <span class="text-lg text-gray-300">ab</span>
                        <span class="text-3xl font-bold text-white">€4,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <a href="/order-webhosting" class="block w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Webhosting bestellen
                    </a>
                </div>
            </div>

            <!-- Gameserver -->
            <div class="bg-gray-800 rounded-xl p-8 hover:bg-gray-700 transition-all duration-300 transform hover:scale-105 border border-gray-700">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-600 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-gamepad text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Gameserver</h3>
                    <p class="text-gray-300 mb-6">Optimierte Gaming-Server für Minecraft, CS:GO, ARK und viele weitere Spiele.</p>
                    <ul class="text-sm text-gray-400 space-y-2 mb-8">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>DDoS-Schutz</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Instant Setup</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Mod-Support</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Backup-System</li>
                    </ul>
                    <div class="text-center mb-6">
                        <span class="text-lg text-gray-300">ab</span>
                        <span class="text-3xl font-bold text-white">€7,99</span>
                        <span class="text-gray-300">/Monat</span>
                    </div>
                    <a href="/order-gameserver" class="block w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        Gameserver bestellen
                    </a>
                </div>
            </div>

            <!-- Domains -->
            <div class="bg-gray-800 rounded-xl p-8 hover:bg-gray-700 transition-all duration-300 transform hover:scale-105 border border-gray-700">
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-600 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-link text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Domains</h3>
                    <p class="text-gray-300 mb-6">Registrieren Sie Ihre Wunschdomain aus über 500 verfügbaren Endungen.</p>
                    <ul class="text-sm text-gray-400 space-y-2 mb-8">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>500+ TLDs verfügbar</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>DNS-Management</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Domain-Transfer</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Whois-Privacy</li>
                    </ul>
                    <div class="text-center mb-6">
                        <span class="text-lg text-gray-300">ab</span>
                        <span class="text-3xl font-bold text-white">€0,99</span>
                        <span class="text-gray-300">/Jahr</span>
                    </div>
                    <a href="/order-domain" class="block w-full bg-orange-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-orange-700 transition-colors">
                        Domain registrieren
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Why Choose Us -->
<div class="py-20 bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-white mb-4">Warum SpectraHost?</h2>
            <p class="text-xl text-gray-300">Ihre Vorteile bei unseren Hosting-Services</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-4">Sofortige Bereitstellung</h3>
                <p class="text-gray-300">Ihre Services werden automatisch eingerichtet und sind innerhalb von Minuten verfügbar.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-4">99.9% Uptime</h3>
                <p class="text-gray-300">Höchste Verfügbarkeit durch redundante Infrastruktur und professionelles Monitoring.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-headset text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-4">24/7 Support</h3>
                <p class="text-gray-300">Deutschsprachiger Support rund um die Uhr für alle Ihre Fragen und Probleme.</p>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>