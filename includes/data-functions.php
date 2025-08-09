<?php
require_once __DIR__ . '/../config/config.php';

/**
 * User Authentication and Management Functions
 */

/**
 * Register a new user
 */
function registerUser($name, $email, $password) {
    try {
        $pdo = getDBConnection();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);
        
        return ['success' => true, 'message' => 'Registration successful'];
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("SELECT id, name, email, password, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Create session
        createUserSession($user['id']);
        
        return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Please try again.'];
    }
}

/**
 * Create user session
 */
function createUserSession($userId) {
    session_start();
    
    // Generate session token
    $sessionToken = bin2hex(random_bytes(32));
    
    try {
        $pdo = getDBConnection();
        
        // Clean up old sessions for this user
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$userId]);
        
        // Create new session record
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $sessionToken, 
            $expiresAt, 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
        
    } catch (PDOException $e) {
        error_log("Session creation error: " . $e->getMessage());
    }
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    // Check if session has expired
    if (isset($_SESSION['expires_at']) && $_SESSION['expires_at'] < time()) {
        destroyUserSession();
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT us.id, u.is_active 
            FROM user_sessions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.user_id = ? AND us.session_token = ? AND us.expires_at > NOW() AND us.is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
        $session = $stmt->fetch();
        
        if (!$session || !$session['is_active']) {
            destroyUserSession();
            return false;
        }
        
        // Extend session
        $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
        $stmt = $pdo->prepare("UPDATE user_sessions SET expires_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s', $_SESSION['expires_at']), $session['id']]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Authentication check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current user information
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Get current user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Destroy user session (logout)
 */
function destroyUserSession() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (isset($_SESSION['session_token'])) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
            $stmt->execute([$_SESSION['session_token']]);
        } catch (PDOException $e) {
            error_log("Session destruction error: " . $e->getMessage());
        }
    }
    
    // Clear session variables
    $_SESSION = [];
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Sanitize input data
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function isValidPassword($password) {
    // At least 6 characters long
    return strlen($password) >= 6;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to login if not authenticated
 */
function requireAuthentication() {
    if (!isAuthenticated()) {
        header('Location: /index.php');
        exit;
    }
}
?>