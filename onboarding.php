<?php 
require_once 'includes/auth.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    if ($currentUser) {
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

<!-- Onboarding Screens -->
<div class="onboarding-container">
    <!-- Onboarding 1: Book Appointments Online -->
    <div id="onboarding-1" class="onboarding-slide active">
        <div class="onboarding-image">
            <div class="medical-image">
                <i class="fas fa-stethoscope"></i>
                <div class="medical-clipboard"></div>
            </div>
        </div>
        <div class="onboarding-content">
            <h2 class="onboarding-title">Book Appointments Online</h2>
            <p class="onboarding-description">
                Schedule your medical appointments effortlessly from the comfort of your home, anytime and anywhere.
            </p>
            <button class="btn-primary" onclick="nextOnboarding()">Next</button>
            <div class="onboarding-progress">
                <div class="progress-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
                <a href="create-account.php" class="skip-link">Skip</a>
            </div>
        </div>
    </div>

    <!-- Onboarding 2: Connect with Specialists -->
    <div id="onboarding-2" class="onboarding-slide">
        <div class="onboarding-image">
            <div class="doctor-image">
                <div class="doctor-avatar">
                    <div class="doctor-face"></div>
                    <div class="doctor-hair"></div>
                    <div class="doctor-coat"></div>
                </div>
            </div>
        </div>
        <div class="onboarding-content">
            <h2 class="onboarding-title">Connect with Specialists</h2>
            <p class="onboarding-description">
                Access a network of qualified specialists ready to provide personalized care and guidance for your health needs.
            </p>
            <button class="btn-primary" onclick="nextOnboarding()">Next</button>
            <div class="onboarding-progress">
                <div class="progress-dots">
                    <span class="dot"></span>
                    <span class="dot active"></span>
                    <span class="dot"></span>
                </div>
                <a href="create-account.php" class="skip-link">Skip</a>
            </div>
        </div>
    </div>

    <!-- Onboarding 3: Thousands of Clinics -->
    <div id="onboarding-3" class="onboarding-slide">
        <div class="onboarding-image">
            <div class="clinics-image">
                <div class="clinic-buildings">
                    <div class="building building-1"></div>
                    <div class="building building-2"></div>
                    <div class="building building-3"></div>
                    <div class="building building-4"></div>
                </div>
                <div class="clinic-pins">
                    <div class="pin pin-1">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="pin pin-2">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="pin pin-3">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                </div>
                <div class="main-heart">
                    <i class="fas fa-heart"></i>
                </div>
            </div>
        </div>
        <div class="onboarding-content">
            <h2 class="onboarding-title">Thousands of Clinics</h2>
            <p class="onboarding-description">
                Choose from thousands of clinics nearby, ensuring you find the right facility for your health services.
            </p>
            <button class="btn-primary" onclick="window.location.href='create-account.php'">Get Started</button>
            <div class="onboarding-progress">
                <div class="progress-dots">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot active"></span>
                </div>
                <a href="create-account.php" class="skip-link">Skip</a>
            </div>
        </div>
    </div>
</div>

<script>
// Mark onboarding as completed when user visits this page
localStorage.setItem('onboarding_completed', 'true');

// Global state management
let currentOnboardingSlide = 1;
const totalOnboardingSlides = 3;

// Onboarding navigation
function nextOnboarding() {
    if (currentOnboardingSlide < totalOnboardingSlides) {
        currentOnboardingSlide++;
        showOnboardingSlide(currentOnboardingSlide);
    } else {
        // Last slide - go to create account
        window.location.href = 'create-account.php';
    }
}

function showOnboardingSlide(slideNumber) {
    // Hide all onboarding slides
    const slides = document.querySelectorAll('.onboarding-slide');
    slides.forEach(slide => slide.classList.remove('active'));
    
    // Show the selected slide
    document.getElementById(`onboarding-${slideNumber}`).classList.add('active');
    
    // Update progress dots
    updateProgressDots(slideNumber);
}

function updateProgressDots(slideNumber) {
    const dots = document.querySelectorAll('.progress-dots .dot');
    dots.forEach((dot, index) => {
        dot.classList.remove('active');
        if (index === slideNumber - 1) {
            dot.classList.add('active');
        }
    });
}

// Keyboard navigation support
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        nextOnboarding();
    } else if (e.key === 'ArrowLeft' && currentOnboardingSlide > 1) {
        e.preventDefault();
        currentOnboardingSlide--;
        showOnboardingSlide(currentOnboardingSlide);
    } else if (e.key === 'Escape') {
        window.location.href = 'create-account.php';
    }
});

// Touch/swipe support for mobile
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const swipeDistance = touchEndX - touchStartX;
    
    if (Math.abs(swipeDistance) > swipeThreshold) {
        if (swipeDistance > 0 && currentOnboardingSlide > 1) {
            // Swipe right - previous slide
            currentOnboardingSlide--;
            showOnboardingSlide(currentOnboardingSlide);
        } else if (swipeDistance < 0 && currentOnboardingSlide < totalOnboardingSlides) {
            // Swipe left - next slide
            nextOnboarding();
        }
    }
}
</script>

<?php include 'footer.php'; ?>
