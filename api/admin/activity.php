<?php
header('Content-Type: application/json');
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';

requireLogin();
requireAdmin();

try {
    $db = Database::getInstance();
    
    // Get recent user activities - simplified for demo
    $activities = [
        [
            'type' => 'user_registration',
            'description' => 'Neuer Benutzer registriert',
            'details' => 'max.mustermann@example.com',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'type' => 'service_order',
            'description' => 'Service-Bestellung: Basic vServer',
            'details' => 'vServer - €19.99',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'type' => 'payment_received',
            'description' => 'Zahlung erhalten',
            'details' => '€29.99 via iDEAL',
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ],
        [
            'type' => 'ticket_created',
            'description' => 'Neues Support-Ticket',
            'details' => 'Server Problem - Priorität: Hoch',
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 hours'))
        ],
        [
            'type' => 'user_login',
            'description' => 'Admin-Anmeldung',
            'details' => 'admin@spectrahost.de',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
        ]
    ];
    
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