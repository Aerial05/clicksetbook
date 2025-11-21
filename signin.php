<?php 
require_once 'includes/auth.php';

// Prevent logged-in users from accessing signin page
requireNotLoggedIn();

$error_message = '';
$success_message = '';

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        // Attempt login
        $auth = new Auth();
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            // Start user session
            startUserSession($result['user']);
            
            // DEBUG: Verify session was set
            error_log("=== SIGNIN: Session after startUserSession ===");
            error_log("Session ID: " . session_id());
            error_log("user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
            error_log("logged_in: " . ($_SESSION['logged_in'] ?? 'NOT SET'));
            error_log("Session status: " . session_status());
            
            // Redirect based on user role
            if ($result['user']['role'] === 'admin') {
                header('Location: admin-dashboard.php');
                exit();
            }
            
            // For non-admin users, redirect to dashboard
            // Dashboard will check profile completion and redirect if needed
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

include 'header.php'; 
?>

<!-- Sign In Screen -->
<div class="auth-container">
    <div class="auth-header">
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
        <h2 class="auth-title">Hi, Welcome Back!</h2>
        <p class="auth-subtitle">Hope you're doing fine.</p>
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
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="Your Email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="Password" class="form-input" required>
            </div>
            
            <button type="submit" class="btn-primary btn-full">Sign In</button>
        </form>
        
        <div class="divider">
            <span>or</span>
        </div>
        
        <button class="btn-social" onclick="handleGoogleLogin()">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" class="social-icon">
            Sign in with Google
        </button>
        
        <button class="btn-social" onclick="handleFacebookLogin()">
            <i class="fab fa-facebook-f social-icon"></i>
            Sign in with Facebook
        </button>
        
        <div class="auth-links">
            <a href="forget-password.php" class="auth-link">Forget password?</a>
            <p class="auth-footer">
                Don't have an account yet? <a href="create-account.php" class="auth-link">Sign up</a>
            </p>
            <p class="auth-footer" style="margin-top: 16px; font-size: 12px;">
                <a href="reset-onboarding.php" class="auth-link" style="color: #9ca3af;">Reset onboarding (for testing)</a>
            </p>
        </div>
    </div>
</div>

<script>
// Social login handlers
function handleGoogleLogin() {
    alert('Google login coming soon!');
}

function handleFacebookLogin() {
    alert('Facebook login coming soon!');
}
</script>

<?php include 'footer.php'; ?>
