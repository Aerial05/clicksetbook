<?php 
require_once 'includes/auth.php';

// Require login to access this page
requireLogin();

$error_message = '';
$success_message = '';

// Get current logged-in user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Get full user details from database
$auth = new Auth();
$fullUser = $auth->getUserById($userId);

if (!$fullUser) {
    // User not found, redirect to signin
    session_destroy();
    header('Location: signin.php');
    exit();
}

// Check if profile is already complete
if ($auth->isProfileComplete($userId)) {
    // Profile already complete, redirect to dashboard
    header('Location: dashboard.php');
    exit();
}

// Get user data
$userEmail = $fullUser['email'];
$existingFirstName = $fullUser['first_name'] ?? '';
$existingLastName = $fullUser['last_name'] ?? '';
$existingPhone = $fullUser['phone'] ?? '';
$existingDOB = $fullUser['date_of_birth'] ?? '';
$existingAddress = $fullUser['address'] ?? '';
$existingEmergencyContact = $fullUser['emergency_contact'] ?? '';
$existingEmergencyPhone = $fullUser['emergency_phone'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dateOfBirth = $_POST['date_of_birth'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emergencyContact = trim($_POST['emergency_contact'] ?? '');
    $emergencyPhone = trim($_POST['emergency_phone'] ?? '');
    
    // Basic validation - require first_name, last_name, date_of_birth, phone, and address for profile completion
    if (empty($firstName) || empty($lastName) || empty($dateOfBirth) || empty($phone) || empty($address)) {
        $error_message = 'Please fill in all required fields: First Name, Last Name, Date of Birth, Phone, and Address';
    } else {
        // Update user profile
        $profileData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth,
            'phone' => $phone,
            'address' => $address,
            'emergency_contact' => $emergencyContact,
            'emergency_phone' => $emergencyPhone
        ];
        
        $result = $auth->updateProfile($userId, $profileData);
        
        if ($result['success']) {
            // Clear profile incomplete flag
            unset($_SESSION['profile_incomplete']);
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

include 'header.php'; 
?>

<!-- Fill Profile Screen -->
<div class="profile-container">
    <div class="profile-header">
        <h2 class="profile-title">Complete Your Profile</h2>
        <p style="color: #9ca3af; font-size: 14px; margin-top: 8px;">Please complete your profile to access the dashboard</p>
    </div>
    
    <div class="profile-content">
        <div class="profile-image-container">
            <div class="profile-image">
                <i class="fas fa-user"></i>
            </div>
            <button class="edit-image-btn" onclick="handleImageUpload()">
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="profile-form">
            <h3 style="margin-bottom: 20px; color: #1f2937;">Complete Your Profile</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="input-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">First Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="first_name" placeholder="Enter your first name" class="form-input" value="<?php echo htmlspecialchars($_POST['first_name'] ?? $existingFirstName); ?>" required>
                </div>
                
                <div class="input-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Last Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="last_name" placeholder="Enter your last name" class="form-input" value="<?php echo htmlspecialchars($_POST['last_name'] ?? $existingLastName); ?>" required>
                </div>
            </div>
            
            <div class="input-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars($userEmail); ?>" class="form-input" readonly>
            </div>
            
            <div class="input-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Date of Birth <span style="color: #ef4444;">*</span></label>
                <input type="date" name="date_of_birth" placeholder="Date of Birth" class="form-input" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? $existingDOB); ?>" required>
            </div>
            
            <div class="input-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Phone Number <span style="color: #ef4444;">*</span></label>
                <input type="tel" name="phone" placeholder="Enter your phone number" class="form-input" value="<?php echo htmlspecialchars($_POST['phone'] ?? $existingPhone); ?>" required>
            </div>
            
            <div class="input-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Address <span style="color: #ef4444;">*</span></label>
                <textarea name="address" placeholder="Enter your address" class="form-input" rows="3" style="resize: vertical;" required><?php echo htmlspecialchars($_POST['address'] ?? $existingAddress); ?></textarea>
            </div>
            
            <div class="input-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Emergency Contact (Optional)</label>
                <input type="text" name="emergency_contact" placeholder="Emergency contact name" class="form-input" value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? $existingEmergencyContact); ?>">
            </div>
            
            <div class="input-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Emergency Phone (Optional)</label>
                <input type="tel" name="emergency_phone" placeholder="Emergency contact phone" class="form-input" value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? $existingEmergencyPhone); ?>">
            </div>
            
            <div style="margin-top: 32px;">
                <button type="submit" class="btn-primary btn-full">Complete Profile & Continue</button>
            </div>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h2 class="modal-title">Congratulations!</h2>
        <p class="modal-message">Your account is ready to use. You will be redirected to the Home Page in a few seconds.</p>
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
    </div>
</div>

<script>
// Image upload handling (for profile picture)
let uploadedImageFile = null;

function handleImageUpload() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/jpg,image/png,image/gif,image/webp';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, PNG, GIF, and WebP images are allowed');
                return;
            }
            
            uploadedImageFile = file;
            
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

async function uploadProfilePicture() {
    if (!uploadedImageFile) {
        return true; // No image to upload, continue
    }
    
    const formData = new FormData();
    formData.append('profile_picture', uploadedImageFile);
    
    try {
        const response = await fetch('api/upload-profile-picture.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to upload profile picture');
        }
        
        return true;
    } catch (error) {
        console.error('Profile picture upload error:', error);
        alert('Failed to upload profile picture: ' + error.message);
        return false;
    }
}

// Handle form submission
document.querySelector('.profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const firstName = document.querySelector('input[name="first_name"]').value.trim();
    const lastName = document.querySelector('input[name="last_name"]').value.trim();
    const dateOfBirth = document.querySelector('input[name="date_of_birth"]').value.trim();
    const phone = document.querySelector('input[name="phone"]').value.trim();
    const address = document.querySelector('textarea[name="address"]').value.trim();
    
    if (!firstName || !lastName || !dateOfBirth || !phone || !address) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    // Validate phone number format
    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
    if (!phoneRegex.test(phone)) {
        showNotification('Please enter a valid phone number', 'error');
        return;
    }
    
    // Upload profile picture first if one was selected
    const uploadSuccess = await uploadProfilePicture();
    if (!uploadSuccess) {
        return; // Stop if upload failed
    }
    
    // If validation passes, submit the form
    this.submit();
});

// Success Modal Functions
function showSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.add('active');
    
    // Auto-redirect after 5 seconds
    setTimeout(() => {
        hideSuccessModal();
        showNotification('Welcome to Click Set Book! 🎉', 'success');
        // In a real app, this would redirect to the main dashboard
        window.location.href = 'dashboard.php';
    }, 5000);
}

function hideSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.remove('active');
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
