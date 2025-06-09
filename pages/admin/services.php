<?php
require_once '../includes/session.php';
requireLogin();
requireAdmin();

$db = Database::getInstance();

// Get all services
$stmt = $db->prepare("SELECT * FROM services ORDER BY type, name");
$stmt->execute();
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service-Verwaltung - SpectraHost Admin</title>
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
                    <h1 class="text-xl font-bold">Service-Verwaltung</h1>
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
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Alle Services</h2>
                    <button onclick="addService()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Neuer Service
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spezifikationen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $service['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($service['name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($service['description'], 0, 50)) ?>...</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?= ucfirst($service['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">€<?= number_format($service['price'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($service['cpu_cores'] > 0): ?>
                                        <?= $service['cpu_cores'] ?> vCPU, <?= $service['memory_gb'] ?>GB RAM, <?= $service['storage_gb'] ?>GB SSD
                                    <?php else: ?>
                                        Domain-Service
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $service['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $service['active'] ? 'Aktiv' : 'Inaktiv' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editService(<?= $service['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="toggleService(<?= $service['id'] ?>, <?= $service['active'] ? 'false' : 'true' ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                        <i class="fas fa-toggle-<?= $service['active'] ? 'on' : 'off' ?>"></i>
                                    </button>
                                    <button onclick="deleteService(<?= $service['id'] ?>)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
        function addService() {
            alert('Service hinzufügen - Feature wird implementiert');
        }

        function editService(serviceId) {
            alert('Service bearbeiten: ' + serviceId);
        }

        function toggleService(serviceId, newStatus) {
            alert('Service Status ändern: ' + serviceId + ' -> ' + newStatus);
        }

        function deleteService(serviceId) {
            if (confirm('Sind Sie sicher, dass Sie diesen Service löschen möchten?')) {
                alert('Service löschen: ' + serviceId);
            }
        }
    </script>
</body>
</html>