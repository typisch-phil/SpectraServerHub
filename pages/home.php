<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpectraHost - Premium Hosting Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">S</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">SpectraHost</span>
                    </a>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/" class="text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                        <a href="/services" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Services</a>
                        <a href="/webspace" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Webspace</a>
                        <a href="/vserver" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">VServer</a>
                        <a href="/gameserver" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">GameServer</a>
                        <a href="/domain" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Domains</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                    <a href="/register" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">Registrieren</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-700 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Premium Hosting
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500">
                    Solutions
                </span>
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                Professionelle Hosting-Lösungen mit erstklassigem Support, modernster Technologie und unschlagbarer Performance für Ihr Online-Business.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/services" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    Services erkunden
                </a>
                <a href="/contact" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                    Kontakt aufnehmen
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Warum SpectraHost?
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Wir bieten die perfekte Kombination aus Leistung, Zuverlässigkeit und Support für Ihre digitalen Projekte.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-rocket text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Blitzschnell</h3>
                    <p class="text-gray-600">Ultraschnelle SSD-Speicher und optimierte Server-Hardware für maximale Performance.</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Sicher</h3>
                    <p class="text-gray-600">Modernste Sicherheitstechnologien und regelmäßige Backups schützen Ihre Daten.</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Unser deutschsprachiges Support-Team steht Ihnen rund um die Uhr zur Verfügung.</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Skalierbar</h3>
                    <p class="text-gray-600">Flexible Tarife, die mit Ihrem Business mitwachsen und sich anpassen lassen.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Unsere Services
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Von einfachen Webspace-Paketen bis hin zu leistungsstarken Dedicated Servern - wir haben die passende Lösung für Sie.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="group hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-8 rounded-xl shadow-lg">
                        <div class="text-center">
                            <i class="fas fa-globe text-4xl mb-4"></i>
                            <h3 class="text-2xl font-bold mb-4">Webspace</h3>
                            <p class="mb-6 opacity-90">Professionelles Web-Hosting mit PHP, MySQL und SSL-Zertifikaten.</p>
                            <a href="/webspace" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                Mehr erfahren
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="group hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-8 rounded-xl shadow-lg">
                        <div class="text-center">
                            <i class="fas fa-server text-4xl mb-4"></i>
                            <h3 class="text-2xl font-bold mb-4">VServer</h3>
                            <p class="mb-6 opacity-90">Leistungsstarke virtuelle Server mit Root-Zugang und voller Kontrolle.</p>
                            <a href="/vserver" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                Mehr erfahren
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="group hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-8 rounded-xl shadow-lg">
                        <div class="text-center">
                            <i class="fas fa-gamepad text-4xl mb-4"></i>
                            <h3 class="text-2xl font-bold mb-4">GameServer</h3>
                            <p class="mb-6 opacity-90">Optimierte Game-Server für Minecraft, CS2, ARK und viele weitere Spiele.</p>
                            <a href="/gameserver" class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                Mehr erfahren
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="group hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white p-8 rounded-xl shadow-lg">
                        <div class="text-center">
                            <i class="fas fa-link text-4xl mb-4"></i>
                            <h3 class="text-2xl font-bold mb-4">Domains</h3>
                            <p class="mb-6 opacity-90">Domain-Registration und -Verwaltung mit über 500 verfügbaren Endungen.</p>
                            <a href="/domain" class="bg-white text-orange-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                Mehr erfahren
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold">S</span>
                        </div>
                        <span class="text-xl font-bold">SpectraHost</span>
                    </div>
                    <p class="text-gray-400">
                        Premium Hosting Solutions für professionelle Ansprüche.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Services</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/webspace" class="hover:text-white transition-colors">Webspace</a></li>
                        <li><a href="/vserver" class="hover:text-white transition-colors">VServer</a></li>
                        <li><a href="/gameserver" class="hover:text-white transition-colors">GameServer</a></li>
                        <li><a href="/domain" class="hover:text-white transition-colors">Domains</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Unternehmen</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/contact" class="hover:text-white transition-colors">Kontakt</a></li>
                        <li><a href="/impressum" class="hover:text-white transition-colors">Impressum</a></li>
                        <li><a href="/datenschutz" class="hover:text-white transition-colors">Datenschutz</a></li>
                        <li><a href="/agb" class="hover:text-white transition-colors">AGB</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Kontakt</h3>
                    <div class="space-y-2 text-gray-400">
                        <p><i class="fas fa-envelope mr-2"></i> info@spectrahost.de</p>
                        <p><i class="fas fa-phone mr-2"></i> +49 (0) 123 456789</p>
                        <p><i class="fas fa-clock mr-2"></i> 24/7 Support</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 SpectraHost. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>
</body>
</html>