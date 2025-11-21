// Global state management for PHP pages
let currentOnboardingSlide = 1;
const totalOnboardingSlides = 3;

// Initialize the app for PHP pages
document.addEventListener('DOMContentLoaded', function() {
    // Initialize page-specific functionality
    initializePage();
});

function initializePage() {
    // Add common functionality that works across all PHP pages
    initializeCommonFeatures();
    
    // Page-specific initialization based on current page
    const currentPage = getCurrentPage();
    switch(currentPage) {
        case 'onboarding':
            initializeOnboarding();
            break;
        case 'create-account':
            initializeCreateAccount();
            break;
        case 'fill-profile':
            initializeFillProfile();
            break;
        case 'signin':
            initializeSignIn();
            break;
        case 'forget-password':
            initializeForgetPassword();
            break;
        case 'verify-code':
            initializeVerifyCode();
            break;
        case 'create-new-password':
            initializeCreateNewPassword();
            break;
    }
}

function getCurrentPage() {
    const path = window.location.pathname;
    const page = path.split('/').pop().replace('.php', '');
    return page;
}

function initializeCommonFeatures() {
    // Common functionality for all pages
    initializeNotifications();
    initializeFormValidation();
    initializeLocalStorage();
}

function initializeLocalStorage() {
    // Global localStorage utility functions
    window.hasCompletedOnboarding = function() {
        return localStorage.getItem('onboarding_completed') === 'true';
    };
    
    window.markOnboardingCompleted = function() {
        localStorage.setItem('onboarding_completed', 'true');
    };
    
    window.clearOnboardingStatus = function() {
        localStorage.removeItem('onboarding_completed');
    };
}

function initializeNotifications() {
    // Notification system is already implemented in individual pages
    // This function can be extended for common notification features
}

function initializeFormValidation() {
    // Common form validation features
    // This function can be extended for common validation features
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

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

// Form handling
function validateForm(formType) {
    let isValid = true;
    const errors = [];
    
    if (formType === 'create-account') {
        const name = document.querySelector('#create-account-screen input[placeholder="Your Name"]').value.trim();
        const email = document.querySelector('#create-account-screen input[placeholder="Your Email"]').value.trim();
        const password = document.querySelector('#create-account-screen input[placeholder="Password"]').value;
        
        if (!name) {
            errors.push('Name is required');
            isValid = false;
        }
        
        if (!email) {
            errors.push('Email is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            errors.push('Please enter a valid email');
            isValid = false;
        }
        
        if (!password) {
            errors.push('Password is required');
            isValid = false;
        } else if (password.length < 6) {
            errors.push('Password must be at least 6 characters');
            isValid = false;
        }
    }
    
    if (!isValid) {
        showNotification(errors.join(', '), 'error');
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Profile completion
function completeProfile() {
    const nickname = document.querySelector('#fill-profile-screen input[placeholder="Nickname"]').value.trim();
    const dateOfBirth = document.querySelector('#fill-profile-screen input[type="date"]').value;
    const gender = document.querySelector('#fill-profile-screen select').value;
    
    if (!nickname || !dateOfBirth || !gender) {
        showNotification('Please fill in all profile fields', 'error');
        return;
    }
    
    // Show success modal
    showSuccessModal();
}

// Success Modal Functions
function showSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.add('active');
    
    // Auto-redirect after 5 seconds
    setTimeout(() => {
        hideSuccessModal();
        showNotification('Welcome to Click Set Book! 🎉', 'success');
        // In a real app, this would redirect to the main dashboard
    }, 5000);
}

function hideSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.remove('active');
}

// Page-specific initialization functions
function initializeOnboarding() {
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

    // Make functions global for onclick handlers
    window.nextOnboarding = nextOnboarding;
    window.showOnboardingSlide = showOnboardingSlide;

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
}

function initializeCreateAccount() {
    // Form validation and submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const name = formData.get('name').trim();
            const email = formData.get('email').trim();
            const password = formData.get('password');
            
            if (!name || !email || !password) {
                showNotification('Please fill in all fields', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showNotification('Please enter a valid email', 'error');
                return;
            }
            
            if (password.length < 6) {
                showNotification('Password must be at least 6 characters', 'error');
                return;
            }
            
            // Submit form
            form.submit();
        });
    }
}

function initializeFillProfile() {
    // Profile form handling
    const form = document.querySelector('.profile-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const dateOfBirth = document.querySelector('input[name="date_of_birth"]').value.trim();
            const phone = document.querySelector('input[name="phone"]').value.trim();
            const address = document.querySelector('textarea[name="address"]').value.trim();
            
            if (!dateOfBirth || !phone || !address) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }
            
            // Validate phone number format
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(phone)) {
                showNotification('Please enter a valid phone number', 'error');
                return;
            }
            
            // Submit form
            form.submit();
        });
    }
}

function initializeSignIn() {
    // Sign in form handling - let PHP handle everything
    // No JavaScript interference with form submission
}

function initializeForgetPassword() {
    // Forget password form handling
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.querySelector('input[name="email"]').value.trim();
            
            if (!email) {
                showNotification('Please enter your email', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showNotification('Please enter a valid email', 'error');
                return;
            }
            
            // Submit form
            form.submit();
        });
    }
}

