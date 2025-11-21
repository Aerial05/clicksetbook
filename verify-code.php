<?php 
require_once 'includes/auth.php';

// Prevent logged-in users from accessing this page
requireNotLoggedIn();

// Get email from URL parameter or session
$email = $_GET['email'] ?? $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    header('Location: forget-password.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = '';
    $code .= $_POST['code1'] ?? '';
    $code .= $_POST['code2'] ?? '';
    $code .= $_POST['code3'] ?? '';
    $code .= $_POST['code4'] ?? '';
    $code .= $_POST['code5'] ?? '';
    
    if (strlen($code) !== 5) {
        $error_message = 'Please enter the complete 5-digit code';
    } else {
        // Verify code
        $auth = new Auth();
        $result = $auth->verifyResetCode($email, $code);
        
        if ($result['success']) {
            // Store token in session
            $_SESSION['reset_token'] = $result['token'];
            $_SESSION['reset_email'] = $email;
            
            // Redirect to create new password
            header('Location: create-new-password.php');
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

// Handle resend code request
if (isset($_GET['resend'])) {
    $auth = new Auth();
    $result = $auth->createPasswordReset($email);
    if ($result['success']) {
        $_SESSION['reset_code'] = $result['code']; // For testing
        $success_message = 'A new verification code has been sent to your email';
    }
}

include 'header.php'; 
?>

<!-- Verify Code Screen -->
<div class="auth-container">
    <div class="auth-header">
        <a href="forget-password.php" class="back-btn">
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
        <h2 class="auth-title">Verify Code</h2>
        <p class="auth-subtitle">Enter the code we just sent you on your registered email.</p>
        <p class="auth-subtitle" style="font-size: 14px; color: #9ca3af; margin-top: 8px;">Sent to: <?php echo htmlspecialchars($email); ?></p>
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
            <div class="verification-inputs">
                <input type="text" name="code1" class="verification-input" maxlength="1" oninput="moveToNext(this)" required>
                <input type="text" name="code2" class="verification-input" maxlength="1" oninput="moveToNext(this)" required>
                <input type="text" name="code3" class="verification-input" maxlength="1" oninput="moveToNext(this)" required>
                <input type="text" name="code4" class="verification-input" maxlength="1" oninput="moveToNext(this)" required>
                <input type="text" name="code5" class="verification-input" maxlength="1" oninput="moveToNext(this)" required>
            </div>
            
            <button type="submit" class="btn-primary btn-full">Verify</button>
            
            <p class="resend-text">
                Didn't get the Code? <a href="verify-code.php?email=<?php echo urlencode($email); ?>&resend=1" class="auth-link">Resend</a>
            </p>
        </form>
    </div>
</div>

<?php 
// Display code for testing (remove in production)
if (isset($_SESSION['reset_code'])): 
?>
<script>
console.log('Test Code: <?php echo $_SESSION['reset_code']; ?>');
</script>
<?php endif; ?>

<script>
// Verification Code Input Handling
function moveToNext(input) {
    const inputs = document.querySelectorAll('.verification-input');
    const currentIndex = Array.from(inputs).indexOf(input);
    
    // Add filled class for visual feedback
    if (input.value) {
        input.classList.add('filled');
    } else {
        input.classList.remove('filled');
    }
    
    // Move to next input if current one has a value
    if (input.value && currentIndex < inputs.length - 1) {
        inputs[currentIndex + 1].focus();
    }
    
    // Auto-verify if all fields are filled
    let allFilled = true;
    inputs.forEach(inp => {
        if (!inp.value) allFilled = false;
    });
    
    if (allFilled) {
        setTimeout(() => verifyCode(), 500);
    }
}

// Handle backspace in verification inputs
document.addEventListener('keydown', function(e) {
    if (e.key === 'Backspace') {
        const activeElement = document.activeElement;
        if (activeElement && activeElement.classList.contains('verification-input')) {
            const inputs = document.querySelectorAll('.verification-input');
            const currentIndex = Array.from(inputs).indexOf(activeElement);
            
            // If current input is empty, move to previous input
            if (!activeElement.value && currentIndex > 0) {
                inputs[currentIndex - 1].focus();
                inputs[currentIndex - 1].value = '';
                inputs[currentIndex - 1].classList.remove('filled');
            }
        }
    }
});

// Handle paste in verification inputs
document.addEventListener('paste', function(e) {
    const activeElement = document.activeElement;
    if (activeElement && activeElement.classList.contains('verification-input')) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 5);
        const inputs = document.querySelectorAll('.verification-input');
        
        for (let i = 0; i < pastedData.length && i < inputs.length; i++) {
            inputs[i].value = pastedData[i];
            inputs[i].classList.add('filled');
        }
        
        // Focus the next empty input or the last one
        const nextEmptyIndex = Math.min(pastedData.length, inputs.length - 1);
        inputs[nextEmptyIndex].focus();
        
        // Auto-verify if all fields are filled
        if (pastedData.length === 5) {
            setTimeout(() => verifyCode(), 500);
        }
    }
});

// Handle form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const inputs = document.querySelectorAll('.verification-input');
    let code = '';
    
    inputs.forEach(input => {
        code += input.value;
    });
    
    if (code.length !== 5) {
        e.preventDefault();
        showNotification('Please enter the complete 5-digit code', 'error');
        return false;
    }
});

function resendCode() {
    showNotification('Verification code resent to your email', 'info');
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
