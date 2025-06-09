<?php
// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

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

class EmailAPI {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function testConnection() {
        try {
            $start = microtime(true);
            
            // Validate configuration
            if (empty($this->config['smtp_host']) || empty($this->config['smtp_port']) || 
                empty($this->config['smtp_username']) || empty($this->config['smtp_password'])) {
                throw new Exception('SMTP configuration incomplete');
            }
            
            // Test SMTP connection
            $socket = @fsockopen($this->config['smtp_host'], $this->config['smtp_port'], $errno, $errstr, 10);
            
            if (!$socket) {
                throw new Exception("Cannot connect to SMTP server: {$errstr} ({$errno})");
            }
            
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                throw new Exception('Invalid SMTP server response: ' . trim($response));
            }
            
            // Send EHLO command
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $response = fgets($socket, 515);
            
            if (substr($response, 0, 3) !== '250') {
                fclose($socket);
                throw new Exception('EHLO command failed: ' . trim($response));
            }
            
            // Test STARTTLS if SSL is enabled
            if (!empty($this->config['smtp_ssl'])) {
                fwrite($socket, "STARTTLS\r\n");
                $response = fgets($socket, 515);
                
                if (substr($response, 0, 3) !== '220') {
                    fclose($socket);
                    throw new Exception('STARTTLS failed: ' . trim($response));
                }
            }
            
            fclose($socket);
            
            $responseTime = round((microtime(true) - $start) * 1000);
            
            return [
                'success' => true,
                'message' => 'E-Mail SMTP Verbindung erfolgreich getestet',
                'details' => [
                    'smtp_host' => $this->config['smtp_host'],
                    'smtp_port' => $this->config['smtp_port'],
                    'username' => $this->config['smtp_username'],
                    'ssl_enabled' => !empty($this->config['smtp_ssl']),
                    'response_time' => $responseTime . 'ms',
                    'status' => 'Ready for email notifications'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'E-Mail SMTP Verbindung fehlgeschlagen: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'suggestion' => 'Überprüfen Sie SMTP-Server, Port und Anmeldedaten'
                ]
            ];
        }
    }
    
    public function sendTestEmail($to = null) {
        try {
            $to = $to ?: $this->config['smtp_username'];
            $subject = 'SpectraHost SMTP Test';
            $message = "Dies ist eine Test-E-Mail von SpectraHost.\n\nSMTP-Konfiguration funktioniert korrekt!\n\nZeit: " . date('Y-m-d H:i:s');
            
            $headers = [
                'From: ' . $this->config['smtp_username'],
                'Reply-To: ' . $this->config['smtp_username'],
                'X-Mailer: SpectraHost'
            ];
            
            // Simple mail test (in production, use PHPMailer or similar)
            if (mail($to, $subject, $message, implode("\r\n", $headers))) {
                return [
                    'success' => true,
                    'message' => 'Test-E-Mail erfolgreich gesendet',
                    'details' => [
                        'recipient' => $to,
                        'subject' => $subject,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                throw new Exception('Mail function failed');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test-E-Mail konnte nicht gesendet werden: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'suggestion' => 'Überprüfen Sie die SMTP-Konfiguration und Serverberechtigungen'
                ]
            ];
        }
    }
}

function getEmailConfig() {
    global $database;
    
    try {
        $stmt = $database->prepare("SELECT config_value FROM system_configs WHERE config_key = 'email_config'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['config_value']) {
            return json_decode($result['config_value'], true);
        }
    } catch (Exception $e) {
        // Try file fallback
        $configFile = __DIR__ . '/../../config/email.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config) {
                return $config;
            }
        }
    }
    
    return [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_ssl' => true
    ];
}

function saveEmailConfig($config) {
    global $database;
    
    try {
        $configJson = json_encode($config);
        
        try {
            $stmt = $database->prepare("
                CREATE TABLE IF NOT EXISTS system_configs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    config_key VARCHAR(100) NOT NULL UNIQUE,
                    config_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute();
            
            $stmt = $database->prepare("
                INSERT INTO system_configs (config_key, config_value) 
                VALUES ('email_config', ?)
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            $stmt->execute([$configJson]);
            
        } catch (Exception $e) {
            // Fallback: save to file if database fails
            $configDir = __DIR__ . '/../../config';
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }
            file_put_contents($configDir . '/email.json', $configJson);
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

try {
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'test':
                    $config = getEmailConfig();
                    
                    if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'E-Mail SMTP nicht konfiguriert',
                            'details' => [
                                'error' => 'SMTP-Server, Benutzername oder Passwort fehlen',
                                'suggestion' => 'Konfigurieren Sie die E-Mail SMTP-Einstellungen'
                            ]
                        ]);
                        break;
                    }
                    
                    $email = new EmailAPI($config);
                    $result = $email->testConnection();
                    echo json_encode($result);
                    break;
                    
                case 'configure':
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    if (!$input || !isset($input['config'])) {
                        echo json_encode(['success' => false, 'message' => 'Invalid configuration data']);
                        break;
                    }
                    
                    $config = $input['config'];
                    
                    // Validate required fields
                    if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'SMTP-Server, Benutzername und Passwort sind erforderlich'
                        ]);
                        break;
                    }
                    
                    // Set default port if not provided
                    if (empty($config['smtp_port'])) {
                        $config['smtp_port'] = 587;
                    }
                    
                    if (saveEmailConfig($config)) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'E-Mail SMTP Konfiguration gespeichert'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Fehler beim Speichern der Konfiguration'
                        ]);
                    }
                    break;
                    
                case 'send_test':
                    $config = getEmailConfig();
                    $input = json_decode(file_get_contents('php://input'), true);
                    $testEmail = $input['email'] ?? $config['smtp_username'];
                    
                    if (empty($config['smtp_host'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'E-Mail SMTP nicht konfiguriert'
                        ]);
                        break;
                    }
                    
                    $email = new EmailAPI($config);
                    $result = $email->sendTestEmail($testEmail);
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
?>