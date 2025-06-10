<?php
require_once __DIR__ . '/../../includes/dashboard-layout.php';

// Benutzer-Authentifizierung prüfen
if (!isLoggedIn()) {
    header("Location: /login");
    exit;
}

$user = getCurrentUser();
if (!$user) {
    header("Location: /login");
    exit;
}

// MySQL-Datenbankverbindung
$host = $_ENV['MYSQL_HOST'] ?? 'localhost';
$username = $_ENV['MYSQL_USER'] ?? 'root';
$password = $_ENV['MYSQL_PASSWORD'] ?? '';
$database = $_ENV['MYSQL_DATABASE'] ?? 'spectrahost';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    $user_tickets = [];
    $ticket_stats = [];
    $user_services = [];
} else {
    // Support-Tickets laden
    try {
        // Benutzer-Tickets abrufen
        $stmt = $mysqli->prepare("
            SELECT t.*, 
                   (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id) as message_count,
                   (SELECT tm.created_at FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.created_at DESC LIMIT 1) as last_activity
            FROM support_tickets t 
            WHERE t.user_id = ? 
            ORDER BY t.created_at DESC
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $tickets_result = $stmt->get_result();
        
        $user_tickets = [];
        while ($row = $tickets_result->fetch_assoc()) {
            $user_tickets[] = $row;
        }
        
        // Ticket-Statistiken
        $stmt = $mysqli->prepare("SELECT status, COUNT(*) as count FROM support_tickets WHERE user_id = ? GROUP BY status");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        
        $ticket_stats = [];
        while ($row = $stats_result->fetch_assoc()) {
            $ticket_stats[$row['status']] = $row['count'];
        }
        
        // Benutzer-Services für Ticket-Erstellung
        $stmt = $mysqli->prepare("
            SELECT id, name
            FROM services 
            WHERE user_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $services_result = $stmt->get_result();
        
        $user_services = [];
        while ($row = $services_result->fetch_assoc()) {
            $user_services[] = $row;
        }
        
    } catch (Exception $e) {
        error_log("Support page error: " . $e->getMessage());
        $user_tickets = [];
        $ticket_stats = [];
        $user_services = [];
    }
}

// FAQ-Einträge
$faq_items = [
    [
        'id' => 1,
        'question' => 'Wie kann ich mein Passwort zurücksetzen?',
        'answer' => 'Sie können Ihr Passwort über den "Passwort vergessen" Link auf der Login-Seite zurücksetzen.'
    ],
    [
        'id' => 2,
        'question' => 'Wie lange dauert die Server-Bereitstellung?',
        'answer' => 'Neue Server werden normalerweise innerhalb von 15 Minuten nach der Bestellung bereitgestellt.'
    ],
    [
        'id' => 3,
        'question' => 'Welche Zahlungsmethoden werden akzeptiert?',
        'answer' => 'Wir akzeptieren Kreditkarten, PayPal, SEPA-Lastschrift und Überweisung.'
    ]
];

renderDashboardHeader('Support - Dashboard');
?>

<div class="min-h-screen bg-gray-900">
    <!-- Dashboard Navigation -->
    <nav class="bg-gray-800 shadow-lg border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">S</span>
                        </div>
                        <span class="text-xl font-bold text-white">SpectraHost Dashboard</span>
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
                    <a href="/" class="text-gray-300 hover:text-blue-400 px-3 py-1 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Zur Website
                    </a>
                    <button onclick="logout()" class="text-gray-300 hover:text-red-400 px-3 py-1 rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
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
                        <?php if (!empty($user_tickets)): ?>
                            <?php foreach ($user_tickets as $ticket): ?>
                                <div class="p-6 hover:bg-gray-750 cursor-pointer" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php 
                                                    switch($ticket['status']) {
                                                        case 'open': echo 'bg-green-900 text-green-400'; break;
                                                        case 'in_progress': echo 'bg-blue-900 text-blue-400'; break;
                                                        case 'waiting_customer': echo 'bg-yellow-900 text-yellow-400'; break;
                                                        case 'resolved': echo 'bg-purple-900 text-purple-400'; break;
                                                        case 'closed': echo 'bg-gray-700 text-gray-300'; break;
                                                        default: echo 'bg-blue-900 text-blue-400';
                                                    }
                                                    ?>">
                                                    <?php 
                                                    $status_labels = [
                                                        'open' => 'Offen',
                                                        'in_progress' => 'In Bearbeitung',
                                                        'waiting_customer' => 'Wartet auf Kunden',
                                                        'resolved' => 'Gelöst',
                                                        'closed' => 'Geschlossen'
                                                    ];
                                                    echo $status_labels[$ticket['status']] ?? ucfirst($ticket['status']);
                                                    ?>
                                                </span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php 
                                                    switch($ticket['priority']) {
                                                        case 'urgent': echo 'bg-red-900 text-red-400'; break;
                                                        case 'high': echo 'bg-orange-900 text-orange-400'; break;
                                                        case 'medium': echo 'bg-yellow-900 text-yellow-400'; break;
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
                                                <span><i class="fas fa-comments mr-1"></i><?php echo $ticket['message_count'] ?? 0; ?> Nachrichten</span>
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
            <form id="createTicketForm" onsubmit="createTicket(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategorie</label>
                        <select name="category" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="general">Allgemein</option>
                            <option value="technical">Technisch</option>
                            <option value="billing">Abrechnung</option>
                            <option value="abuse">Missbrauch</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Priorität</label>
                        <select name="priority" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="low">Niedrig</option>
                            <option value="medium" selected>Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="urgent">Dringend</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Betroffener Service (optional)</label>
                    <select name="service_id" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Kein spezifischer Service</option>
                        <?php foreach ($user_services as $service): ?>
                            <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Betreff</label>
                    <input type="text" name="subject" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kurze Beschreibung des Problems" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Beschreibung</label>
                    <textarea name="description" rows="6" class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Detaillierte Beschreibung des Problems oder Ihrer Anfrage..." required></textarea>
                </div>
                
                <!-- File Upload Section -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Dateien anhängen (optional)</label>
                    <div id="fileDropZone" class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center hover:border-gray-500 transition-colors">
                        <input type="file" id="fileInput" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip" class="hidden" onchange="handleFileSelect(event)">
                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                        <p class="text-gray-400 mb-2">Dateien hier ablegen oder <button type="button" onclick="document.getElementById('fileInput').click()" class="text-blue-400 hover:text-blue-300">durchsuchen</button></p>
                        <p class="text-sm text-gray-500">Maximal 10MB pro Datei. Erlaubte Formate: JPG, PNG, GIF, PDF, TXT, ZIP</p>
                    </div>
                    <div id="fileList" class="mt-3 space-y-2"></div>
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
let uploadedFiles = [];

// Modal Functions
function showCreateTicketModal() {
    document.getElementById('createTicketModal').classList.remove('hidden');
    document.getElementById('createTicketForm').reset();
    uploadedFiles = [];
    updateFileList();
}

function hideCreateTicketModal() {
    document.getElementById('createTicketModal').classList.add('hidden');
}

// Create Ticket Function
async function createTicket(event) {
    event.preventDefault();
    
    const form = document.getElementById('createTicketForm');
    const formData = new FormData(form);
    
    const ticketData = {
        subject: formData.get('subject'),
        description: formData.get('description'),
        category: formData.get('category'),
        priority: formData.get('priority'),
        service_id: formData.get('service_id') || null
    };
    
    try {
        const response = await fetch('/api/tickets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(ticketData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Upload files if any
            if (uploadedFiles.length > 0) {
                await uploadTicketFiles(data.ticket_id);
            }
            
            alert('Ticket erfolgreich erstellt!');
            hideCreateTicketModal();
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error creating ticket:', error);
        alert('Fehler beim Erstellen des Tickets');
    }
}

// File Upload Functions
function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip'];
    
    for (const file of files) {
        if (file.size > maxSize) {
            alert(`Datei "${file.name}" ist zu groß (max. 10MB)`);
            continue;
        }
        
        if (!allowedTypes.includes(file.type)) {
            alert(`Dateityp von "${file.name}" ist nicht erlaubt`);
            continue;
        }
        
        uploadedFiles.push(file);
    }
    
    updateFileList();
}

function updateFileList() {
    const container = document.getElementById('fileList');
    container.innerHTML = uploadedFiles.map((file, index) => `
        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
            <div class="flex items-center space-x-3">
                <i class="fas fa-file text-gray-400"></i>
                <span class="text-white">${file.name}</span>
                <span class="text-sm text-gray-400">(${(file.size / 1024).toFixed(1)} KB)</span>
            </div>
            <button type="button" onclick="removeFile(${index})" class="text-red-400 hover:text-red-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function removeFile(index) {
    uploadedFiles.splice(index, 1);
    updateFileList();
}

async function uploadTicketFiles(ticketId) {
    for (const file of uploadedFiles) {
        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('file', file);
        
        try {
            await fetch('/api/ticket-upload.php', {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            console.error('Error uploading file:', error);
        }
    }
}

// View Ticket Function
function viewTicket(ticketId) {
    window.open(`/dashboard/ticket-view?id=${ticketId}`, '_blank');
}

// FAQ Functions
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

// Logout function
function logout() {
    if (confirm('Möchten Sie sich wirklich abmelden?')) {
        window.location.href = '/api/logout.php';
    }
}

// Initialize drag and drop
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('fileDropZone');
    
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            dropZone.classList.add('border-blue-500', 'bg-gray-700');
        }
        
        function unhighlight(e) {
            dropZone.classList.remove('border-blue-500', 'bg-gray-700');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            handleFileSelect({ target: { files: files } });
        }
    }
});
</script>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php renderDashboardFooter(); ?>