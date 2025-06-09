<?php
require_once '../includes/layout.php';
renderHeader('Kontakt - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-12 text-center">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Kontakt</h1>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                    Haben Sie Fragen oder benötigen Sie Unterstützung? Unser Team steht Ihnen gerne zur Verfügung.
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Contact Information -->
            <div>
                <h2 class="text-2xl font-bold mb-6">Kontaktinformationen</h2>
                
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-map-marker-alt text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Adresse</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                SpectraHost GmbH<br>
                                Musterstraße 123<br>
                                12345 Musterstadt<br>
                                Deutschland
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-phone text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Telefon</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                +49 (0) 123 456789<br>
                                Mo-Fr: 9:00 - 18:00 Uhr
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-envelope text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">E-Mail</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                <a href="mailto:support@spectrahost.de" class="text-blue-600 hover:text-blue-500">
                                    support@spectrahost.de
                                </a><br>
                                <a href="mailto:info@spectrahost.de" class="text-blue-600 hover:text-blue-500">
                                    info@spectrahost.de
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Support-Zeiten</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                24/7 Online-Support<br>
                                Telefon: Mo-Fr 9:00-18:00
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="mt-8">
                    <h3 class="font-semibold mb-4">Folgen Sie uns</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-blue-400 rounded-lg flex items-center justify-center text-white hover:bg-blue-500 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-blue-800 rounded-lg flex items-center justify-center text-white hover:bg-blue-900 transition-colors">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="card">
                <h2 class="text-2xl font-bold mb-6">Nachricht senden</h2>
                
                <form id="contactForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="form-label">Vorname *</label>
                            <input type="text" id="firstName" name="firstName" required class="form-input">
                        </div>
                        <div>
                            <label for="lastName" class="form-label">Nachname *</label>
                            <input type="text" id="lastName" name="lastName" required class="form-input">
                        </div>
                    </div>
                    
                    <div>
                        <label for="email" class="form-label">E-Mail-Adresse *</label>
                        <input type="email" id="email" name="email" required class="form-input">
                    </div>
                    
                    <div>
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>
                    
                    <div>
                        <label for="subject" class="form-label">Betreff *</label>
                        <select id="subject" name="subject" required class="form-input">
                            <option value="">Bitte wählen...</option>
                            <option value="general">Allgemeine Anfrage</option>
                            <option value="sales">Vertrieb</option>
                            <option value="support">Technischer Support</option>
                            <option value="billing">Abrechnung</option>
                            <option value="other">Sonstiges</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="message" class="form-label">Nachricht *</label>
                        <textarea id="message" name="message" rows="6" required class="form-input" 
                                  placeholder="Beschreiben Sie Ihr Anliegen..."></textarea>
                    </div>
                    
                    <div class="flex items-center">
                        <input id="privacy" name="privacy" type="checkbox" required
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="privacy" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Ich akzeptiere die <a href="#" class="text-blue-600 hover:text-blue-500">Datenschutzerklärung</a> *
                        </label>
                    </div>
                    
                    <button type="submit" id="contactBtn" class="w-full btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Nachricht senden
                    </button>
                </form>
                
                <div id="message-status" class="mt-4 hidden"></div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="mt-16">
            <h2 class="text-3xl font-bold text-center mb-12">Häufig gestellte Fragen</h2>
            
            <div class="max-w-3xl mx-auto space-y-4">
                <div class="card">
                    <button class="w-full text-left font-semibold py-3 flex justify-between items-center" onclick="toggleFAQ(this)">
                        Wie schnell ist der Support?
                        <i class="fas fa-chevron-down transition-transform"></i>
                    </button>
                    <div class="hidden mt-3 text-gray-600 dark:text-gray-400">
                        Unser Support-Team antwortet in der Regel innerhalb von 2 Stunden. Bei kritischen Problemen oft sogar deutlich schneller.
                    </div>
                </div>
                
                <div class="card">
                    <button class="w-full text-left font-semibold py-3 flex justify-between items-center" onclick="toggleFAQ(this)">
                        Bieten Sie 24/7 Support an?
                        <i class="fas fa-chevron-down transition-transform"></i>
                    </button>
                    <div class="hidden mt-3 text-gray-600 dark:text-gray-400">
                        Ja, unser Online-Support ist 24/7 verfügbar. Telefonischer Support steht Mo-Fr von 9:00-18:00 Uhr zur Verfügung.
                    </div>
                </div>
                
                <div class="card">
                    <button class="w-full text-left font-semibold py-3 flex justify-between items-center" onclick="toggleFAQ(this)">
                        Wie kann ich mein Hosting upgraden?
                        <i class="fas fa-chevron-down transition-transform"></i>
                    </button>
                    <div class="hidden mt-3 text-gray-600 dark:text-gray-400">
                        Ein Upgrade ist jederzeit über Ihr Dashboard möglich. Kontaktieren Sie uns für eine persönliche Beratung.
                    </div>
                </div>
                
                <div class="card">
                    <button class="w-full text-left font-semibold py-3 flex justify-between items-center" onclick="toggleFAQ(this)">
                        Gibt es eine Geld-zurück-Garantie?
                        <i class="fas fa-chevron-down transition-transform"></i>
                    </button>
                    <div class="hidden mt-3 text-gray-600 dark:text-gray-400">
                        Ja, wir bieten eine 30-Tage Geld-zurück-Garantie für alle Hosting-Pakete.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('contactBtn');
    const statusDiv = document.getElementById('message-status');
    
    SpectraHost.setLoading(btn, true);
    statusDiv.className = 'mt-4 hidden';
    
    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Simulate form submission (replace with actual API endpoint)
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        statusDiv.className = 'mt-4 alert alert-success';
        statusDiv.textContent = 'Vielen Dank für Ihre Nachricht! Wir werden uns schnellstmöglich bei Ihnen melden.';
        
        // Reset form
        this.reset();
        
    } catch (error) {
        statusDiv.className = 'mt-4 alert alert-error';
        statusDiv.textContent = 'Fehler beim Senden der Nachricht. Bitte versuchen Sie es erneut.';
    }
    
    setLoading(btn, false);
});

function toggleFAQ(button) {
    const content = button.nextElementSibling;
    const icon = button.querySelector('i');
    
    content.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}
</script>

<?php renderFooter(); ?>