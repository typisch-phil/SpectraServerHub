<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/mollie.php';

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$topup_id = $_GET['topup_id'] ?? null;

// Benutzer-Daten abrufen
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
if (!$user) {
    header("Location: /login");
    exit;
}

// Topup-Details abrufen
$topup = null;
if ($topup_id) {
    $topup = $db->fetchOne("
        SELECT * FROM balance_topups 
        WHERE id = ? AND user_id = ?
    ", [$topup_id, $user_id]);
}

$success = $topup && $topup['status'] === 'completed';
$pending = $topup && $topup['status'] === 'pending';
?>

<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guthaben-Aufladung - SpectraHost Dashboard</title>
    <meta name="description" content="SpectraHost Guthaben-Aufladung Ergebnis">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            750: '#374151',
                            850: '#1f2937'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if ($pending): ?>
    <!-- Auto-refresh für pending payments -->
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
</head>
<body class="bg-gray-900 text-white">

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
                        <a href="/dashboard/billing" class="text-blue-400 border-b-2 border-blue-400 px-1 pb-4 text-sm font-medium">Billing</a>
                        <a href="/dashboard/support" class="text-gray-300 hover:text-white px-1 pb-4 text-sm font-medium">Support</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-300">
                        Guthaben: <span class="font-bold text-green-400">€<?php echo number_format($user['balance'], 2); ?></span>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-300 hover:text-white focus:outline-none">
                            <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium"><?php echo strtoupper(substr($user['email'] ?? 'U', 0, 1)); ?></span>
                            </div>
                            <span class="text-sm"><?php echo htmlspecialchars($user['email'] ?? 'Benutzer'); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <a href="/dashboard/profile" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profil bearbeiten</a>
                            <a href="/dashboard/settings" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Einstellungen</a>
                            <div class="border-t border-gray-700"></div>
                            <a href="/logout" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Abmelden</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
            <?php if ($success): ?>
                <!-- Erfolgreich -->
                <div class="mb-8">
                    <div class="w-24 h-24 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-white text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-4">Guthaben erfolgreich aufgeladen!</h1>
                    <p class="text-gray-300 text-lg mb-6">
                        Ihr Guthaben wurde um <span class="font-bold text-green-400">€<?php echo number_format($topup['amount'], 2); ?></span> erhöht.
                    </p>
                </div>
                
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8 max-w-md mx-auto">
                    <h3 class="text-lg font-medium text-white mb-4">Transaktionsdetails</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Betrag:</span>
                            <span class="text-white font-medium">€<?php echo number_format($topup['amount'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Datum:</span>
                            <span class="text-white"><?php echo date('d.m.Y H:i', strtotime($topup['completed_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Transaktion:</span>
                            <span class="text-white">#<?php echo $topup['id']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Neues Guthaben:</span>
                            <span class="text-green-400 font-bold">€<?php echo number_format($user['balance'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pending): ?>
                <!-- Ausstehend -->
                <div class="mb-8">
                    <div class="w-24 h-24 bg-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-clock text-white text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-4">Zahlung wird verarbeitet...</h1>
                    <p class="text-gray-300 text-lg mb-6">
                        Ihre Zahlung von <span class="font-bold text-yellow-400">€<?php echo number_format($topup['amount'], 2); ?></span> wird noch verarbeitet.
                    </p>
                    <div class="flex items-center justify-center space-x-2 text-yellow-400">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Bitte warten Sie...</span>
                    </div>
                    <p class="text-sm text-gray-400 mt-4">Diese Seite wird automatisch aktualisiert.</p>
                </div>
                
            <?php else: ?>
                <!-- Fehler oder nicht gefunden -->
                <div class="mb-8">
                    <div class="w-24 h-24 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-times text-white text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-4">Zahlung nicht gefunden</h1>
                    <p class="text-gray-300 text-lg mb-6">
                        Die angeforderte Transaktion konnte nicht gefunden werden oder ist fehlgeschlagen.
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Aktionsbuttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard/billing" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Zurück zur Billing
                </a>
                <?php if ($success): ?>
                <a href="/dashboard" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>Zum Dashboard
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>