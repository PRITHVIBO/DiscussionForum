<?php
// Authentication check - include this at the top of protected pages
require_once __DIR__ . '/data-functions.php';

// Start session if not already started
if (!isset($_SESSION)) {
    session_start();
}

// Check authentication
if (!isAuthenticated()) {
    // Store the requested URL to redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header('Location: /index.php');
    exit;
}

// Get current user for use in protected pages
$current_user = getCurrentUser();

// If user data couldn't be retrieved, something is wrong with the session
if (!$current_user) {
    destroyUserSession();
    header('Location: /index.php');
    exit;
}
?>