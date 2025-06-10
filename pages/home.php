<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

$pageTitle = 'SpectraHost - Premium Hosting Solutions';
$pageDescription = 'Professionelle Hosting-Lösungen mit erstklassigem Support, modernster Technologie und unschlagbarer Performance für Ihr Online-Business.';
renderHeader($pageTitle, $pageDescription);
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-purple-600 to-blue-800">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-black opacity-10"></div>
    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grid\" width=\"10\" height=\"10\" patternUnits=\"userSpaceOnUse\"><path d=\"M 10 0 L 0 0 0 10\" fill=\"none\" stroke=\"white\" stroke-width=\"0.5\" opacity=\"0.1\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grid)\"/></svg>');"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6">
                Premium
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500">
                    Hosting
                </span>
                <br>Solutions
            </h1>
            <p class="text-xl md:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto leading-relaxed">
                Professionelle Hosting-Lösungen mit erstklassigem Support, modernster Technologie und unschlagbarer Performance für Ihr Online-Business.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="/services" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <i class="fas fa-rocket mr-2"></i>Services erkunden
                </a>
                <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-blue-600 transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-phone mr-2"></i>Kontakt aufnehmen
                </a>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-1">99.9%</div>
                    <div class="text-blue-200 text-sm">Uptime</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-1">24/7</div>
                    <div class="text-blue-200 text-sm">Support</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-1">1000+</div>
                    <div class="text-blue-200 text-sm">Kunden</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-1">10+</div>
                    <div class="text-blue-200 text-sm">Jahre</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-20 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Warum SpectraHost?
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                Wir bieten die perfekte Kombination aus Leistung, Zuverlässigkeit und Support für Ihre digitalen Projekte.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Blitzschnell</h3>
                <p class="text-gray-600 dark:text-gray-400">Ultraschnelle SSD-Speicher und optimierte Server-Hardware für maximale Performance.</p>
            </div>
            
            <div class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Sicher</h3>
                <p class="text-gray-600 dark:text-gray-400">Modernste Sicherheitstechnologien und regelmäßige Backups schützen Ihre Daten.</p>
            </div>
            
            <div class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-headset text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">24/7 Support</h3>
                <p class="text-gray-600 dark:text-gray-400">Unser deutschsprachiges Support-Team steht Ihnen rund um die Uhr zur Verfügung.</p>
            </div>
            
            <div class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Skalierbar</h3>
                <p class="text-gray-600 dark:text-gray-400">Flexible Tarife, die mit Ihrem Business mitwachsen und sich anpassen lassen.</p>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Unsere Services
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                Von einfachen Webspace-Paketen bis hin zu leistungsstarken Dedicated Servern - wir haben die passende Lösung für Sie.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-8 h-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-globe text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Webspace</h3>
                        <p class="mb-6 opacity-90 leading-relaxed">Professionelles Web-Hosting mit PHP, MySQL und SSL-Zertifikaten.</p>
                        <a href="/webspace" class="inline-block bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                            Mehr erfahren
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-8 h-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-server text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">VServer</h3>
                        <p class="mb-6 opacity-90 leading-relaxed">Leistungsstarke virtuelle Server mit Root-Zugang und voller Kontrolle.</p>
                        <a href="/vserver" class="inline-block bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                            Mehr erfahren
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-8 h-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-gamepad text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">GameServer</h3>
                        <p class="mb-6 opacity-90 leading-relaxed">Optimierte Game-Server für Minecraft, CS2, ARK und viele weitere Spiele.</p>
                        <a href="/gameserver" class="inline-block bg-white text-purple-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                            Mehr erfahren
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white p-8 h-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-link text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Domains</h3>
                        <p class="mb-6 opacity-90 leading-relaxed">Domain-Registration und -Verwaltung mit über 500 verfügbaren Endungen.</p>
                        <a href="/domain" class="inline-block bg-white text-orange-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                            Mehr erfahren
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-20 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Was unsere Kunden sagen
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                Vertrauen Sie auf die Erfahrungen zufriedener Kunden
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4 italic">
                    "Exzellenter Service und Support. Meine Website läuft seit Jahren stabil und schnell bei SpectraHost."
                </p>
                <div class="font-semibold text-gray-900 dark:text-white">Thomas M.</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Online-Shop Betreiber</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4 italic">
                    "Der GameServer läuft perfekt. Keine Lags, super Performance und der Support antwortet immer schnell."
                </p>
                <div class="font-semibold text-gray-900 dark:text-white">Alex K.</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Gaming Community</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4 italic">
                    "Sehr professionell und zuverlässig. Die VServer haben eine top Performance und das Preis-Leistungs-Verhältnis stimmt."
                </p>
                <div class="font-semibold text-gray-900 dark:text-white">Sarah L.</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Web-Entwicklerin</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            Bereit für Premium Hosting?
        </h2>
        <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Starten Sie noch heute mit unseren leistungsstarken Hosting-Lösungen und erleben Sie den Unterschied.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-user-plus mr-2"></i>Jetzt registrieren
            </a>
            <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-blue-600 transition-all duration-300">
                <i class="fas fa-phone mr-2"></i>Beratung anfragen
            </a>
        </div>
    </div>
</section>

<?php renderFooter(); ?>