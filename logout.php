<?php
require_once 'includes/data-functions.php';

// Start session
session_start();

// Destroy user session
destroyUserSession();

// Redirect to login page with success message
header('Location: /index.php?logout=success');
exit;
?>