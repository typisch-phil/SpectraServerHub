<?php
// Produkte-Übersichtsseite
renderHeader('Unsere Hosting-Produkte - SpectraHost');
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <!-- Hero Section -->
    <section class="pt-20 pb-16">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-4xl mx-auto">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">
                    Unsere <span class="text-blue-600">Hosting-Lösungen</span>
                </h1>
                <p class="text-xl text-gray-600 leading-relaxed">
                    Professionelle Hosting-Services für jeden Bedarf - von einfachen Websites bis hin zu komplexen Anwendungen
                </p>
            </div>
        </div>
    </section>

    <!-- Produktkategorien -->
    <section class="pb-20">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
                
                <!-- Webspace -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border border-gray-100">
                    <div class="bg-blue-100 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Webspace</h3>
                    <p class="text-gray-600 mb-6">Perfekt für Websites, Blogs und kleine Anwendungen mit PHP, MySQL und E-Mail-Funktionen.</p>
                    <ul class="text-sm text-gray-600 mb-8 space-y-2">
                        <li>✓ PHP 8.2 & MySQL</li>
                        <li>✓ SSL-Zertifikate inklusive</li>
                        <li>✓ E-Mail-Postfächer</li>
                        <li>✓ 99.9% Uptime-Garantie</li>
                    </ul>
                    <a href="/webspace" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200 text-center block">
                        Webspace ansehen
                    </a>
                </div>

                <!-- vServer -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border border-gray-100">
                    <div class="bg-green-100 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Virtual Server</h3>
                    <p class="text-gray-600 mb-6">Volle Root-Kontrolle für anspruchsvolle Projekte und Anwendungen mit garantierten Ressourcen.</p>
                    <ul class="text-sm text-gray-600 mb-8 space-y-2">
                        <li>✓ Root-Server Zugang</li>
                        <li>✓ SSD-Storage</li>
                        <li>✓ Backup-Service</li>
                        <li>✓ DDoS-Schutz inklusive</li>
                    </ul>
                    <a href="/vserver" class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors duration-200 text-center block">
                        vServer ansehen
                    </a>
                </div>

                <!-- Game Server -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border border-gray-100">
                    <div class="bg-purple-100 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Game Server</h3>
                    <p class="text-gray-600 mb-6">Optimierte Gaming-Server für Minecraft, CS2, Rust und viele weitere beliebte Spiele.</p>
                    <ul class="text-sm text-gray-600 mb-8 space-y-2">
                        <li>✓ Instant Setup</li>
                        <li>✓ Mod-Support</li>
                        <li>✓ Web-Interface</li>
                        <li>✓ Anti-DDoS Protection</li>
                    </ul>
                    <a href="/gameserver" class="w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition-colors duration-200 text-center block">
                        Gameserver ansehen
                    </a>
                </div>

                <!-- Domains -->
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border border-gray-100">
                    <div class="bg-orange-100 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Domains</h3>
                    <p class="text-gray-600 mb-6">Registrieren Sie Ihre Wunsch-Domain aus über 500 verfügbaren Endungen zu Top-Preisen.</p>
                    <ul class="text-sm text-gray-600 mb-8 space-y-2">
                        <li>✓ 500+ Domain-Endungen</li>
                        <li>✓ Kostenloser Transfer</li>
                        <li>✓ DNS-Management</li>
                        <li>✓ Domain-Schutz inklusive</li>
                    </ul>
                    <a href="/domain" class="w-full bg-orange-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-orange-700 transition-colors duration-200 text-center block">
                        Domains ansehen
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">24/7 Premium Support inklusive</h2>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Unser deutschsprachiges Support-Team steht Ihnen rund um die Uhr zur Verfügung und hilft bei allen Fragen zu Ihren Hosting-Services.
            </p>
            <a href="/contact" class="inline-block bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                Support kontaktieren
            </a>
        </div>
    </section>
</div>

<?php renderFooter(); ?>