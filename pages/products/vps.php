<?php 
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

$pageTitle = 'vServer / VPS - SpectraHost';
renderHeader($pageTitle);

// Get VPS services
$stmt = $db->prepare("SELECT * FROM services WHERE type = 'vps' AND active = 1 ORDER BY price ASC");
$stmt->execute();
$vpsServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-gradient-to-r from-green-600 to-green-800 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Leistungsstarke vServer</h1>
        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
            Virtuelle Server mit voller Root-Berechtigung und garantierten Ressourcen. 
            Automatische Bereitstellung über Proxmox VE in wenigen Minuten.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-server mr-2"></i>
                Automatische Bereitstellung
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-crown mr-2"></i>
                Root-Zugriff inklusive
            </div>
            <div class="flex items-center bg-white/20 rounded-lg px-4 py-2">
                <i class="fas fa-tachometer-alt mr-2"></i>
                SSD NVMe Storage
            </div>
        </div>
    </div>
</div>

<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Wählen Sie Ihren vServer</h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Alle vServer werden automatisch über unsere Proxmox VE Infrastruktur bereitgestellt
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($vpsServices as $service): ?>
            <div class="card hover-lift">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-server text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <div class="text-3xl font-bold text-green-600 mb-2">
                        €<?php echo number_format($service['price'], 2); ?>/Monat
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                </div>
                
                <div class="space-y-3 mb-8">
                    <?php if ($service['cpu_cores'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-microchip text-green-500 w-5 mr-3"></i>
                        <span><?php echo $service['cpu_cores']; ?> vCPU Core<?php echo $service['cpu_cores'] > 1 ? 's' : ''; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($service['memory_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-memory text-green-500 w-5 mr-3"></i>
                        <span><?php echo $service['memory_gb']; ?> GB RAM</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($service['storage_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-hdd text-green-500 w-5 mr-3"></i>
                        <span><?php echo $service['storage_gb']; ?> GB SSD NVMe</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($service['bandwidth_gb'] > 0): ?>
                    <div class="flex items-center">
                        <i class="fas fa-network-wired text-green-500 w-5 mr-3"></i>
                        <span><?php echo $service['bandwidth_gb']; ?> GB Traffic</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center">
                        <i class="fas fa-crown text-green-500 w-5 mr-3"></i>
                        <span>Root-Zugriff (SSH)</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-500 w-5 mr-3"></i>
                        <span>DDoS-Schutz</span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-clock text-green-500 w-5 mr-3"></i>
                        <span>Setup in 5 Minuten</span>
                    </div>
                </div>
                
                <a href="/order?service=<?php echo $service['id']; ?>" 
                   class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-lg transition-colors text-center block">
                    Jetzt bestellen
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- OS Selection Preview -->
<div class="bg-gray-50 dark:bg-gray-800 py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Betriebssystem-Auswahl</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Wählen Sie aus einer Vielzahl von vorinstallierten Betriebssystemen
            </p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-6">
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fab fa-ubuntu text-2xl text-orange-600"></i>
                </div>
                <span class="text-sm font-medium">Ubuntu 22.04</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fab fa-centos text-2xl text-red-600"></i>
                </div>
                <span class="text-sm font-medium">CentOS 9</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fab fa-debian text-2xl text-blue-600"></i>
                </div>
                <span class="text-sm font-medium">Debian 12</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fab fa-windows text-2xl text-blue-600"></i>
                </div>
                <span class="text-sm font-medium">Windows 2022</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fab fa-suse text-2xl text-green-600"></i>
                </div>
                <span class="text-sm font-medium">openSUSE</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fab fa-fedora text-2xl text-gray-600"></i>
                </div>
                <span class="text-sm font-medium">Fedora</span>
            </div>
            
            <div class="text-center p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-plus text-2xl text-gray-600"></i>
                </div>
                <span class="text-sm font-medium">Weitere</span>
            </div>
        </div>
    </div>
</div>

<!-- Technical Specifications -->
<div class="py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Technische Details</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div>
                <h3 class="text-2xl font-bold mb-6">Hardware & Performance</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-server text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">Enterprise Hardware</div>
                            <p class="text-gray-600 dark:text-gray-400">Intel Xeon Prozessoren der neuesten Generation</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-memory text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">DDR4 ECC RAM</div>
                            <p class="text-gray-600 dark:text-gray-400">Fehlerkorrigierender Arbeitsspeicher für maximale Stabilität</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-hdd text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">NVMe SSD Storage</div>
                            <p class="text-gray-600 dark:text-gray-400">Ultraschnelle NVMe SSDs mit bis zu 3.500 MB/s</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-network-wired text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">1 Gbit/s Uplink</div>
                            <p class="text-gray-600 dark:text-gray-400">Dedizierte Bandbreite pro vServer</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-2xl font-bold mb-6">Features & Support</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-shield-alt text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">DDoS-Schutz</div>
                            <p class="text-gray-600 dark:text-gray-400">Automatischer Schutz vor DDoS-Attacken inklusive</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-backup text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">Backup-Service</div>
                            <p class="text-gray-600 dark:text-gray-400">Automatische tägliche Snapshots (optional)</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-headset text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">24/7 Support</div>
                            <p class="text-gray-600 dark:text-gray-400">Deutscher Support via Ticket-System</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-clock text-green-500 w-6 mr-3 mt-1"></i>
                        <div>
                            <div class="font-semibold">99.9% Uptime SLA</div>
                            <p class="text-gray-600 dark:text-gray-400">Garantierte Verfügbarkeit mit SLA</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>