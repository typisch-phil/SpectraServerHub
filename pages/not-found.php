<?php
require_once __DIR__ . '/../includes/layout.php';
http_response_code(404);
renderHeader('404 - Seite nicht gefunden - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4">
    <div class="max-w-lg w-full text-center">
        <div class="mb-8">
            <h1 class="text-9xl font-bold text-blue-600 dark:text-blue-400">404</h1>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mt-4">Seite nicht gefunden</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Die angeforderte Seite konnte nicht gefunden werden.
            </p>
        </div>
        
        <div class="space-y-4">
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                <i class="fas fa-home mr-2"></i>
                Zur Startseite
            </a>
            
            <div class="flex justify-center space-x-4 text-sm">
                <a href="/login" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Anmelden
                </a>
                <a href="/order" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Services bestellen
                </a>
                <a href="/contact" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Kontakt
                </a>
            </div>
        </div>
        
        <div class="mt-12 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Unsere Services</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="text-left">
                    <h4 class="font-medium text-gray-900 dark:text-white">Hosting</h4>
                    <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Webhosting</li>
                        <li>VPS Server</li>
                        <li>Game Server</li>
                    </ul>
                </div>
                <div class="text-left">
                    <h4 class="font-medium text-gray-900 dark:text-white">Domains</h4>
                    <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                        <li>.de Domains</li>
                        <li>.com Domains</li>
                        <li>Weitere TLDs</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>