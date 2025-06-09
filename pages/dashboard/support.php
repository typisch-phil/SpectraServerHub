<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $_SESSION['user'];

$database = Database::getInstance();

renderHeader('Support - SpectraHost Dashboard');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Dashboard Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Meine Services</a>
                        <a href="/dashboard/billing" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Billing</a>
                        <a href="/dashboard/support" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Support</a>
                        <a href="/order" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Bestellen</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Guthaben:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400"><?php echo number_format($user['balance'] ?? 0, 2); ?> €</span>
                    </div>
                    <span class="text-gray-700 dark:text-gray-300">Willkommen, <?php echo htmlspecialchars($user['first_name'] ?? 'Benutzer'); ?></span>
                    <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                        <a href="/admin" class="btn-outline">Admin Panel</a>
                    <?php endif; ?>
                    <a href="/api/logout" class="btn-outline">Abmelden</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Support</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Erstellen Sie Support-Tickets und verwalten Sie Ihre Anfragen</p>
        </div>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Benötigen Sie Hilfe? Erstellen Sie ein Support-Ticket oder durchsuchen Sie unsere FAQ</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Quick Actions -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schnelle Hilfe</h2>
                    
                    <div class="space-y-3">
                        <button onclick="openTicketModal()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-ticket-alt mr-3"></i>
                            Neues Ticket erstellen
                        </button>
                        
                        <a href="#faq" class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-3 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-question-circle mr-3"></i>
                            FAQ durchsuchen
                        </a>
                        
                        <a href="mailto:support@spectrahost.de" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-envelope mr-3"></i>
                            E-Mail an Support
                        </a>
                    </div>
                </div>

                <!-- Support Hours -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Support-Zeiten</h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Montag - Freitag:</span>
                            <span class="font-medium">9:00 - 18:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Samstag:</span>
                            <span class="font-medium">10:00 - 16:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Sonntag:</span>
                            <span class="font-medium">Geschlossen</span>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Support ist online</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md" id="faq">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Häufig gestellte Fragen</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- FAQ Item -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                <button class="w-full text-left p-4 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" onclick="toggleFaq(1)">
                                    <span class="font-medium text-gray-900 dark:text-white">Wie kann ich mein Passwort zurücksetzen?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-1"></i>
                                </button>
                                <div class="hidden p-4 pt-0 text-gray-600 dark:text-gray-400" id="content-1">
                                    <p>Um Ihr Passwort zurückzusetzen, klicken Sie auf der Anmeldeseite auf "Passwort vergessen" und folgen Sie den Anweisungen. Sie erhalten eine E-Mail mit einem Reset-Link.</p>
                                </div>
                            </div>

                            <!-- FAQ Item -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                <button class="w-full text-left p-4 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" onclick="toggleFaq(2)">
                                    <span class="font-medium text-gray-900 dark:text-white">Wie lange dauert die Server-Einrichtung?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-2"></i>
                                </button>
                                <div class="hidden p-4 pt-0 text-gray-600 dark:text-gray-400" id="content-2">
                                    <p>Die automatische Server-Einrichtung dauert in der Regel 5-15 Minuten. Bei besonderen Konfigurationen kann es bis zu 30 Minuten dauern.</p>
                                </div>
                            </div>

                            <!-- FAQ Item -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                <button class="w-full text-left p-4 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" onclick="toggleFaq(3)">
                                    <span class="font-medium text-gray-900 dark:text-white">Welche Zahlungsmethoden werden akzeptiert?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-3"></i>
                                </button>
                                <div class="hidden p-4 pt-0 text-gray-600 dark:text-gray-400" id="content-3">
                                    <p>Wir akzeptieren iDEAL, Kreditkarten (Visa, Mastercard), PayPal und Banküberweisungen. Alle Zahlungen werden sicher über Mollie verarbeitet.</p>
                                </div>
                            </div>

                            <!-- FAQ Item -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                <button class="w-full text-left p-4 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" onclick="toggleFaq(4)">
                                    <span class="font-medium text-gray-900 dark:text-white">Kann ich mein Service upgraden?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-4"></i>
                                </button>
                                <div class="hidden p-4 pt-0 text-gray-600 dark:text-gray-400" id="content-4">
                                    <p>Ja, Sie können Ihr Service jederzeit upgraden. Gehen Sie in Ihr Dashboard und wählen Sie das gewünschte Service aus. Die Preisdifferenz wird anteilig berechnet.</p>
                                </div>
                            </div>

                            <!-- FAQ Item -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                <button class="w-full text-left p-4 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" onclick="toggleFaq(5)">
                                    <span class="font-medium text-gray-900 dark:text-white">Wie erstelle ich ein Backup meiner Daten?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-5"></i>
                                </button>
                                <div class="hidden p-4 pt-0 text-gray-600 dark:text-gray-400" id="content-5">
                                    <p>Automatische Backups werden je nach Service täglich oder wöchentlich erstellt. Manuelle Backups können Sie über das Control Panel Ihres Services erstellen.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Neues Support-Ticket erstellen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="ticketForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategorie</label>
                        <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" required>
                            <option value="">Bitte wählen...</option>
                            <option value="technical">Technisches Problem</option>
                            <option value="billing">Abrechnung</option>
                            <option value="general">Allgemeine Frage</option>
                            <option value="abuse">Missbrauch melden</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priorität</label>
                        <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" required>
                            <option value="low">Niedrig</option>
                            <option value="medium" selected>Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="critical">Kritisch</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Betreff</label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Kurze Beschreibung des Problems" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nachricht</label>
                    <textarea rows="6" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Beschreiben Sie Ihr Problem so detailliert wie möglich..." required></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Ticket erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTicketModal() {
            document.getElementById('ticketModal').classList.remove('hidden');
            document.getElementById('ticketModal').classList.add('flex');
        }

        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }

        function toggleFaq(id) {
            const content = document.getElementById(`content-${id}`);
            const icon = document.getElementById(`icon-${id}`);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        document.getElementById('ticketForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Here you would send the ticket data to your backend
            alert('Ticket wurde erfolgreich erstellt! Sie erhalten eine Bestätigung per E-Mail.');
            closeModal();
            this.reset();
        });

        // Close modal with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>