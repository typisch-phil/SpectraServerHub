<?php
require_once __DIR__ . '/../includes/layout.php';
renderHeader('SpectraHost - Premium Hosting Solutions');
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-20">
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                Premium <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Hosting</span> Solutions
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-3xl mx-auto">
                Entdecken Sie unsere leistungsstarken Hosting-Lösungen mit 99.9% Uptime-Garantie, 
                blitzschnellem Support und modernster Technologie.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#services" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                    Services entdecken
                </a>
                <a href="/contact" class="border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 px-8 py-3 rounded-lg font-semibold transition-colors">
                    Beratung anfordern
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-blue-600 mb-2" data-counter="50000">0</div>
                <div class="text-gray-600 dark:text-gray-400">Zufriedene Kunden</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-blue-600 mb-2" data-counter="99.9">0</div>
                <div class="text-gray-600 dark:text-gray-400">% Uptime</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-blue-600 mb-2" data-counter="24">0</div>
                <div class="text-gray-600 dark:text-gray-400">h Support</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-bold text-blue-600 mb-2" data-counter="10">0</div>
                <div class="text-gray-600 dark:text-gray-400">Jahre Erfahrung</div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="py-20 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Unsere Services
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Von Webhosting bis zu dedizierten Servern - wir haben die perfekte Lösung für Ihre Anforderungen.
            </p>
        </div>
        
        <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Services werden dynamisch geladen -->
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Warum SpectraHost?
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Unsere Vorteile machen den Unterschied für Ihren Online-Erfolg.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-rocket text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Blitzschnell</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    SSD-Storage und optimierte Server für maximale Performance.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Sicher</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Tägliche Backups und modernste Sicherheitstechnologie.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-2xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Unser Expertenteam steht Ihnen rund um die Uhr zur Verfügung.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-line text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Skalierbar</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Wachsen Sie mit unseren flexiblen Hosting-Lösungen.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-coins text-2xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Günstig</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Premium-Qualität zu fairen und transparenten Preisen.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cog text-2xl text-indigo-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Einfach</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Intuitive Control Panels und einfache Verwaltung.
                </p>
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
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Vertrauen Sie auf die Erfahrungen von über 50.000 zufriedenen Kunden.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    "Exzellenter Service und Support. Meine Website läuft seit Jahren ohne Probleme!"
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <div class="font-semibold">Maria Schmidt</div>
                        <div class="text-sm text-gray-500">Online-Shop Betreiberin</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    "Schnelle Server, faire Preise und kompetenter Support. Absolut empfehlenswert!"
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <div class="font-semibold">Thomas Müller</div>
                        <div class="text-sm text-gray-500">Web-Entwickler</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    "Der beste Hosting-Anbieter den ich je hatte. Migration war problemlos!"
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div>
                        <div class="font-semibold">Anna Weber</div>
                        <div class="text-sm text-gray-500">Blogger</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
            Bereit für Premium Hosting?
        </h2>
        <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Starten Sie noch heute mit unserem professionellen Hosting und erleben Sie den Unterschied.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold transition-colors">
                Jetzt registrieren
            </a>
            <a href="/contact" class="border border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg font-semibold transition-colors">
                Beratung anfordern
            </a>
        </div>
    </div>
</section>

<script>
// API request helper
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    
    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${errorText}`);
    }
    
    return await response.json();
}

// Load services on page load
document.addEventListener('DOMContentLoaded', function() {
    loadServices();
    animateCounters();
});

async function loadServices() {
    try {
        const data = await apiRequest('/api/services.php');
        if (data.success) {
            renderServices(data.services);
        }
    } catch (error) {
        console.error('Error loading services:', error);
    }
}

function renderServices(services) {
    const grid = document.getElementById('services-grid');
    const serviceTypes = {
        'webhosting': { icon: 'fas fa-globe', color: 'blue' },
        'vps': { icon: 'fas fa-server', color: 'green' },
        'gameserver': { icon: 'fas fa-gamepad', color: 'purple' },
        'domain': { icon: 'fas fa-link', color: 'orange' }
    };
    
    // Group services by type and show only the first 6
    const featuredServices = services.slice(0, 6);
    
    grid.innerHTML = featuredServices.map(service => {
        const type = serviceTypes[service.type] || { icon: 'fas fa-cog', color: 'gray' };
        
        return `
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-${type.color}-100 dark:bg-${type.color}-900 rounded-lg flex items-center justify-center mr-4">
                        <i class="${type.icon} text-xl text-${type.color}-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold">${service.name}</h3>
                        <div class="text-2xl font-bold text-${type.color}-600">€${service.price}/Monat</div>
                    </div>
                </div>
                
                <p class="text-gray-600 dark:text-gray-400 mb-4">${service.description}</p>
                
                <div class="space-y-2 mb-6">
                    ${service.cpu_cores > 0 ? `<div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-microchip w-4 mr-2"></i>
                        ${service.cpu_cores} CPU Core${service.cpu_cores > 1 ? 's' : ''}
                    </div>` : ''}
                    ${service.memory_gb > 0 ? `<div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-memory w-4 mr-2"></i>
                        ${service.memory_gb} GB RAM
                    </div>` : ''}
                    ${service.storage_gb > 0 ? `<div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-hdd w-4 mr-2"></i>
                        ${service.storage_gb} GB Storage
                    </div>` : ''}
                </div>
                
                <button onclick="orderService(${service.id})" class="w-full bg-${type.color}-600 hover:bg-${type.color}-700 text-white py-2 px-4 rounded-lg transition-colors">
                    Jetzt bestellen
                </button>
            </div>
        `;
    }).join('');
}

function orderService(serviceId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = `/order?service=${serviceId}`;
    <?php else: ?>
        window.location.href = `/login?redirect=/order?service=${serviceId}`;
    <?php endif; ?>
}

function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-counter'));
        let current = 0;
        const increment = target / 100;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (target === 99.9) {
                counter.textContent = current.toFixed(1);
            } else {
                counter.textContent = Math.floor(current).toLocaleString();
            }
        }, 20);
    });
}
</script>

<?php renderFooter(); ?>