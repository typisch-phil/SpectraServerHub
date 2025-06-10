<?php
require_once __DIR__ . '/../../includes/dashboard-layout.php';

// Dark Version Support Dashboard
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];
$db = Database::getInstance();

// Support-Daten aus der Datenbank laden
try {
    // Support Tickets des Benutzers
    $stmt = $db->prepare("
        SELECT st.*, sc.name as category_name, s.name as service_name
        FROM support_tickets st 
        LEFT JOIN support_categories sc ON st.category_id = sc.id 
        LEFT JOIN services s ON st.service_id = s.id 
        WHERE st.user_id = ? 
        ORDER BY st.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ticket-Statistiken
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM support_tickets WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $ticket_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Support-Kategorien
    $stmt = $db->prepare("SELECT * FROM support_categories WHERE is_active = 1 ORDER BY sort_order");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // FAQ Items
    $stmt = $db->prepare("SELECT * FROM faq_items WHERE is_active = 1 ORDER BY views DESC LIMIT 10");
    $stmt->execute();
    $faq_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Benutzer Services für Ticket-Erstellung
    $stmt = $db->prepare("SELECT id, name FROM services WHERE user_id = ? AND status = 'active' ORDER BY name");
    $stmt->execute([$user_id]);
    $user_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Support data error: " . $e->getMessage());
    $tickets = [];
    $ticket_stats = [];
    $categories = [];
    $faq_items = [];
    $user_services = [];
}

renderDashboardHeader('Support - Dashboard');
?>

<div class="min-h-screen bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-gray-800 shadow-lg border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost</span>
                    </a>
                    <div class="ml-10 flex space-x-8">
                        <a href="/dashboard" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="/dashboard/services" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Services</a>
                        <a href="/dashboard/billing" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700" onclick="showCreateTicketModal()">
                        <i class="fas fa-plus mr-2"></i>Neues Ticket
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Support Center</h1>
                    <p class="mt-2 text-gray-400">Wir helfen Ihnen gerne bei allen Fragen und Problemen</p>
                </div>
                <div class="flex space-x-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400"><?php echo $ticket_stats['open'] ?? 0; ?></div>
                        <div class="text-sm text-gray-400">Offen</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-400"><?php echo $ticket_stats['pending'] ?? 0; ?></div>
                        <div class="text-sm text-gray-400">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-400"><?php echo $ticket_stats['closed'] ?? 0; ?></div>
                        <div class="text-sm text-gray-400">Geschlossen</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Tickets -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 mb-8">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">Meine Support Tickets</h3>
                            <button class="text-sm text-blue-400 hover:text-blue-300">Alle anzeigen</button>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-700">
                        <?php if (!empty($tickets)): ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="p-6 hover:bg-gray-750">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php 
                                                    switch($ticket['status']) {
                                                        case 'open': echo 'bg-green-900 text-green-400'; break;
                                                        case 'pending': echo 'bg-orange-900 text-orange-400'; break;
                                                        case 'closed': echo 'bg-gray-700 text-gray-300'; break;
                                                        default: echo 'bg-blue-900 text-blue-400';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($ticket['status']); ?>
                                                </span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php 
                                                    switch($ticket['priority']) {
                                                        case 'high': echo 'bg-red-900 text-red-400'; break;
                                                        case 'medium': echo 'bg-orange-900 text-orange-400'; break;
                                                        case 'low': echo 'bg-green-900 text-green-400'; break;
                                                        default: echo 'bg-gray-700 text-gray-300';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-400 mb-2"><?php echo nl2br(htmlspecialchars(substr($ticket['description'], 0, 200))); ?>...</p>
                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                <span><i class="fas fa-calendar mr-1"></i><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></span>
                                                <?php if ($ticket['service_name']): ?>
                                                    <span><i class="fas fa-server mr-1"></i><?php echo htmlspecialchars($ticket['service_name']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($ticket['category_name']): ?>
                                                    <span><i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($ticket['category_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <button class="text-blue-400 hover:text-blue-300">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-8 text-center">
                                <i class="fas fa-ticket-alt text-gray-600 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-white mb-2">Keine Support Tickets</h3>
                                <p class="text-gray-400 mb-6">Sie haben noch keine Support Tickets erstellt.</p>
                                <button class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700" onclick="showCreateTicketModal()">
                                    <i class="fas fa-plus mr-2"></i>Erstes Ticket erstellen
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Häufig gestellte Fragen</h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($faq_items)): ?>
                            <div class="space-y-4">
                                <?php foreach ($faq_items as $faq): ?>
                                    <div class="border border-gray-600 rounded-lg">
                                        <button class="w-full text-left p-4 hover:bg-gray-750 focus:outline-none" onclick="toggleFaq(<?php echo $faq['id']; ?>)">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($faq['question']); ?></h4>
                                                <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="faq-icon-<?php echo $faq['id']; ?>"></i>
                                            </div>
                                        </button>
                                        <div class="hidden px-4 pb-4" id="faq-content-<?php echo $faq['id']; ?>">
                                            <p class="text-sm text-gray-400"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-question-circle text-gray-600 text-4xl mb-4"></i>
                                <p class="text-gray-400">Keine FAQ-Einträge verfügbar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Support -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Support-Optionen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <button class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700" onclick="showCreateTicketModal()">
                                <i class="fas fa-plus mr-2"></i>Neues Ticket erstellen
                            </button>
                            <a href="/contact" class="block w-full bg-green-600 text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-green-700">
                                <i class="fas fa-phone mr-2"></i>Telefonischer Support
                            </a>
                            <a href="mailto:support@spectrahost.de" class="block w-full bg-gray-700 text-gray-300 text-center py-3 px-4 rounded-lg font-medium hover:bg-gray-600">
                                <i class="fas fa-envelope mr-2"></i>E-Mail Support
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Support Categories -->
                <?php if (!empty($categories)): ?>
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Support-Kategorien</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($categories as $category): ?>
                                <div class="flex items-center p-3 border border-gray-600 rounded-lg hover:bg-gray-750">
                                    <div class="w-8 h-8 bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas <?php echo $category['icon']; ?> text-blue-400"></i>
                                    </div>
                                    <span class="text-sm font-medium text-white"><?php echo htmlspecialchars($category['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contact Info -->
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-medium text-white">Kontakt</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm text-gray-400">
                            <div class="flex items-center">
                                <i class="fas fa-phone w-5 h-5 mr-3 text-blue-400"></i>
                                <span>+49 (0) 123 456789</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope w-5 h-5 mr-3 text-blue-400"></i>
                                <span>support@spectrahost.de</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock w-5 h-5 mr-3 text-blue-400"></i>
                                <span>24/7 Support</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Ticket Modal -->
<div id="createTicketModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border border-gray-600 w-full max-w-2xl shadow-lg rounded-md bg-gray-800">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-white">Neues Support Ticket erstellen</h3>
                <button onclick="hideCreateTicketModal()" class="text-gray-400 hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="createTicketForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategorie</label>
                        <select class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Kategorie wählen</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Priorität</label>
                        <select class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="low">Niedrig</option>
                            <option value="medium" selected>Mittel</option>
                            <option value="high">Hoch</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Betroffener Service (optional)</label>
                    <select class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Kein spezifischer Service</option>
                        <?php foreach ($user_services as $service): ?>
                            <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Betreff</label>
                    <input type="text" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kurze Beschreibung des Problems" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Beschreibung</label>
                    <textarea rows="6" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Detaillierte Beschreibung des Problems oder Ihrer Anfrage..." required></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="hideCreateTicketModal()" class="flex-1 bg-gray-700 text-gray-300 py-2 px-4 rounded-lg font-medium hover:bg-gray-600">
                        Abbrechen
                    </button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700">
                        Ticket erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateTicketModal() {
    document.getElementById('createTicketModal').classList.remove('hidden');
}

function hideCreateTicketModal() {
    document.getElementById('createTicketModal').classList.add('hidden');
}

function toggleFaq(id) {
    const content = document.getElementById('faq-content-' + id);
    const icon = document.getElementById('faq-icon-' + id);
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderDashboardFooter(); ?>