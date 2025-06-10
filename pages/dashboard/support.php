<?php
// Neues Support Dashboard mit MySQL-Integration
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];
$db = Database::getInstance();

// Support-Daten aus der Datenbank laden
try {
    // Support Tickets laden
    $stmt = $db->prepare("
        SELECT st.*, s.name as service_name, stt.name as service_type_name,
               COALESCE((SELECT created_at FROM support_replies WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1), st.created_at) as last_activity
        FROM support_tickets st 
        LEFT JOIN services s ON st.service_id = s.id 
        LEFT JOIN service_types stt ON s.service_type_id = stt.id 
        WHERE st.user_id = ? 
        ORDER BY st.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ticket-Statistiken
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM support_tickets WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $ticket_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Häufige Fragen aus der Datenbank
    $stmt = $db->prepare("
        SELECT * FROM faq_items 
        WHERE is_active = 1 
        ORDER BY sort_order ASC, views DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $faq_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Support-Kategorien
    $stmt = $db->prepare("
        SELECT * FROM support_categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    $stmt->execute();
    $support_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aktuelle Services für Ticket-Erstellung
    $stmt = $db->prepare("
        SELECT s.id, s.name, st.name as service_type_name 
        FROM services s 
        LEFT JOIN service_types st ON s.service_type_id = st.id 
        WHERE s.user_id = ? AND s.status = 'active' 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Support data error: " . $e->getMessage());
    $tickets = [];
    $ticket_stats = [];
    $faq_items = [];
    $support_categories = [];
    $user_services = [];
}

renderHeader('Support - Dashboard');
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">SpectraHost</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-500 hover:text-gray-700 px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-blue-600 border-b-2 border-blue-600 px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700" onclick="showNewTicketModal()">
                        <i class="fas fa-plus mr-2"></i>Neues Ticket
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Support Center</h1>
                    <p class="mt-2 text-gray-600">Wir helfen Ihnen bei allen Fragen zu Ihren Services</p>
                </div>
                <div class="flex space-x-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $ticket_stats['open'] ?? 0; ?></div>
                        <div class="text-sm text-gray-500">Offen</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600"><?php echo $ticket_stats['pending'] ?? 0; ?></div>
                        <div class="text-sm text-gray-500">In Bearbeitung</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $ticket_stats['closed'] ?? 0; ?></div>
                        <div class="text-sm text-gray-500">Geschlossen</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Quick Help -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-8 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium">Brauchen Sie Hilfe?</h3>
                                <p class="text-blue-100 mt-2">Unser Support-Team ist 24/7 für Sie da</p>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i class="fas fa-headset text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-6 flex space-x-3">
                            <button class="bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100" onclick="showNewTicketModal()">
                                <i class="fas fa-plus mr-2"></i>Ticket erstellen
                            </button>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 border border-white border-opacity-30">
                                <i class="fas fa-phone mr-2"></i>Anrufen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Support Tickets -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Meine Tickets</h3>
                            <div class="flex space-x-2">
                                <button class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1 rounded-lg border border-gray-300">Alle</button>
                                <button class="text-sm text-white bg-blue-600 px-3 py-1 rounded-lg">Offen</button>
                                <button class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1 rounded-lg border border-gray-300">Geschlossen</button>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (!empty($tickets)): ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="p-6 hover:bg-gray-50 cursor-pointer" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 text-sm font-medium rounded-full 
                                                <?php 
                                                switch($ticket['priority']) {
                                                    case 'high': echo 'bg-red-100 text-red-800'; break;
                                                    case 'medium': echo 'bg-orange-100 text-orange-800'; break;
                                                    case 'low': echo 'bg-green-100 text-green-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                            <span class="px-3 py-1 text-sm font-medium rounded-full 
                                                <?php 
                                                switch($ticket['status']) {
                                                    case 'open': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'pending': echo 'bg-orange-100 text-orange-800'; break;
                                                    case 'closed': echo 'bg-green-100 text-green-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            #<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </div>
                                    </div>
                                    
                                    <h4 class="text-lg font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-600">
                                        <div class="flex items-center space-x-4">
                                            <?php if ($ticket['service_name']): ?>
                                                <span><i class="fas fa-server mr-1"></i><?php echo htmlspecialchars($ticket['service_name']); ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-calendar mr-1"></i><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></span>
                                            <span><i class="fas fa-clock mr-1"></i>Letzte Aktivität: <?php echo date('d.m.Y H:i', strtotime($ticket['last_activity'])); ?></span>
                                        </div>
                                        <button class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-8 text-center">
                                <i class="fas fa-ticket-alt text-gray-300 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Tickets vorhanden</h3>
                                <p class="text-gray-600 mb-6">Sie haben noch keine Support-Tickets erstellt.</p>
                                <button class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700" onclick="showNewTicketModal()">
                                    <i class="fas fa-plus mr-2"></i>Erstes Ticket erstellen
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Contact Methods -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Kontakt</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-ticket-alt text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Support Ticket</p>
                                    <p class="text-xs text-gray-500">Beste Option für technische Fragen</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-phone text-green-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">+49 (0) 123 456 789</p>
                                    <p class="text-xs text-gray-500">24/7 Telefon-Support</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-envelope text-purple-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">support@spectrahost.de</p>
                                    <p class="text-xs text-gray-500">E-Mail Support</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <i class="fab fa-discord text-indigo-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Discord Chat</p>
                                    <p class="text-xs text-gray-500">Live-Chat für schnelle Hilfe</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <?php if (!empty($faq_items)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Häufige Fragen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($faq_items as $faq): ?>
                                <div class="cursor-pointer" onclick="toggleFaq(<?php echo $faq['id']; ?>)">
                                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($faq['question']); ?></p>
                                        <i class="fas fa-chevron-down text-gray-400" id="faq-icon-<?php echo $faq['id']; ?>"></i>
                                    </div>
                                    <div class="hidden mt-2 p-3 bg-gray-50 rounded-lg" id="faq-answer-<?php echo $faq['id']; ?>">
                                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="/faq" class="text-sm text-blue-600 hover:text-blue-800">Alle FAQs anzeigen →</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Support Categories -->
                <?php if (!empty($support_categories)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Support-Kategorien</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <?php foreach ($support_categories as $category): ?>
                                <a href="#" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <i class="fas <?php echo $category['icon'] ?? 'fa-question-circle'; ?> text-blue-600 w-5"></i>
                                        <span class="ml-3 text-sm font-medium"><?php echo htmlspecialchars($category['name']); ?></span>
                                    </div>
                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- New Ticket Modal -->
<div id="newTicketModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-medium text-gray-900">Neues Support Ticket erstellen</h3>
                <button onclick="hideNewTicketModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="newTicketForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Betreff *</label>
                        <input type="text" name="subject" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kurze Beschreibung des Problems" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priorität</label>
                        <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low">Niedrig</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">Hoch</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategorie</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Allgemein</option>
                            <?php foreach ($support_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Betroffener Service</label>
                        <select name="service_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Kein spezifischer Service</option>
                            <?php foreach ($user_services as $service): ?>
                                <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name'] . ' (' . $service['service_type_name'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung *</label>
                    <textarea name="description" rows="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Detaillierte Beschreibung Ihres Anliegens..." required></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Anhänge (optional)</label>
                    <input type="file" name="attachments[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip">
                    <p class="text-xs text-gray-500 mt-1">Maximale Dateigröße: 10MB pro Datei</p>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="hideNewTicketModal()" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Ticket erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showNewTicketModal() {
    document.getElementById('newTicketModal').classList.remove('hidden');
}

function hideNewTicketModal() {
    document.getElementById('newTicketModal').classList.add('hidden');
}

function viewTicket(ticketId) {
    window.location.href = '/dashboard/support/ticket/' + ticketId;
}

function toggleFaq(faqId) {
    const answer = document.getElementById('faq-answer-' + faqId);
    const icon = document.getElementById('faq-icon-' + faqId);
    
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        answer.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

// Form submission
document.getElementById('newTicketForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/api/support/create-ticket', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideNewTicketModal();
            window.location.reload();
        } else {
            alert('Fehler beim Erstellen des Tickets: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Erstellen des Tickets');
    });
});
</script>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderFooter(); ?>