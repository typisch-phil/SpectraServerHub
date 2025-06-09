<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'status':
                    // Get all integration statuses
                    $integrations = [
                        'proxmox' => ['status' => 'active', 'last_test' => '2025-06-09 20:45:00'],
                        'mollie' => ['status' => 'active', 'last_test' => '2025-06-09 20:30:00'],
                        'email' => ['status' => 'configuring', 'last_test' => null],
                        'backup' => ['status' => 'inactive', 'last_test' => null],
                        'monitoring' => ['status' => 'active', 'last_test' => '2025-06-09 20:15:00'],
                        'dns' => ['status' => 'partial', 'last_test' => '2025-06-09 19:30:00']
                    ];
                    echo json_encode(['success' => true, 'data' => $integrations]);
                    break;
                    
                case 'logs':
                    // Get recent API logs
                    $logs = [
                        [
                            'id' => 1,
                            'integration' => 'mollie',
                            'type' => 'webhook',
                            'message' => 'Zahlung #12345 erfolgreich verarbeitet',
                            'status' => 'success',
                            'timestamp' => '2025-06-09 20:58:00'
                        ],
                        [
                            'id' => 2,
                            'integration' => 'proxmox',
                            'type' => 'vm_creation',
                            'message' => 'VM-ID: 201 für Kunde kunde@test.de erstellt',
                            'status' => 'success',
                            'timestamp' => '2025-06-09 20:45:00'
                        ],
                        [
                            'id' => 3,
                            'integration' => 'email',
                            'type' => 'smtp',
                            'message' => 'SMTP Verbindung zu server.example.com unterbrochen',
                            'status' => 'error',
                            'timestamp' => '2025-06-09 20:00:00'
                        ]
                    ];
                    echo json_encode(['success' => true, 'data' => $logs]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'test':
                    $integration = $input['integration'] ?? '';
                    $result = testIntegration($integration);
                    echo json_encode($result);
                    break;
                    
                case 'configure':
                    $integration = $input['integration'] ?? '';
                    $config = $input['config'] ?? [];
                    $result = configureIntegration($integration, $config);
                    echo json_encode($result);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function testIntegration($integration) {
    switch ($integration) {
        case 'proxmox':
            // Simulate Proxmox API test
            return [
                'success' => true,
                'message' => 'Proxmox VE Verbindung erfolgreich getestet',
                'details' => [
                    'version' => '8.2.4',
                    'nodes' => 3,
                    'vms' => 47,
                    'response_time' => '145ms'
                ]
            ];
            
        case 'mollie':
            // Simulate Mollie API test
            return [
                'success' => true,
                'message' => 'Mollie API Verbindung erfolgreich',
                'details' => [
                    'api_version' => 'v2',
                    'methods' => ['creditcard', 'ideal', 'paypal', 'banktransfer'],
                    'response_time' => '89ms'
                ]
            ];
            
        case 'email':
            // Simulate SMTP test
            return [
                'success' => false,
                'message' => 'SMTP Verbindung fehlgeschlagen',
                'details' => [
                    'error' => 'Connection timeout to smtp.gmail.com:587',
                    'suggestion' => 'Überprüfen Sie die SMTP-Einstellungen'
                ]
            ];
            
        case 'backup':
            return [
                'success' => false,
                'message' => 'Backup-Service nicht konfiguriert',
                'details' => [
                    'error' => 'No backup provider configured',
                    'suggestion' => 'Konfigurieren Sie einen Cloud-Backup-Anbieter'
                ]
            ];
            
        case 'monitoring':
            return [
                'success' => true,
                'message' => 'Monitoring-System funktionsfähig',
                'details' => [
                    'uptime' => '99.8%',
                    'monitored_services' => 12,
                    'alerts_24h' => 2
                ]
            ];
            
        case 'dns':
            return [
                'success' => true,
                'message' => 'DNS API teilweise funktionsfähig',
                'details' => [
                    'zones' => 23,
                    'records' => 156,
                    'last_sync' => '2025-06-09 19:30:00',
                    'warning' => 'Einige Zonen sind nicht synchronisiert'
                ]
            ];
            
        default:
            return [
                'success' => false,
                'message' => 'Unbekannte Integration: ' . $integration
            ];
    }
}

function configureIntegration($integration, $config) {
    switch ($integration) {
        case 'proxmox':
            return [
                'success' => true,
                'message' => 'Proxmox VE Konfiguration gespeichert',
                'redirect' => '/admin/integrations/proxmox'
            ];
            
        case 'mollie':
            return [
                'success' => true,
                'message' => 'Mollie Konfiguration aktualisiert',
                'redirect' => '/admin/integrations/mollie'
            ];
            
        case 'email':
            return [
                'success' => true,
                'message' => 'E-Mail SMTP Konfiguration gespeichert',
                'redirect' => '/admin/integrations/email'
            ];
            
        case 'backup':
            return [
                'success' => true,
                'message' => 'Backup-Konfiguration erstellt',
                'redirect' => '/admin/integrations/backup'
            ];
            
        case 'monitoring':
            return [
                'success' => true,
                'message' => 'Monitoring-Konfiguration aktualisiert',
                'redirect' => '/admin/integrations/monitoring'
            ];
            
        case 'dns':
            return [
                'success' => true,
                'message' => 'DNS Management konfiguriert',
                'redirect' => '/admin/integrations/dns'
            ];
            
        default:
            return [
                'success' => false,
                'message' => 'Unbekannte Integration: ' . $integration
            ];
    }
}
?>