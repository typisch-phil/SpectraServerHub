<?php
// 404 wird über index.php geladen - alle includes sind bereits verfügbar
renderHeader('Seite nicht gefunden - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <div class="w-32 h-32 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="text-white font-bold text-6xl">404</span>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Seite nicht gefunden
            </h1>
            
            <p class="text-gray-600 dark:text-gray-400 mb-8">
                Die angeforderte Seite konnte nicht gefunden werden. 
                Möglicherweise wurde sie verschoben oder existiert nicht mehr.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/" class="btn-primary">
                    <i class="fas fa-home mr-2"></i>
                    Zur Startseite
                </a>
                <a href="/contact" class="btn-outline">
                    <i class="fas fa-envelope mr-2"></i>
                    Kontakt
                </a>
            </div>
        </div>
        
        <!-- Helpful Links -->
        <div class="border-t dark:border-gray-700 pt-8">
            <h3 class="text-lg font-semibold mb-4">Hilfreiche Links</h3>
            <div class="space-y-2">
                <a href="/" class="block text-blue-600 hover:text-blue-500">Startseite</a>
                <a href="#services" class="block text-blue-600 hover:text-blue-500">Services</a>
                <a href="/contact" class="block text-blue-600 hover:text-blue-500">Kontakt</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="block text-blue-600 hover:text-blue-500">Dashboard</a>
                <?php else: ?>
                    <a href="/login" class="block text-blue-600 hover:text-blue-500">Anmelden</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>