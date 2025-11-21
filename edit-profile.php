<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Handle form submission
$success = false;
$error = '';
$imageUploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = $_POST['date_of_birth'] ?? null;
    $address = trim($_POST['address'] ?? '');
    $emergencyContact = trim($_POST['emergency_contact'] ?? '');
    $emergencyPhone = trim($_POST['emergency_phone'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, 
                    last_name = ?, 
                    phone = ?, 
                    date_of_birth = ?, 
                    address = ?, 
                    emergency_contact = ?, 
                    emergency_phone = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $firstName,
                $lastName,
                $phone,
                $dateOfBirth ?: null,
                $address,
                $emergencyContact,
                $emergencyPhone,
                $currentUser['id']
            ]);
            
            $success = true;
            // Refresh current user data
            $_SESSION['user'] = getCurrentUser();
            $currentUser = $_SESSION['user'];
            
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Failed to update profile. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
            margin-bottom: 32px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 16px;
            border: 2px dashed var(--primary-color);
        }
        
        .profile-picture-container {
            position: relative;
            margin-bottom: 16px;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .profile-picture-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 700;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .edit-picture-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: 3px solid white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
        }
        
        .edit-picture-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }
        
        .upload-hint {
            font-size: 14px;
            color: var(--text-light);
            text-align: center;
            margin-top: 8px;
        }
        
        .upload-hint strong {
            color: var(--primary-color);
            cursor: pointer;
        }
        
        .upload-hint strong:hover {
            text-decoration: underline;
        }
        
        #profileImageInput {
            display: none;
        }
        
        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: #dc2626;
            margin-left: 2px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            color: var(--text-color);
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-control:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        .form-control::placeholder {
            color: #9ca3af;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }
        
        .form-hint {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
            display: block;
        }
        
        .btn-save {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        
        .btn-save:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-save:active {
            transform: translateY(0);
        }
        
        .emergency-section {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <button onclick="window.history.back()" class="icon-btn">
                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Edit Profile</h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <svg style="width: 20px; height: 20px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Profile updated successfully!
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <svg style="width: 20px; height: 20px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Picture Section -->
        <div class="card">
            <div class="profile-picture-section">
                <div class="profile-picture-container">
                    <?php if (!empty($currentUser['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($currentUser['profile_image']); ?>?v=<?php echo time(); ?>" alt="Profile Picture" class="profile-picture" id="profilePicturePreview">
                    <?php else: ?>
                        <div class="profile-picture-placeholder" id="profilePicturePlaceholder">
                            <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" class="edit-picture-btn" onclick="document.getElementById('profileImageInput').click()" title="Change profile picture">
                        <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </button>
                </div>
                <input type="file" id="profileImageInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="handleImageChange(event)">
                <div class="upload-hint">
                    <strong onclick="document.getElementById('profileImageInput').click()">Click to upload</strong> or drag and drop<br>
                    JPG, PNG, GIF or WebP (max 5MB)
                </div>
                <div id="uploadStatus" style="margin-top: 12px; font-size: 14px; text-align: center;"></div>
            </div>
        </div>

        <!-- Profile Form -->
        <form method="POST" class="card">
            <h2 class="form-section-title">Personal Information</h2>
            
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                <small class="form-hint">Email cannot be changed</small>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($currentUser['date_of_birth'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" class="form-control" placeholder="Enter your full address"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
            </div>

            <div class="emergency-section">
                <h2 class="form-section-title">Emergency Contact</h2>

                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($currentUser['emergency_contact'] ?? ''); ?>" placeholder="Full name">
                </div>

                <div class="form-group">
                    <label for="emergency_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" value="<?php echo htmlspecialchars($currentUser['emergency_phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
                </div>
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>
    
    <script>
        function handleImageChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showUploadStatus('Please select a valid image file (JPG, PNG, GIF, or WebP)', 'error');
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showUploadStatus('Image size must be less than 5MB', 'error');
                return;
            }

            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profilePicturePreview');
                const placeholder = document.getElementById('profilePicturePlaceholder');
                
                if (preview) {
                    preview.src = e.target.result;
                } else if (placeholder) {
                    // Replace placeholder with actual image
                    const container = placeholder.parentElement;
                    placeholder.remove();
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Profile Picture';
                    img.className = 'profile-picture';
                    img.id = 'profilePicturePreview';
                    container.insertBefore(img, container.firstChild);
                }
            };
            reader.readAsDataURL(file);

            // Upload the image
            uploadProfilePicture(file);
        }

        async function uploadProfilePicture(file) {
            const formData = new FormData();
            formData.append('profile_image', file);

            showUploadStatus('Uploading...', 'loading');

            try {
                const response = await fetch('api/upload-profile-picture.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showUploadStatus('Profile picture updated successfully!', 'success');
                    
                    // Update the preview image immediately with the new URL (includes cache-busting timestamp)
                    const preview = document.getElementById('profilePicturePreview');
                    if (preview && result.image_url) {
                        preview.src = result.image_url;
                    }
                    
                    // Reload after 1 second to update the image everywhere and refresh session
                    setTimeout(() => {
                        window.location.href = window.location.href.split('?')[0] + '?updated=' + Date.now();
                    }, 1000);
                } else {
                    showUploadStatus(result.message || 'Failed to upload image', 'error');
                }
            } catch (error) {
                console.error('Upload error:', error);
                showUploadStatus('An error occurred while uploading', 'error');
            }
        }

        function showUploadStatus(message, type) {
            const statusDiv = document.getElementById('uploadStatus');
            statusDiv.textContent = message;
            
            // Reset classes
            statusDiv.className = '';
            
            // Add appropriate styling
            if (type === 'success') {
                statusDiv.style.color = '#059669';
                statusDiv.style.fontWeight = '600';
            } else if (type === 'error') {
                statusDiv.style.color = '#dc2626';
                statusDiv.style.fontWeight = '600';
            } else if (type === 'loading') {
                statusDiv.style.color = '#3b82f6';
                statusDiv.style.fontWeight = '500';
            }
        }

        // Add drag and drop functionality
        const profileSection = document.querySelector('.profile-picture-section');
        
        profileSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            profileSection.style.borderColor = 'var(--primary-dark)';
            profileSection.style.background = 'linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%)';
        });

        profileSection.addEventListener('dragleave', (e) => {
            e.preventDefault();
            profileSection.style.borderColor = 'var(--primary-color)';
            profileSection.style.background = 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)';
        });

        profileSection.addEventListener('drop', (e) => {
            e.preventDefault();
            profileSection.style.borderColor = 'var(--primary-color)';
            profileSection.style.background = 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById('profileImageInput');
                input.files = files;
                handleImageChange({ target: input });
            }
        });
    </script>
</body>
</html>
