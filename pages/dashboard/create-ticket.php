<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $priority = $_POST['priority'] ?? 'medium';
        $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
        
        if (empty($subject) || empty($description)) {
            throw new Exception('Betreff und Beschreibung sind erforderlich');
        }
        
        $stmt = $db->prepare("
            INSERT INTO support_tickets (user_id, subject, description, priority, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'open', NOW(), NOW())
        ");
        
        if ($stmt->execute([$user_id, $subject, $description, $priority])) {
            $_SESSION['success'] = 'Ticket wurde erfolgreich erstellt';
            header('Location: /dashboard/support');
            exit;
        } else {
            throw new Exception('Fehler beim Erstellen des Tickets');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Services für Dropdown laden
$services = $db->fetchAll("SELECT id, name FROM services WHERE user_id = ? AND status = 'active'", [$user_id]);
?>

<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neues Ticket erstellen - SpectraHost Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <div class="min-h-screen bg-gray-900">
        <!-- Navigation hier einfügen -->
        
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h1 class="text-xl font-bold text-white">Neues Support-Ticket erstellen</h1>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <?php if (isset($error)): ?>
                        <div class="bg-red-900 border border-red-700 text-red-400 px-4 py-3 rounded">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Betreff *</label>
                            <input type="text" name="subject" required 
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                                   value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Kategorie</label>
                                <select name="category" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                                    <option value="general">Allgemein</option>
                                    <option value="technical">Technisch</option>
                                    <option value="billing">Abrechnung</option>
                                    <option value="abuse">Missbrauch</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Priorität</label>
                                <select name="priority" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                                    <option value="low">Niedrig</option>
                                    <option value="medium" selected>Mittel</option>
                                    <option value="high">Hoch</option>
                                    <option value="urgent">Dringend</option>
                                </select>
                            </div>
                        </div>
                        
                        <?php if (!empty($services)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Betroffener Service (optional)</label>
                            <select name="service_id" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                                <option value="">-- Service auswählen --</option>
                                <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Beschreibung *</label>
                            <textarea name="description" rows="6" required
                                      class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                                      placeholder="Beschreiben Sie Ihr Anliegen detailliert..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="/dashboard/support" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                Abbrechen
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Ticket erstellen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>