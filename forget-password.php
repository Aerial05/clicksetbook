<?php 
require_once 'includes/auth.php';
require_once 'config/emailjs.php';

// Prevent logged-in users from accessing this page
requireNotLoggedIn();

$error_message = '';
$success_message = '';
$emailjs_config = getEmailJSConfig();

// Handle AJAX request for sending reset code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    
    // Basic validation
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your email']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email']);
        exit();
    }
    
    // Create password reset request
    $auth = new Auth();
    $result = $auth->createPasswordReset($email);
    
    if ($result['success']) {
        // Store email in session for verify-code page
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $result['code']; // For testing - remove in production
        
        // Return reset details for EmailJS
        echo json_encode([
            'success' => true,
            'message' => 'Verification code generated',
            'code' => $result['code'],
            'email' => $email
        ]);
        exit();
    } else {
        // Don't reveal if email exists or not for security
        // Always show success for security
        $_SESSION['reset_email'] = $email;
        echo json_encode([
            'success' => true,
            'message' => 'If the email exists, a code will be sent',
            'email' => $email
        ]);
        exit();
    }
}

include 'header.php'; 
?>

<!-- Forget Password Screen -->
<div class="auth-container">
    <div class="auth-header">
        <a href="signin.php" class="back-btn">
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
        <h2 class="auth-title">Forget Password?</h2>
        <p class="auth-subtitle">Enter your Email, we will send you a verification code.</p>
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
        
        <form id="forgotPasswordForm" method="POST" onsubmit="return false;">
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" id="emailInput" placeholder="Your Email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <button type="submit" id="submitBtn" class="btn-primary btn-full">
                <span class="btn-text">Send Code</span>
                <span class="btn-loader" style="display: none;">
                    <i class="fas fa-circle-notch fa-spin"></i> Sending...
                </span>
            </button>
        </form>
    </div>
</div>

<!-- EmailJS SDK -->
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script src="js/emailjs-service.js"></script>

<script>
// Initialize EmailJS configuration (same pattern as test-emailjs.php)
const emailJSConfig = <?php 
    if ($emailjs_config) {
        echo json_encode($emailjs_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS);
    } else {
        echo '{}';
    }
?>;

// Debug: Check if EmailJS SDK is loaded
console.log('Checking EmailJS SDK...');
console.log('emailjs available:', typeof emailjs !== 'undefined');
console.log('EmailJSService available:', typeof EmailJSService !== 'undefined');

const emailService = new EmailJSService(emailJSConfig);
console.log('EmailJSService created:', emailService);

// Check if EmailJS is configured
if (!emailService.isConfigured()) {
    console.warn('⚠️ EmailJS is not configured. Please update config/emailjs.php');
} else {
    console.log('✓ EmailJS is configured');
}

<?php 
// Display code for testing (remove in production)
if (isset($_SESSION['reset_code'])): 
?>
console.log('Test Code: <?php echo $_SESSION['reset_code']; ?>');
<?php endif; ?>

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

// Handle form submission with EmailJS
const form = document.getElementById('forgotPasswordForm');
if (!form._submitHandlerAttached) {
    form._submitHandlerAttached = true;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        console.log('Form submit handler called');
        
        const email = document.getElementById('emailInput').value.trim();
        const submitBtn = document.getElementById('submitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        // Prevent multiple submissions
        if (submitBtn.disabled) {
            console.log('Button already disabled, preventing duplicate submission');
            return false;
        }
    
    if (!email) {
        showNotification('Please enter your email', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('Please enter a valid email', 'error');
        return;
    }
    
    // Check if EmailJS is configured
    if (!emailService || !emailService.isConfigured()) {
        showNotification('Email service is not configured. Please contact administrator.', 'error');
        console.error('EmailJS not configured. Update config/emailjs.php');
        return;
    }
    
    // Disable button and show loader
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';
    
    try {
        // Step 1: Create password reset request in database
        console.log('Creating password reset request...');
        const response = await fetch('forget-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ajax=1&email=' + encodeURIComponent(email)
        });
        
        const data = await response.json();
        console.log('Server response:', data);
        
        if (data.success) {
            // Step 2: Send email using EmailJS (same as test-emailjs.php)
            const resetLink = window.location.origin + '/verify-code.php?email=' + encodeURIComponent(email);
            
            console.log('Sending password reset email with code:', data.code);
            console.log('EmailJS Config:', {
                serviceId: emailService.config.serviceId,
                templateId: emailService.config.templates.passwordReset,
                publicKey: emailService.config.publicKey
            });
            
            // Add timeout to prevent hanging
            const sendEmailWithTimeout = Promise.race([
                emailService.sendPasswordResetEmail(
                    email,
                    'User',
                    resetLink,
                    data.code
                ),
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Email send timeout after 10 seconds')), 10000)
                )
            ]);
            
            const emailResult = await sendEmailWithTimeout;
            
            console.log('Email result:', emailResult);
            
            if (emailResult.success) {
                showNotification('✓ Verification code sent to your email!', 'success');
                
                // Redirect to verify code page after 1.5 seconds
                setTimeout(() => {
                    window.location.href = 'verify-code.php?email=' + encodeURIComponent(email);
                }, 1500);
            } else {
                // Email send failed, but code was generated
                const errorMsg = emailResult.error || 'Unknown error';
                showNotification('Email send failed, but you can still use the code. Check console for details.', 'error');
                console.error('EmailJS Error Details:', {
                    error: emailResult.error,
                    config: {
                        publicKey: emailService.config.publicKey,
                        serviceId: emailService.config.serviceId,
                        templateId: emailService.config.templates.passwordReset
                    }
                });
                
                // Still redirect since code was generated
                setTimeout(() => {
                    window.location.href = 'verify-code.php?email=' + encodeURIComponent(email);
                }, 3000);
            }
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
        
    } catch (error) {
        console.error('Error details:', error);
        console.error('Error stack:', error.stack);
        
        // Provide more specific error messages
        let errorMessage = 'Failed to process request. ';
        if (error.message && error.message.includes('timeout')) {
            errorMessage += 'Email service timed out. The code was generated but email may not have been sent. You can still try using the code. ';
            
            // Still allow proceeding to verify page since code was generated
            showNotification(errorMessage, 'error');
            setTimeout(() => {
                if (document.getElementById('emailInput').value) {
                    window.location.href = 'verify-code.php?email=' + encodeURIComponent(document.getElementById('emailInput').value);
                }
            }, 3000);
        } else if (error.message && error.message.includes('EmailJS')) {
            errorMessage += 'Email service initialization failed. ';
            showNotification(errorMessage, 'error');
        } else if (error.message && error.message.includes('fetch')) {
            errorMessage += 'Network error. Check your connection. ';
            showNotification(errorMessage, 'error');
        } else {
            errorMessage += error.message || 'Please try again or contact support. ';
            showNotification(errorMessage, 'error');
        }
    } finally {
        // Re-enable button
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
    });
} else {
    console.log('Submit handler already attached, skipping');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
</script>

<?php include 'footer.php'; ?>
