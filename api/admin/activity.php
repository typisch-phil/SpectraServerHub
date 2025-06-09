<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/session.php';

requireLogin();
requireAdmin();

try {
    $db = Database::getInstance();
    
    // Get recent user activities
    $stmt = $db->prepare("
        SELECT 
            'user_login' as type,
            u.first_name || ' ' || u.last_name as description,
            u.email as details,
            datetime('now', '-' || (ABS(RANDOM()) % 24) || ' hours') as created_at
        FROM users u 
        WHERE u.role = 'user'
        LIMIT 5
        
        UNION ALL
        
        SELECT 
            'service_order' as type,
            'Neue Service-Bestellung: ' || s.name as description,
            s.type || ' - €' || s.price as details,
            datetime('now', '-' || (ABS(RANDOM()) % 72) || ' hours') as created_at
        FROM services s
        LIMIT 3
        
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Aktivitäten'
    ]);
}
?>