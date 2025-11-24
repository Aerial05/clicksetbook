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
            <!-- Animated gradient orbs -->
            <div class="gradient-orb orb-1"></div>
            <div class="gradient-orb orb-2"></div>
            <div class="gradient-orb orb-3"></div>
            
            <!-- Floating particles -->
            <div class="particles">
                <div class="particle particle-1"></div>
                <div class="particle particle-2"></div>
                <div class="particle particle-3"></div>
                <div class="particle particle-4"></div>
                <div class="particle particle-5"></div>
                <div class="particle particle-6"></div>
                <div class="particle particle-7"></div>
                <div class="particle particle-8"></div>
            </div>
        </div>
        <div class="splash-content">
            <div class="logo-container">
                <div class="logo-icon-wrapper">
                    <div class="logo-glow"></div>
                    <div class="logo-icon">
                        <div class="cross-icon">
                            <div class="cross-vertical"></div>
                            <div class="cross-horizontal"></div>
                            <div class="cross-center"></div>
                        </div>
                    </div>
                </div>
                <h1 class="app-title">Click Set Book</h1>
                <p class="app-tagline">Your Health, Our Priority</p>
                
                <!-- Clinic Information -->
                <div class="clinic-info" style="margin-top: 32px; padding: 20px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.2);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <svg style="width: 18px; height: 18px; color: rgba(255, 255, 255, 0.9); flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 13px; line-height: 1.5; margin: 0; text-align: left;">
                            National Road, Poblacion, Pulilan, Bulacan, Pulilan, Philippines
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <svg style="width: 18px; height: 18px; color: rgba(255, 255, 255, 0.9); flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 13px; margin: 0; text-align: left; font-weight: 500; letter-spacing: 0.3px;">
                            0977 758 7993
                        </p>
                    </div>
                </div>
                
                <div class="loading-dots">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
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
