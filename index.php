<?php
require_once 'includes/data-functions.php';

// Start session
session_start();

// If user is already logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: /dashboard.php');
    exit;
}

// Handle form submissions and URL parameters
$error_message = '';
$success_message = '';

// Check for logout success message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success_message = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate CSRF token if not exists
    $csrf_token = generateCSRFToken();
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Security validation failed. Please try again.';
    } else {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'login') {
                $email = sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                
                if (empty($email) || empty($password)) {
                    $error_message = 'Please fill in all fields.';
                } elseif (!isValidEmail($email)) {
                    $error_message = 'Please enter a valid email address.';
                } else {
                    $result = loginUser($email, $password);
                    if ($result['success']) {
                        // Redirect to dashboard or requested page
                        $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : '/dashboard.php';
                        unset($_SESSION['redirect_url']);
                        header('Location: ' . $redirect_url);
                        exit;
                    } else {
                        $error_message = $result['message'];
                    }
                }
            } elseif ($_POST['action'] == 'register') {
                $name = sanitizeInput($_POST['name']);
                $email = sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                
                if (empty($name) || empty($email) || empty($password)) {
                    $error_message = 'Please fill in all fields.';
                } elseif (!isValidEmail($email)) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (!isValidPassword($password)) {
                    $error_message = 'Password must be at least 6 characters long.';
                } else {
                    $result = registerUser($name, $email, $password);
                    if ($result['success']) {
                        $success_message = $result['message'] . ' You can now sign in.';
                    } else {
                        $error_message = $result['message'];
                    }
                }
            }
        }
    }
}

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="Logo.png">
</head>
<body>
    <div class="container" id="container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show position-absolute top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show position-absolute top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-container sign-up">
            <form action="" method="POST" id="registerForm">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <h1>Create Account</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span>or use your email for registration</span>
                <input type="text" name="name" placeholder="Full Name" required maxlength="100" 
                       value="<?php echo isset($_POST['name']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['name']) : ''; ?>">
                <input type="email" name="email" placeholder="Email Address" required maxlength="100"
                       value="<?php echo isset($_POST['email']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password (min 6 characters)" required minlength="6">
                <button type="submit">Sign Up</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <form action="" method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <h1>Sign In</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span>or use your email and password</span>
                <input type="email" name="email" placeholder="Email Address" required
                       value="<?php echo isset($_POST['email']) && $_POST['action'] == 'login' ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <a href="#" id="forgot-password-link">Forgot Your Password?</a>
                <button type="submit">Sign In</button>
            </form>
        </div>

        <div class="form-container forgot-password">
            <div class="engineer-image-container">
                <img src="im.png" alt="Working Engineer" class="engineer-image">
                <div class="engineer-animation">
                    <div class="code-lines">
                        <div class="code-line"></div>
                        <div class="code-line short"></div>
                        <div class="code-line"></div>
                        <div class="code-line medium"></div>
                    </div>
                </div>
            </div>
            <div class="message-container">
                <h1>Work In Progress</h1>
                <p>Our engineers are working hard to implement this feature.</p>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <span class="progress-text">80% Complete</span>
                </div>
                <button class="back-btn" id="back-to-login">← Back to Sign In</button>
            </div>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to access all forum features</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to join our community</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>