<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Admin-Authentifizierung überprüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Überprüfung ob Benutzer Admin-Rechte hat
$db = Database::getInstance();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

// Services laden
$services = [];
try {
    $stmt = $db->query("SELECT * FROM service_types ORDER BY category, name");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading services: " . $e->getMessage());
}

$pageTitle = "Service-Verwaltung - SpectraHost Admin";
$pageDescription = "Verwaltung von Hosting-Services und Paketen";

renderHeader($pageTitle, $pageDescription);
?>

<div class="min-h-screen bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 to-blue-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Service-Verwaltung</h1>
                    <p class="text-gray-200">Verwaltung von Hosting-Services und Paketen</p>
                </div>
                <div class="hidden md:block">
                    <div class="text-right">
                        <div class="text-gray-300 text-sm">Gesamt Services</div>
                        <div class="text-white font-semibold text-2xl"><?php echo count($services); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-8">
                <a href="/admin/dashboard" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Dashboard</a>
                <a href="/admin/users" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Benutzer</a>
                <a href="/admin/services" class="text-white bg-purple-600 px-4 py-2 rounded-lg font-medium">Services</a>
                <a href="/admin/tickets" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">Tickets</a>
                <a href="/admin/ip-management" class="text-gray-300 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-800">IP-Management</a>
            </nav>
        </div>

        <!-- Services Übersicht -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl border-2 border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-server mr-3"></i>Services Übersicht
                </h2>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Neuer Service
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Name</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Kategorie</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Preis</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Status</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Erstellt</th>
                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400">
                                Keine Services vorhanden
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($services as $service): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-700/30">
                            <td class="py-4 px-4">
                                <div class="text-white font-medium"><?php echo htmlspecialchars($service['name']); ?></div>
                                <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($service['description'] ?? ''); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    switch($service['category']) {
                                        case 'webspace': echo 'bg-blue-900 text-blue-200'; break;
                                        case 'vserver': echo 'bg-green-900 text-green-200'; break;
                                        case 'gameserver': echo 'bg-purple-900 text-purple-200'; break;
                                        case 'domain': echo 'bg-yellow-900 text-yellow-200'; break;
                                        default: echo 'bg-gray-900 text-gray-200';
                                    }
                                    ?>">
                                    <?php echo ucfirst($service['category']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-white font-medium">€<?php echo number_format($service['monthly_price'] ?? 0, 2); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($service['is_active']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-200">
                                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                        Aktiv
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900 text-red-200">
                                        <div class="w-2 h-2 bg-red-400 rounded-full mr-2"></div>
                                        Inaktiv
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-300"><?php echo date('d.m.Y', strtotime($service['created_at'])); ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button onclick="editService(<?php echo $service['id']; ?>)" class="text-blue-400 hover:text-blue-300 p-1" title="Bearbeiten">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="editSpecs(<?php echo $service['id']; ?>)" class="text-yellow-400 hover:text-yellow-300 p-1" title="Spezifikationen">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <button onclick="toggleService(<?php echo $service['id']; ?>)" class="text-<?php echo $service['is_active'] ? 'red' : 'green'; ?>-400 hover:text-<?php echo $service['is_active'] ? 'red' : 'green'; ?>-300 p-1" title="<?php echo $service['is_active'] ? 'Deaktivieren' : 'Aktivieren'; ?>">
                                        <i class="fas fa-<?php echo $service['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Service Kategorien Statistiken -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <?php
            $categories = ['webspace', 'vserver', 'gameserver', 'domain'];
            $categoryColors = [
                'webspace' => 'blue',
                'vserver' => 'green', 
                'gameserver' => 'purple',
                'domain' => 'yellow'
            ];
            $categoryIcons = [
                'webspace' => 'fa-globe',
                'vserver' => 'fa-server',
                'gameserver' => 'fa-gamepad',
                'domain' => 'fa-link'
            ];
            
            foreach ($categories as $category):
                $count = count(array_filter($services, fn($s) => $s['category'] === $category));
                $color = $categoryColors[$category];
                $icon = $categoryIcons[$category];
            ?>
            <div class="bg-gradient-to-br from-<?php echo $color; ?>-800 to-<?php echo $color; ?>-900 rounded-2xl p-6 border border-<?php echo $color; ?>-700">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-<?php echo $color; ?>-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas <?php echo $icon; ?> text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?php echo $count; ?></div>
                        <div class="text-<?php echo $color; ?>-200 text-sm"><?php echo ucfirst($category); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Service Bearbeiten Modal -->
<div id="editServiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-white">Service bearbeiten</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editServiceForm">
            <input type="hidden" id="editServiceId" name="service_id">
            
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-medium mb-2">Name</label>
                <input type="text" id="editServiceName" name="name" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-medium mb-2">Beschreibung</label>
                <textarea id="editServiceDescription" name="description" rows="3" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-medium mb-2">Kategorie</label>
                <select id="editServiceCategory" name="category" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
                    <option value="webspace">Webspace</option>
                    <option value="vserver">vServer</option>
                    <option value="gameserver">GameServer</option>
                    <option value="domain">Domain</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-medium mb-2">Monatlicher Preis (€)</label>
                <input type="number" id="editServicePrice" name="monthly_price" step="0.01" min="0" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" id="editServiceActive" name="is_active" class="mr-2">
                    <span class="text-gray-300">Service aktiv</span>
                </label>
            </div>
            
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-colors">
                    Speichern
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors">
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Spezifikationen Bearbeiten Modal -->
<div id="editSpecsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-2xl mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-white">Service Spezifikationen</h3>
            <button onclick="closeSpecsModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editSpecsForm">
            <input type="hidden" id="specsServiceId" name="service_id">
            
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-medium mb-2">Spezifikationen (JSON Format)</label>
                <textarea id="editServiceSpecs" name="specifications" rows="10" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-blue-500 font-mono text-sm" placeholder='{"cpu": "2 Cores", "ram": "4 GB", "storage": "50 GB SSD"}'></textarea>
                <div class="text-gray-400 text-xs mt-1">Geben Sie die Spezifikationen im JSON-Format ein</div>
            </div>
            
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg transition-colors">
                    Speichern
                </button>
                <button type="button" onclick="closeSpecsModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors">
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Service bearbeiten
async function editService(serviceId) {
    try {
        const response = await fetch(`/api/admin/service-details.php?id=${serviceId}`);
        const service = await response.json();
        
        if (!response.ok) {
            throw new Error(service.error || 'Fehler beim Laden des Services');
        }
        
        document.getElementById('editServiceId').value = service.id;
        document.getElementById('editServiceName').value = service.name || '';
        document.getElementById('editServiceDescription').value = service.description || '';
        document.getElementById('editServiceCategory').value = service.category || '';
        document.getElementById('editServicePrice').value = service.monthly_price || '';
        document.getElementById('editServiceActive').checked = service.is_active == 1;
        
        document.getElementById('editServiceModal').classList.remove('hidden');
    } catch (error) {
        alert('Fehler beim Laden des Services: ' + error.message);
    }
}

// Spezifikationen bearbeiten
async function editSpecs(serviceId) {
    try {
        const response = await fetch(`/api/admin/service-details.php?id=${serviceId}`);
        const service = await response.json();
        
        if (!response.ok) {
            throw new Error(service.error || 'Fehler beim Laden des Services');
        }
        
        document.getElementById('specsServiceId').value = service.id;
        document.getElementById('editServiceSpecs').value = service.specifications || '{}';
        
        document.getElementById('editSpecsModal').classList.remove('hidden');
    } catch (error) {
        alert('Fehler beim Laden der Spezifikationen: ' + error.message);
    }
}

// Service aktivieren/deaktivieren
async function toggleService(serviceId) {
    try {
        const response = await fetch('/api/admin/toggle-service.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ service_id: serviceId })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Fehler beim Ändern des Service-Status');
        }
        
        location.reload();
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
}

// Modals schließen
function closeEditModal() {
    document.getElementById('editServiceModal').classList.add('hidden');
}

function closeSpecsModal() {
    document.getElementById('editSpecsModal').classList.add('hidden');
}

// Form Submit Handler für Service bearbeiten
document.getElementById('editServiceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        service_id: formData.get('service_id'),
        name: formData.get('name'),
        description: formData.get('description'),
        category: formData.get('category'),
        monthly_price: parseFloat(formData.get('monthly_price')),
        is_active: formData.get('is_active') ? 1 : 0
    };
    
    try {
        const response = await fetch('/api/admin/update-service.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Fehler beim Aktualisieren des Services');
        }
        
        closeEditModal();
        location.reload();
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
});

// Form Submit Handler für Spezifikationen
document.getElementById('editSpecsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    let specifications = formData.get('specifications');
    
    // JSON validieren
    try {
        JSON.parse(specifications);
    } catch (error) {
        alert('Ungültiges JSON-Format in den Spezifikationen');
        return;
    }
    
    const data = {
        service_id: formData.get('service_id'),
        specifications: specifications
    };
    
    try {
        const response = await fetch('/api/admin/update-service-specs.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Fehler beim Aktualisieren der Spezifikationen');
        }
        
        closeSpecsModal();
        alert('Spezifikationen erfolgreich aktualisiert');
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
});
</script>

<?php
renderFooter();
?>