function initializeVerifyCode() {
    // Verification code handling (already implemented in verify-code.php)
    // This function can be extended if needed
}

function initializeCreateNewPassword() {
    // Create new password form handling
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
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
            
            // Submit form
            form.submit();
        });
    }
}

// Authentication Functions
function handleSignIn() {
    const email = document.querySelector('#signin-screen input[type="email"]').value.trim();
    const password = document.querySelector('#signin-screen input[type="password"]').value;
    
    if (!email || !password) {
        showNotification('Please fill in all fields', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('Please enter a valid email', 'error');
        return;
    }
    
    // Simulate sign in
    showNotification('Signing in...', 'info');
    
    // In a real app, this would authenticate and redirect to dashboard
    setTimeout(() => {
        showNotification('Welcome back! 🎉', 'success');
    }, 1500);
}

function sendVerificationCode() {
    const email = document.querySelector('#forget-password-screen input[type="email"]').value.trim();
    
    if (!email) {
        showNotification('Please enter your email', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('Please enter a valid email', 'error');
        return;
    }
    
    // Simulate sending verification code
    showNotification('Verification code sent to your email', 'success');
    
    // Redirect to verify code page
    setTimeout(() => {
        window.location.href = 'verify-code.php?email=' + encodeURIComponent(email);
    }, 1500);
}

function verifyCode() {
    const inputs = document.querySelectorAll('.verification-input');
    let code = '';
    
    inputs.forEach(input => {
        code += input.value;
    });
    
    if (code.length !== 5) {
        showNotification('Please enter the complete 5-digit code', 'error');
        return;
    }
    
    // Simulate verification
    showNotification('Code verified successfully!', 'success');
    
    // Redirect to create new password page
    setTimeout(() => {
        window.location.href = 'create-new-password.php';
    }, 1500);
}

function resetPassword() {
    const password = document.querySelector('#create-new-password-screen input[placeholder="Password"]').value;
    const confirmPassword = document.querySelector('#create-new-password-screen input[placeholder="Confirm Password"]').value;
    
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
    
    // Redirect to sign in page
    setTimeout(() => {
        window.location.href = 'signin.php';
    }, 1500);
}

function resendCode() {
    showNotification('Verification code resent to your email', 'info');
}

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

// Image upload handling (for profile picture)
function handleImageUpload() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const profileImage = document.querySelector('.profile-image');
                profileImage.innerHTML = `<img src="${e.target.result}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            };
            reader.readAsDataURL(file);
        }
    };
    input.click();
}

// Add click handler for edit image button
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for edit image button
    const editImageBtn = document.querySelector('.edit-image-btn');
    if (editImageBtn) {
        editImageBtn.addEventListener('click', handleImageUpload);
    }
    
    // Add event listeners for social login buttons
    const googleBtns = document.querySelectorAll('.btn-social');
    googleBtns.forEach(btn => {
        if (btn.textContent.includes('Google')) {
            btn.addEventListener('click', handleGoogleLogin);
        } else if (btn.textContent.includes('Facebook')) {
            btn.addEventListener('click', handleFacebookLogin);
        }
    });
    
    // Add form validation to create account button
    const createAccountBtn = document.querySelector('#create-account-screen .btn-primary');
    if (createAccountBtn) {
        createAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (validateForm('create-account')) {
                // Redirect to fill profile page
                window.location.href = 'fill-profile.php';
            }
        });
    }
});

// Keyboard navigation support
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowRight' || e.key === ' ') {
        // Next slide or continue
        const activeScreen = document.querySelector('.screen.active');
        if (activeScreen && activeScreen.id === 'onboarding-screen') {
            e.preventDefault();
            nextOnboarding();
        }
    } else if (e.key === 'ArrowLeft') {
        // Previous slide
        const activeScreen = document.querySelector('.screen.active');
        if (activeScreen && activeScreen.id === 'onboarding-screen' && currentOnboardingSlide > 1) {
            e.preventDefault();
            currentOnboardingSlide--;
            showOnboardingSlide(currentOnboardingSlide);
        }
    } else if (e.key === 'Escape') {
        // Skip onboarding
        const activeScreen = document.querySelector('.screen.active');
        if (activeScreen && activeScreen.id === 'onboarding-screen') {
            skipOnboarding();
        }
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
    
    const activeScreen = document.querySelector('.screen.active');
    if (activeScreen && activeScreen.id === 'onboarding-screen') {
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
}

// Accessibility improvements
document.addEventListener('DOMContentLoaded', function() {
    // Add ARIA labels and roles
    const screens = document.querySelectorAll('.screen');
    screens.forEach((screen, index) => {
        screen.setAttribute('role', 'tabpanel');
        screen.setAttribute('aria-hidden', screen.classList.contains('active') ? 'false' : 'true');
    });
    
    // Add focus management
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('focus', function() {
            this.style.outline = '2px solid #1e40af';
            this.style.outlineOffset = '2px';
        });
        
        button.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });
});

// Performance optimization - lazy load images
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

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

// Initialize lazy loading when DOM is ready
document.addEventListener('DOMContentLoaded', lazyLoadImages);
