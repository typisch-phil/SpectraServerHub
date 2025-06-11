<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$pageTitle = 'Services & Hosting-Lösungen - SpectraHost';
$pageDescription = 'Entdecken Sie unsere professionellen Hosting-Lösungen: Webhosting, VPS, Gameserver und Domains zu unschlagbaren Preisen.';
renderHeader($pageTitle, $pageDescription);
?>

<!-- Hero Section -->
<div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl">
                Premium <span class="text-blue-400">Hosting</span> Services
            </h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                Professionelle Hosting-Lösungen für jeden Bedarf. Von Webhosting bis hin zu leistungsstarken VPS-Servern.
            </p>
        </div>
    </div>
</div>

<!-- Services Grid -->
<div class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- Webhosting -->
            <div class="bg-gray-800 rounded-xl p-8 hover:bg-gray-700 transition-all duration-300 transform hover:scale-105 border border-gray-700">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-globe text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Webhosting</h3>
                    <p class="text-gray-300 mb-6">Professionelles Webhosting mit SSD-Speicher, SSL-Zertifikaten und unbegrenzten E-Mail-Postfächern.</p>
                    <ul class="text-sm text-gray-400 space-y-2 mb-8">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>SSD-Speicher</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>SSL-Zertifikat inklusive</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Unbegrenzte Domains</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>MySQL Datenbanken</li>
                    </ul>
                    <a href="/products/webhosting" class="block w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Details ansehen
                    </a>
                </div>
            </div>

            <!-- VPS -->
            <div class="bg-gray-800 rounded-xl p-8 hover:bg-gray-700 transition-all duration-300 transform hover:scale-105 border border-gray-700">
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-600 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-server text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">VPS Server</h3>
                    <p class="text-gray-300 mb-6">Leistungsstarke Virtual Private Server mit garantierten Ressourcen und Root-Zugriff.</p>
                    <ul class="text-sm text-gray-400 space-y-2 mb-8">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Root-Zugriff</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>SSD-NVMe Speicher</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>99.9% Uptime</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>24/7 Monitoring</li>
                    </ul>
                    <a href="/products/vps" class="block w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        Details ansehen
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
                    <a href="/products/gameserver" class="block w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        Details ansehen
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
                    <a href="/products/domain" class="block w-full bg-orange-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-orange-700 transition-colors">
                        Details ansehen
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
            <p class="text-xl text-gray-300">Ihre Vorteile bei uns im Überblick</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-4">24/7 Support</h3>
                <p class="text-gray-300">Unser deutschsprachiges Support-Team steht Ihnen rund um die Uhr zur Verfügung.</p>
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
                    <i class="fas fa-rocket text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-4">Premium Performance</h3>
                <p class="text-gray-300">Modernste Hardware und SSD-Speicher für maximale Geschwindigkeit.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-6">
            Bereit für Ihr neues Hosting?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
            Starten Sie noch heute mit SpectraHost und erleben Sie den Unterschied.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <i class="fas fa-user-plus mr-2"></i>Jetzt registrieren
            </a>
            <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                <i class="fas fa-phone mr-2"></i>Beratung anfordern
            </a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>