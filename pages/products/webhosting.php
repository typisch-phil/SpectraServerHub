<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';

$pageTitle = 'Webhosting - SpectraHost';
renderHeader($pageTitle);

// Get webhosting services
$stmt = $db->prepare("SELECT * FROM services WHERE type = 'webhosting' AND active = 1 ORDER BY price ASC");
$stmt->execute();
$webhostingServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Professionelles Webhosting</h1>
        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
            Zuverlässiges und schnelles Webhosting für Ihre Website. 
            Von kleinen Blogs bis hin zu großen Unternehmenswebsites.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-check mr-2"></i>
                99.9% Uptime Garantie
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-shield-alt mr-2"></i>
                SSL-Zertifikate inklusive
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-headset mr-2"></i>
                24/7 Support
            </div>
        </div>
    </div>
</div>

<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Wählen Sie Ihr Webhosting-Paket</h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Alle Pakete enthalten modernste Technologien und professionellen Support
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($webhostingServices as $service): ?>
            <div class="card hover-lift">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-globe text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <div class="text-3xl font-bold text-blue-600 mb-2">
                        €<?php echo number_format($service['price'], 2); ?>/Monat
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                </div>
                
                <div class="space-y-3 mb-8">
                    <?php if ($service['storage_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-hdd text-green-500 w-5 mr-3"></i>
                        <span><?php echo $service['storage_gb']; ?> GB SSD Speicher</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($service['bandwidth_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-network-wired text-green-500 w-5 mr-3"></i>
                        <span><?php echo $service['bandwidth_gb']; ?> GB Traffic/Monat</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center">
                        <i class="fas fa-database text-green-500 w-5 mr-3"></i>
                        <span>MySQL Datenbanken</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-green-500 w-5 mr-3"></i>
                        <span>E-Mail Accounts</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-500 w-5 mr-3"></i>
                        <span>SSL Zertifikat inkl.</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-code text-green-500 w-5 mr-3"></i>
                        <span>PHP 8.2 & Node.js</span>
                    </div>
                </div>
                
                <a href="/order?service=<?php echo $service['id']; ?>" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg transition-colors text-center block">
                    Jetzt bestellen
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-gray-50 dark:bg-gray-800 py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Warum SpectraHost Webhosting?</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-rocket text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Ultraschnell</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    SSD-Speicher und modernste Server-Hardware für maximale Performance
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Sicher</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Automatische Backups, SSL-Verschlüsselung und Malware-Schutz
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cog text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Einfach</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Intuitive Control Panel und 1-Click WordPress Installation
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-2xl text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Support</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Deutscher 24/7 Support von echten Webhosting-Experten
                </p>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Häufig gestellte Fragen</h2>
        </div>
        
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800" 
                        onclick="toggleFaq(this)">
                    <span class="font-semibold">Ist ein SSL-Zertifikat bei allen Paketen inklusive?</span>
                    <i class="fas fa-chevron-down transition-transform"></i>
                </button>
                <div class="px-6 pb-4 hidden">
                    <p class="text-gray-600 dark:text-gray-400">
                        Ja, alle unsere Webhosting-Pakete enthalten ein kostenloses SSL-Zertifikat (Let's Encrypt), 
                        das automatisch installiert und erneuert wird.
                    </p>
                </div>
            </div>
            
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800" 
                        onclick="toggleFaq(this)">
                    <span class="font-semibold">Kann ich WordPress mit einem Klick installieren?</span>
                    <i class="fas fa-chevron-down transition-transform"></i>
                </button>
                <div class="px-6 pb-4 hidden">
                    <p class="text-gray-600 dark:text-gray-400">
                        Ja, unser Control Panel bietet eine 1-Click Installation für WordPress, Joomla, Drupal 
                        und viele weitere CMS-Systeme.
                    </p>
                </div>
            </div>
            
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800" 
                        onclick="toggleFaq(this)">
                    <span class="font-semibold">Wie oft werden Backups erstellt?</span>
                    <i class="fas fa-chevron-down transition-transform"></i>
                </button>
                <div class="px-6 pb-4 hidden">
                    <p class="text-gray-600 dark:text-gray-400">
                        Wir erstellen täglich automatische Backups Ihrer Website und behalten diese 30 Tage lang auf. 
                        Sie können jederzeit selbst Backups über das Control Panel wiederherstellen.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFaq(button) {
    const content = button.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<?php renderFooter(); ?>