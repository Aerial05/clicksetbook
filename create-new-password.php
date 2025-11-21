<?php 
require_once 'includes/auth.php';

// Prevent logged-in users from accessing this page
requireNotLoggedIn();

// Verify token exists in session
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_email'])) {
    header('Location: forget-password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$token = $_SESSION['reset_token'];

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($password) || empty($confirmPassword)) {
        $error_message = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $error_message = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters';
    } else {
        // Reset password
        $auth = new Auth();
        $result = $auth->resetPassword($token, $password);
        
        if ($result['success']) {
            // Clear reset session data
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_code']);
            
            $success_message = 'Password reset successfully! Redirecting to sign in...';
            
            // Redirect to signin after 2 seconds
            header('refresh:2;url=signin.php');
        } else {
            $error_message = $result['message'];
        }
    }
}

include 'header.php'; 
?>

<!-- Create New Password Screen -->
<div class="auth-container">
    <div class="auth-header">
        <a href="verify-code.php?email=<?php echo urlencode($email); ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="logo-container">
            <div class="logo-icon">
                <div class="cross-icon">
                    <div class="cross-vertical"></div>
                    <div class="cross-horizontal"></div>
                    <div class="cross-center"></div>
                </div>
            </div>
            <h1 class="app-title">Click Set Book</h1>
        </div>
        <h2 class="auth-title">Create new password</h2>
        <p class="auth-subtitle">Your new password must be different from previously used password!</p>
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
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="Password" class="form-input" required minlength="6">
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-input" required minlength="6">
            </div>
            
            <button type="submit" class="btn-primary btn-full">Reset Password</button>
        </form>
    </div>
</div>

<script>
// Handle form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (!password || !confirmPassword) {
        e.preventDefault();
        showNotification('Please fill in all fields', 'error');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        showNotification('Passwords do not match', 'error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showNotification('Password must be at least 6 characters', 'error');
        return false;
    }
});

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


<script>
// Handle form submission
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (!password || !confirmPassword) {
        showNotification('Please fill in all fields', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showNotification('Passwords do not match', 'error');
        return;
    }
    
    if (password.length < 6) {
        showNotification('Password must be at least 6 characters', 'error');
        return;
    }
    
    // Simulate password reset
    showNotification('Password reset successfully!', 'success');
    
    // Redirect to sign in
    setTimeout(() => {
        window.location.href = 'signin.php';
    }, 1500);
});

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
