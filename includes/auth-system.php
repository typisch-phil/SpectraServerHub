<?php
/**
 * Modernes Authentication System für SpectraHost
 * Direkte Datenbankintegration mit sicherer Session-Verwaltung
 */

class AuthSystem {
    private $db;
    private $sessionLifetime = 7200; // 2 Stunden
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->initSession();
    }
    
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => $this->sessionLifetime,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }
    
    /**
     * Benutzer anmelden
     */
    public function login($email, $password) {
        try {
            // Benutzer aus Datenbank laden
            $stmt = $this->db->prepare("
                SELECT id, email, password, first_name, last_name, role, balance, status, last_login
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                $this->logFailedLogin($email);
                throw new Exception('Ungültige Anmeldedaten');
            }
            
            // Login-Versuch zählen (Brute-Force-Schutz)
            if ($this->isAccountLocked($email)) {
                throw new Exception('Konto temporär gesperrt. Versuchen Sie es später erneut.');
            }
            
            // Session erstellen
            $this->createSession($user);
            
            // Last Login aktualisieren
            $this->updateLastLogin($user['id']);
            
            // Erfolgreiche Anmeldung protokollieren
            $this->logSuccessfulLogin($user['id']);
            
            return [
                'success' => true,
                'message' => 'Anmeldung erfolgreich',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['role'],
                    'balance' => floatval($user['balance'])
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Benutzer registrieren
     */
    public function register($email, $password, $firstName, $lastName, $phone = null) {
        try {
            // Validierung
            if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
                throw new Exception('Alle Pflichtfelder müssen ausgefüllt werden');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ungültige E-Mail-Adresse');
            }
            
            if (strlen($password) < 8) {
                throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
            }
            
            // Prüfen ob E-Mail bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('E-Mail-Adresse bereits registriert');
            }
            
            // Benutzer erstellen
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, first_name, last_name, phone, role, balance, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'user', 0.00, 'active', NOW())
            ");
            
            $stmt->execute([
                $email,
                $hashedPassword,
                $firstName,
                $lastName,
                $phone
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Registrierung protokollieren
            $this->logUserRegistration($userId);
            
            return [
                'success' => true,
                'message' => 'Registrierung erfolgreich',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Benutzer abmelden
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logUserLogout($_SESSION['user_id']);
        }
        
        session_destroy();
        return ['success' => true, 'message' => 'Erfolgreich abgemeldet'];
    }
    
    /**
     * Prüfen ob Benutzer angemeldet ist
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }
    
    /**
     * Aktuellen Benutzer abrufen
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("
            SELECT id, email, first_name, last_name, role, balance, status
            FROM users 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Session erstellen
     */
    private function createSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Last Login aktualisieren
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    /**
     * Gescheiterte Anmeldung protokollieren
     */
    private function logFailedLogin($email) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (email, ip_address, success, attempted_at)
            VALUES (?, ?, 0, NOW())
        ");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);
    }
    
    /**
     * Erfolgreiche Anmeldung protokollieren
     */
    private function logSuccessfulLogin($userId) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (user_id, ip_address, success, attempted_at)
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
    }
    
    /**
     * Benutzer-Registrierung protokollieren
     */
    private function logUserRegistration($userId) {
        $stmt = $this->db->prepare("
            INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at)
            VALUES (?, 'registration', 'User registered', ?, NOW())
        ");
        $stmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
    }
    
    /**
     * Benutzer-Abmeldung protokollieren
     */
    private function logUserLogout($userId) {
        $stmt = $this->db->prepare("
            INSERT INTO user_activities (user_id, activity_type, description, ip_address, created_at)
            VALUES (?, 'logout', 'User logged out', ?, NOW())
        ");
        $stmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
    }
    
    /**
     * Prüfen ob Konto gesperrt ist (Brute-Force-Schutz)
     */
    private function isAccountLocked($email) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as failed_attempts
            FROM login_attempts 
            WHERE email = ? 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['failed_attempts'] >= 5;
    }
}
?>