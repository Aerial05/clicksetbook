<?php 
require_once 'includes/auth.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    if ($currentUser) {
        // Check if profile is complete
        $auth = new Auth();
        if (!$auth->isProfileComplete($currentUser['id'])) {
            header('Location: fill-profile.php');
            exit();
        }
        
        // Profile complete, redirect to appropriate dashboard
        switch ($currentUser['role']) {
            case 'admin':
                header('Location: admin-dashboard.php');
                break;
            case 'doctor':
                header('Location: doctor-dashboard.php');
                break;
            case 'staff':
                header('Location: staff-dashboard.php');
                break;
            default:
                header('Location: dashboard.php');
        }
        exit();
    }
}

include 'header.php'; 
?>

<!-- Splash Screen -->
<div class="splash-screen">
    <div class="splash-container">
        <div class="splash-background">
            <div class="mosaic-pattern">
                <div class="mosaic-tile tile-1"></div>
                <div class="mosaic-tile tile-2"></div>
                <div class="mosaic-tile tile-3"></div>
                <div class="mosaic-tile tile-4"></div>
                <div class="mosaic-tile tile-5"></div>
                <div class="mosaic-tile tile-6"></div>
                <div class="mosaic-tile tile-7"></div>
                <div class="mosaic-tile tile-8"></div>
                <div class="mosaic-tile tile-9"></div>
                <div class="mosaic-tile tile-10"></div>
            </div>
        </div>
        <div class="splash-content">
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
        </div>
    </div>
</div>

<script>
// Check if user has already completed onboarding using localStorage
function hasCompletedOnboarding() {
    return localStorage.getItem('onboarding_completed') === 'true';
}

// Auto-redirect from splash after 3 seconds
setTimeout(() => {
    if (hasCompletedOnboarding()) {
        // Show brief message for returning users
        const logoContainer = document.querySelector('.logo-container');
        if (logoContainer) {
            logoContainer.innerHTML = `
                <div class="logo-icon">
                    <div class="cross-icon">
                        <div class="cross-vertical"></div>
                        <div class="cross-horizontal"></div>
                        <div class="cross-center"></div>
                    </div>
                </div>
                <h1 class="app-title">Welcome Back!</h1>
                <p style="color: rgba(255,255,255,0.8); margin-top: 10px;">Redirecting to sign in...</p>
            `;
        }
        
        // Redirect to sign-in page if onboarding was completed
        setTimeout(() => {
            window.location.href = 'signin.php';
        }, 1500);
    } else {
        // Show onboarding for first-time visitors
        window.location.href = 'onboarding.php';
    }
}, 3000);
</script>

<?php include 'footer.php'; ?>
