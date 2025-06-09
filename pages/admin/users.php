<?php
require_once '../includes/session.php';
requireLogin();
requireAdmin();

$db = Database::getInstance();

// Get all users
$stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung - SpectraHost Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="/admin" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-2"></i>Admin Dashboard
                        </a>
                    </div>
                    <h1 class="text-xl font-bold">Benutzerverwaltung</h1>
                    <div>
                        <a href="/api/logout" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Alle Benutzer</h2>
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