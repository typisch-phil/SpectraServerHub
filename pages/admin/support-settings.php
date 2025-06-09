<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is admin
session_start();
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /login');
    exit;
}

$database = Database::getInstance();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'support_online' => isset($_POST['support_online']) ? 1 : 0,
        'monday_start' => $_POST['monday_start'] ?? '09:00',
        'monday_end' => $_POST['monday_end'] ?? '18:00',
        'tuesday_start' => $_POST['tuesday_start'] ?? '09:00',
        'tuesday_end' => $_POST['tuesday_end'] ?? '18:00',
        'wednesday_start' => $_POST['wednesday_start'] ?? '09:00',
        'wednesday_end' => $_POST['wednesday_end'] ?? '18:00',
        'thursday_start' => $_POST['thursday_start'] ?? '09:00',
        'thursday_end' => $_POST['thursday_end'] ?? '18:00',
        'friday_start' => $_POST['friday_start'] ?? '09:00',
        'friday_end' => $_POST['friday_end'] ?? '18:00',
        'saturday_start' => $_POST['saturday_start'] ?? '10:00',
        'saturday_end' => $_POST['saturday_end'] ?? '16:00',
        'sunday_start' => $_POST['sunday_start'] ?? '',
        'sunday_end' => $_POST['sunday_end'] ?? '',
        'auto_response_enabled' => isset($_POST['auto_response_enabled']) ? 1 : 0,
        'auto_response_message' => $_POST['auto_response_message'] ?? '',
        'notification_email' => $_POST['notification_email'] ?? ''
    ];
    
    // Create or update settings table
    try {
        $db = $database->getConnection();
        
        // Check if settings table exists
        $result = $db->query("SHOW TABLES LIKE 'support_settings'");
        if ($result->rowCount() == 0) {
            $db->exec("
                CREATE TABLE support_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        // Update each setting
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO support_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        $success_message = "Support-Einstellungen erfolgreich aktualisiert!";
    } catch (Exception $e) {
        $error_message = "Fehler beim Speichern der Einstellungen: " . $e->getMessage();
    }
}

// Load current settings
$current_settings = [];
try {
    $db = $database->getConnection();
    $result = $db->query("SHOW TABLES LIKE 'support_settings'");
    if ($result->rowCount() > 0) {
        $stmt = $db->query("SELECT setting_key, setting_value FROM support_settings");
        while ($row = $stmt->fetch()) {
            $current_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // Settings table doesn't exist yet
}

// Default values
$defaults = [
    'support_online' => 1,
    'monday_start' => '09:00',
    'monday_end' => '18:00',
    'tuesday_start' => '09:00',
    'tuesday_end' => '18:00',
    'wednesday_start' => '09:00',
    'wednesday_end' => '18:00',
    'thursday_start' => '09:00',
    'thursday_end' => '18:00',
    'friday_start' => '09:00',
    'friday_end' => '18:00',
    'saturday_start' => '10:00',
    'saturday_end' => '16:00',
    'sunday_start' => '',
    'sunday_end' => '',
    'auto_response_enabled' => 0,
    'auto_response_message' => 'Vielen Dank für Ihre Anfrage. Unser Support-Team wird sich so schnell wie möglich bei Ihnen melden.',
    'notification_email' => 'support@spectrahost.de'
];

// Merge with current settings
$settings = array_merge($defaults, $current_settings);

$title = 'Support-Einstellungen - SpectraHost Admin';
$description = 'Konfigurieren Sie Support-Zeiten und Benachrichtigungen';
renderHeader($title, $description);
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">SpectraHost Admin</h1>
                    </div>
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/admin" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="/admin/users" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">Benutzer</a>
                        <a href="/admin/tickets" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">Tickets</a>
                        <a href="/admin/support-settings" class="bg-gray-900 text-white dark:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">Support-Einstellungen</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="/api/logout.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Support-Einstellungen</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Konfigurieren Sie Support-Zeiten, automatische Antworten und Benachrichtigungen</p>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?= htmlspecialchars($success_message) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <!-- Support Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Support-Status</h3>
                
                <div class="flex items-center">
                    <input type="checkbox" id="support_online" name="support_online" value="1" <?= $settings['support_online'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="support_online" class="ml-2 block text-sm text-gray-900 dark:text-white">
                        Support ist online
                    </label>
                    <div class="ml-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" id="status-indicator">
                            <span class="w-2 h-2 mr-1.5 rounded-full" id="status-dot"></span>
                            <span id="status-text"></span>
                        </span>
                    </div>
                </div>
                
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Wenn aktiviert, wird Kunden angezeigt, dass der Support online ist.
                </p>
            </div>

            <!-- Support Hours -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Support-Zeiten</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php
                    $days = [
                        'monday' => 'Montag',
                        'tuesday' => 'Dienstag', 
                        'wednesday' => 'Mittwoch',
                        'thursday' => 'Donnerstag',
                        'friday' => 'Freitag',
                        'saturday' => 'Samstag',
                        'sunday' => 'Sonntag'
                    ];
                    
                    foreach ($days as $day => $dayName):
                    ?>
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3"><?= $dayName ?></h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Von</label>
                                <input type="time" name="<?= $day ?>_start" value="<?= htmlspecialchars($settings[$day . '_start']) ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bis</label>
                                <input type="time" name="<?= $day ?>_end" value="<?= htmlspecialchars($settings[$day . '_end']) ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Leer lassen für geschlossen
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Auto Response -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Automatische Antworten</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="auto_response_enabled" name="auto_response_enabled" value="1" <?= $settings['auto_response_enabled'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="auto_response_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            Automatische Antworten aktivieren
                        </label>
                    </div>
                    
                    <div>
                        <label for="auto_response_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Automatische Antwort-Nachricht
                        </label>
                        <textarea id="auto_response_message" name="auto_response_message" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Nachricht für automatische Antworten..."><?= htmlspecialchars($settings['auto_response_message']) ?></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Diese Nachricht wird automatisch als erste Antwort auf neue Tickets gesendet.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Benachrichtigungen</h3>
                
                <div>
                    <label for="notification_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Benachrichtigungs-E-Mail
                    </label>
                    <input type="email" id="notification_email" name="notification_email" value="<?= htmlspecialchars($settings['notification_email']) ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="support@spectrahost.de">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        E-Mail-Adresse für Ticket-Benachrichtigungen
                    </p>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Einstellungen speichern
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Update status indicator
    function updateStatusIndicator() {
        const isOnline = document.getElementById('support_online').checked;
        const indicator = document.getElementById('status-indicator');
        const dot = document.getElementById('status-dot');
        const text = document.getElementById('status-text');
        
        if (isOnline) {
            indicator.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
            dot.className = 'w-2 h-2 mr-1.5 rounded-full bg-green-400';
            text.textContent = 'Online';
        } else {
            indicator.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800';
            dot.className = 'w-2 h-2 mr-1.5 rounded-full bg-red-400';
            text.textContent = 'Offline';
        }
    }
    
    // Initialize status indicator
    updateStatusIndicator();
    
    // Update status when checkbox changes
    document.getElementById('support_online').addEventListener('change', updateStatusIndicator);
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const email = document.getElementById('notification_email').value;
        if (email && !email.includes('@')) {
            e.preventDefault();
            alert('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            return false;
        }
    });
</script>

<?php renderFooter(); ?>