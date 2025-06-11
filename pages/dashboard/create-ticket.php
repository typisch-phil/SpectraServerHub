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
        
        // Ticket erstellen
        $stmt = $db->prepare("
            INSERT INTO support_tickets (user_id, subject, description, category, priority, service_id, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())
        ");
        
        if ($stmt->execute([$user_id, $subject, $description, $category, $priority, $service_id])) {
            $ticket_id = $db->lastInsertId();
            
            // Datei-Upload verarbeiten
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowed_types)) {
                    throw new Exception('Dateityp nicht erlaubt. Nur JPG, PNG, GIF, PDF und TXT sind erlaubt.');
                }
                
                if ($file['size'] > $max_size) {
                    throw new Exception('Datei zu groß. Maximum 5MB erlaubt.');
                }
                
                $upload_dir = __DIR__ . '/../../uploads/tickets/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'ticket_' . $ticket_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Attachment in Datenbank speichern
                    $stmt = $db->prepare("
                        INSERT INTO ticket_attachments (ticket_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $ticket_id,
                        $filename,
                        $file['name'],
                        'uploads/tickets/' . $filename,
                        $file['size'],
                        $file['type'],
                        $user_id
                    ]);
                }
            }
            
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
                    
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
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
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Anhang (optional)</label>
                            <div class="relative">
                                <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt" 
                                       class="hidden" onchange="updateFileName(this)">
                                <label for="attachment" class="flex items-center justify-center w-full px-4 py-6 bg-gray-700 border-2 border-dashed border-gray-600 rounded-lg cursor-pointer hover:bg-gray-600 hover:border-gray-500 transition-colors">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                                        <p class="text-gray-300 mb-1">Datei auswählen oder hierher ziehen</p>
                                        <p class="text-gray-400 text-sm">JPG, PNG, GIF, PDF, TXT - Max. 5MB</p>
                                    </div>
                                </label>
                                <div id="file-info" class="mt-2 hidden">
                                    <div class="flex items-center justify-between bg-gray-800 px-3 py-2 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-file text-blue-400 mr-2"></i>
                                            <span id="file-name" class="text-white text-sm"></span>
                                        </div>
                                        <button type="button" onclick="removeFile()" class="text-red-400 hover:text-red-300">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
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

    <script>
        function updateFileName(input) {
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                fileInfo.classList.remove('hidden');
            }
        }
        
        function removeFile() {
            const fileInput = document.getElementById('attachment');
            const fileInfo = document.getElementById('file-info');
            
            fileInput.value = '';
            fileInfo.classList.add('hidden');
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Drag and Drop Funktionalität
        const dropZone = document.querySelector('label[for="attachment"]');
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-blue-500', 'bg-gray-600');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500', 'bg-gray-600');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500', 'bg-gray-600');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('attachment');
                fileInput.files = files;
                updateFileName(fileInput);
            }
        });
    </script>
</body>
</html>