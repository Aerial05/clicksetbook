<?php 
require_once 'includes/auth.php';

// Prevent logged-in users from accessing registration page
requireNotLoggedIn();

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    error_log("Registration attempt - Name: $name, Email: $email, Password length: " . strlen($password));
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields';
        error_log("Validation failed: Empty fields - Name: '$name', Email: '$email', Password length: " . strlen($password));
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
        error_log("Validation failed: Invalid email format - $email");
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long';
        error_log("Validation failed: Password too short - length: " . strlen($password));
    } else {
        // Generate username from email
        $username = strtolower(str_replace(['@', '.', '+', '-'], ['', '', '', ''], explode('@', $email)[0]));
        error_log("Generated username: $username");
        
        // Ensure username is unique
        $originalUsername = $username;
        $counter = 1;
        $auth = new Auth();
        
        // Check if username exists and make it unique
        while ($auth->usernameExists($username)) {
            $username = $originalUsername . $counter;
            $counter++;
        }
        error_log("Final username to use: $username");
        
        // Register user
        $result = $auth->register($username, $email, $password, $name, '', '', null);
        error_log("Registration result: " . json_encode($result));
        
        if ($result['success']) {
            // Account created successfully, redirect to login
            $success_message = 'Account created successfully! Please log in.';
            
            // Store success message in session to show on signin page
            $_SESSION['registration_success'] = 'Account created successfully! Please sign in to continue.';
            
            // Redirect to signin page
            header('Location: signin.php');
            exit();
        } else {
            $error_message = $result['message'];
            error_log("Registration failed with message: " . $result['message']);
        }
    }
}

include 'header.php'; 
?>

<!-- Create Account Screen -->
<div class="auth-container">
    <div class="auth-header">
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">We are here to help you!</p>
    </div>
    
    <div class="auth-form">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="name" placeholder="Your Name" class="form-input" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="Your Email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="Password" class="form-input" required minlength="6">
            </div>
            
            <button type="submit" class="btn-primary btn-full">Create Account</button>
        </form>
        
        <div class="divider">
            <span>or</span>
        </div>
        
        <button class="btn-social" onclick="handleGoogleLogin()">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" class="social-icon">
            Continue with Google
        </button>
        
        <button class="btn-social" onclick="handleFacebookLogin()">
            <i class="fab fa-facebook-f social-icon"></i>
            Continue with Facebook
        </button>
        
        <p class="auth-footer">
            Do you have an account? <a href="signin.php" class="auth-link">Sign In</a>
        </p>
    </div>
</div>

<script>
// Social login handlers
function handleGoogleLogin() {
    showNotification('Google login coming soon!', 'info');
}

function handleFacebookLogin() {
    showNotification('Facebook login coming soon!', 'info');
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add notification styles
    const notificationStyles = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: slideInRight 0.3s ease-out;
        }
        
        .notification-success {
            background: #10b981;
            color: white;
        }
        
        .notification-error {
            background: #ef4444;
            color: white;
        }
        
        .notification-info {
            background: #3b82f6;
            color: white;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        
        .notification-message {
            flex: 1;
            font-weight: 500;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            opacity: 0.8;
        }
        
        .notification-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    
    // Add styles if not already present
    if (!document.querySelector('#notification-styles')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'notification-styles';
        styleSheet.textContent = notificationStyles;
        document.head.appendChild(styleSheet);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>

<?php include 'footer.php'; ?>
