<?php
// 404 - Seite nicht gefunden
renderHeader('Seite nicht gefunden - SpectraHost');
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto text-center">
            <!-- 404 Icon -->
            <div class="mb-8">
                <div class="w-32 h-32 mx-auto bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>

            <!-- 404 Text -->
            <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Seite nicht gefunden</h2>
            <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                Die angeforderte Seite konnte leider nicht gefunden werden. Sie wurde möglicherweise verschoben, gelöscht oder der Link ist fehlerhaft.
            </p>

            <!-- Navigation Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                    Zur Startseite
                </a>
                <a href="/products" class="bg-white text-blue-600 border-2 border-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors duration-200">
                    Unsere Produkte
                </a>
                <a href="/contact" class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-semibold hover:bg-gray-200 transition-colors duration-200">
                    Support kontaktieren
                </a>
            </div>

            <!-- Popular Pages -->
            <div class="mt-12 pt-12 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Beliebte Seiten</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="/webspace" class="p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 text-center">
                        <div class="text-blue-600 font-semibold">Webspace</div>
                        <div class="text-sm text-gray-600">Hosting-Pakete</div>
                    </a>
                    <a href="/vserver" class="p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 text-center">
                        <div class="text-green-600 font-semibold">vServer</div>
                        <div class="text-sm text-gray-600">Virtual Server</div>
                    </a>
                    <a href="/gameserver" class="p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 text-center">
                        <div class="text-purple-600 font-semibold">Game Server</div>
                        <div class="text-sm text-gray-600">Gaming-Hosting</div>
                    </a>
                    <a href="/domain" class="p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 text-center">
                        <div class="text-orange-600 font-semibold">Domains</div>
                        <div class="text-sm text-gray-600">Domain-Registration</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>