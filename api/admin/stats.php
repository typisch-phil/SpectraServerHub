<?php
require_once __DIR__ . '/../../includes/session.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

$db = Database::getInstance();

try {
    // Get user statistics
    $userStmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $userStmt->execute();
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get active user services count
    $serviceStmt = $db->prepare("SELECT COUNT(*) as total FROM user_services WHERE status = 'active'");
    $serviceStmt->execute();
    $activeServices = $serviceStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get server statistics
    $serverStmt = $db->prepare("SELECT COUNT(*) as total FROM servers WHERE status = 'running'");
    $serverStmt->execute();
    $runningServers = $serverStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get revenue statistics from payments
    $revenueStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND created_at >= date('now', '-30 days')");
    $revenueStmt->execute();
    $monthlyRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get new users this month
    $newUsersStmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= date('now', '-30 days')");
    $newUsersStmt->execute();
    $newUsersThisMonth = $newUsersStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total services available
    $totalServicesStmt = $db->prepare("SELECT COUNT(*) as total FROM services WHERE active = 1");
    $totalServicesStmt->execute();
    $totalServices = $totalServicesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $response = [
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'active_services' => (int)$activeServices,
            'running_servers' => (int)$runningServers,
            'monthly_revenue' => (float)$monthlyRevenue,
            'new_users_this_month' => (int)$newUsersThisMonth,
            'total_services' => (int)$totalServices
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Statistiken: ' . $e->getMessage()
    ]);
}
?>