<?php
// Simple file-based user storage for demo purposes when MySQL is not available
// In production with XAMPP, this would use the MySQL database

function getJSONUserStorage() {
    $storage_file = __DIR__ . '/../users.json';
    if (!file_exists($storage_file)) {
        file_put_contents($storage_file, json_encode([]));
    }
    return $storage_file;
}

function saveJSONUsers($users) {
    $storage_file = getJSONUserStorage();
    file_put_contents($storage_file, json_encode($users, JSON_PRETTY_PRINT));
}

function getJSONUsers() {
    $storage_file = getJSONUserStorage();
    $content = file_get_contents($storage_file);
    return json_decode($content, true) ?: [];
}

// Override database functions for demo when MySQL is not available
function registerUserDemo($name, $email, $password) {
    $users = getJSONUsers();
    
    // Check if email already exists
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Create new user
    $newUser = [
        'id' => count($users) + 1,
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'is_active' => 1
    ];
    
    $users[] = $newUser;
    saveJSONUsers($users);
    
    return ['success' => true, 'message' => 'Registration successful'];
}

function loginUserDemo($email, $password) {
    $users = getJSONUsers();
    
    foreach ($users as &$user) {
        if ($user['email'] === $email && $user['is_active']) {
            if (password_verify($password, $user['password'])) {
                // Update last login
                $user['last_login'] = date('Y-m-d H:i:s');
                saveJSONUsers($users);
                
                // Create session
                createUserSessionDemo($user['id']);
                
                return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
            }
        }
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

function createUserSessionDemo($userId) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
    $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
}

function isAuthenticatedDemo() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    // Check if session has expired
    if (isset($_SESSION['expires_at']) && $_SESSION['expires_at'] < time()) {
        destroyUserSessionDemo();
        return false;
    }
    
    // Extend session
    $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
    
    return true;
}

function getCurrentUserDemo() {
    if (!isAuthenticatedDemo()) {
        return null;
    }
    
    $users = getJSONUsers();
    foreach ($users as $user) {
        if ($user['id'] == $_SESSION['user_id']) {
            return $user;
        }
    }
    
    return null;
}

function destroyUserSessionDemo() {
    if (!isset($_SESSION)) {
        session_start();
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
?>