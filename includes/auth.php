<?php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function register($email, $password, $firstName, $lastName) {
        // Check if user already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            throw new Exception('Benutzer existiert bereits');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, first_name, last_name, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
        
        return $this->db->lastInsertId();
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Ungültige Anmeldedaten');
        }
        
        // Update last login
        $stmt = $this->db->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['login_time'] = time();
        
        return $user;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user'];
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        $user = $this->getCurrentUser();
        if (!$user || !$user['is_admin']) {
            header('HTTP/1.1 403 Forbidden');
            die('Zugriff verweigert - Admin-Berechtigung erforderlich');
        }
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Global wrapper functions for API compatibility
function isLoggedIn() {
    $auth = new Auth();
    return $auth->isLoggedIn();
}

function getCurrentUser() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}

function requireLogin() {
    $auth = new Auth();
    return $auth->requireLogin();
}

function requireAdmin() {
    $auth = new Auth();
    return $auth->requireAdmin();
}

$auth = new Auth();

// Note: Global helper functions moved to includes/session.php
?>