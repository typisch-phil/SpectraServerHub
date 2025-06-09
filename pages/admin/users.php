<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

// Get all users
$stmt = $database->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

$title = 'Benutzerverwaltung - SpectraHost Admin';
$description = 'Verwalten Sie Benutzerkonten und Zugriffsrechte';
renderHeader($title, $description);
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/admin" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Admin Panel</a>
                        <a href="/admin/users" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Benutzer</a>
                        <a href="/admin/tickets" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Tickets</a>
                        <a href="/admin/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Services</a>
                        <a href="/admin/invoices" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Rechnungen</a>
                        <a href="/admin/integrations" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Integrationen</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-user mr-2"></i>Zum Dashboard
                    </a>
                    <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Benutzerverwaltung</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Benutzerkonten und Zugriffsrechte</p>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Alle Benutzer</h2>
            </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Mail</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rolle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guthaben</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erstellt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $user['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">€<?= number_format($user['balance'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editUser(<?= $user['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                    <button onclick="deleteUser(<?= $user['id'] ?>)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editUser(userId) {
            // TODO: Implement user editing
            alert('Benutzer bearbeiten: ' + userId);
        }

        function deleteUser(userId) {
            if (confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?')) {
                // TODO: Implement user deletion
                alert('Benutzer löschen: ' + userId);
            }
        }
    </script>
</body>
</html>