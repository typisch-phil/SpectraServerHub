<?php
require_once '../../includes/session.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

$db = Database::getInstance();

try {
    // Get user statistics
    $userStmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $userStmt->execute();
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get service statistics
    $serviceStmt = $db->prepare("SELECT COUNT(*) as total FROM services WHERE status = 'active'");
    $serviceStmt->execute();
    $activeServices = $serviceStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get ticket statistics
    $ticketStmt = $db->prepare("SELECT COUNT(*) as total FROM tickets WHERE status = 'open'");
    $ticketStmt->execute();
    $openTickets = $ticketStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get revenue statistics
    $revenueStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid' AND created_at >= date('now', '-30 days')");
    $revenueStmt->execute();
    $monthlyRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get new users this month
    $newUsersStmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= date('now', '-30 days')");
    $newUsersStmt->execute();
    $newUsersThisMonth = $newUsersStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get recent activity counts
    $activityStmt = $db->prepare("SELECT type, COUNT(*) as count FROM activity_logs WHERE created_at >= date('now', '-7 days') GROUP BY type");
    $activityStmt->execute();
    $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'active_services' => (int)$activeServices,
            'open_tickets' => (int)$openTickets,
            'monthly_revenue' => (float)$monthlyRevenue,
            'new_users_this_month' => (int)$newUsersThisMonth,
            'recent_activity' => $recentActivity
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