<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            // Verify current password
            if (password_verify($currentPassword, $currentUser['password_hash'])) {
                try {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newHash, $currentUser['id']]);
                    
                    $success = 'Password changed successfully!';
                } catch (PDOException $e) {
                    error_log("Password change error: " . $e->getMessage());
                    $error = 'Failed to change password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .settings-section {
            margin-bottom: 24px;
        }
        
        .settings-section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }
        
        .setting-item:hover {
            background: #f9fafb;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info {
            flex: 1;
        }
        
        .setting-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        
        .setting-desc {
            font-size: 13px;
            color: var(--text-light);
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
        
        .form-hint {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
            display: block;
        }
        
        .btn-update {
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
        
        .btn-update:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-update:active {
            transform: translateY(0);
        }
        
        .password-form-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 28px;
            background: #d1d5db;
            border-radius: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle-switch.active {
            background: var(--primary-color);
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.active::after {
            transform: translateX(20px);
        }
        
        .danger-zone {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .danger-zone-title {
            font-size: 16px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 8px;
        }
        
        .danger-zone-desc {
            font-size: 14px;
            color: #dc2626;
            margin-bottom: 16px;
        }
        
        .btn-danger {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-danger:hover {
            background: #991b1b;
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
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Settings</h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <svg style="width: 20px; height: 20px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?php echo htmlspecialchars($success); ?>
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

        <!-- Notification Preferences moved to notification-preferences.php -->

        <!-- Security Settings -->
        <div class="settings-section">
            <h2 class="settings-section-title">Security</h2>
            <div class="card">
                <h3 class="password-form-title">Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                        <small class="form-hint">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn-update">Update Password</button>
                </form>
            </div>
        </div>

        <!-- App Preferences -->
        <div class="settings-section">
            <h2 class="settings-section-title">App Preferences</h2>
            <div class="card" style="padding: 0;">
                <a href="notification-preferences.php" class="setting-item" style="text-decoration: none; color: inherit;">
                    <div class="setting-info">
                        <div class="setting-title">Notification Preferences</div>
                        <div class="setting-desc">Manage how you receive notifications</div>
                    </div>
                    <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- About -->
        <div class="settings-section">
            <h2 class="settings-section-title">About</h2>
            <div class="card" style="padding: 0;">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-title">App Version</div>
                        <div class="setting-desc">1.0.0</div>
                    </div>
                </div>
                
                <a href="privacy-policy.php" class="setting-item" style="text-decoration: none; color: inherit;">
                    <div class="setting-info">
                        <div class="setting-title">Privacy Policy</div>
                        <div class="setting-desc">Learn how we protect your data</div>
                    </div>
                    <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
                
                <a href="terms-of-service.php" class="setting-item" style="text-decoration: none; color: inherit;">
                    <div class="setting-info">
                        <div class="setting-title">Terms of Service</div>
                        <div class="setting-desc">Read our terms and conditions</div>
                    </div>
                    <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="danger-zone">
            <div class="danger-zone-title">⚠️ Danger Zone</div>
            <div class="danger-zone-desc">These actions are irreversible. Please be careful.</div>
            <button onclick="confirmDeleteAccount()" class="btn-danger">Delete Account</button>
        </div>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <script>
    function toggleSetting(element, settingName) {
        element.classList.toggle('active');
        const isActive = element.classList.contains('active');
        
        // Save to localStorage (in production, save to backend)
        localStorage.setItem(settingName, isActive ? '1' : '0');
        
        // Show toast notification
        showToast(isActive ? 'Setting enabled' : 'Setting disabled');
    }

    function showToast(message) {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text-color);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    function confirmDeleteAccount() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            if (confirm('This will permanently delete all your data including appointments, medical records, and personal information. Continue?')) {
                // Redirect to delete account endpoint
                alert('Account deletion feature will be implemented by administrator');
            }
        }
    }

    // Load saved settings on page load
    document.addEventListener('DOMContentLoaded', function() {
        const settings = ['email_notifications', 'sms_notifications', 'appointment_reminders', 'promotional_updates'];
        
        settings.forEach(setting => {
            const saved = localStorage.getItem(setting);
            if (saved !== null) {
                const toggle = document.querySelector(`[onclick*="${setting}"]`);
                if (toggle) {
                    if (saved === '1') {
                        toggle.classList.add('active');
                    } else {
                        toggle.classList.remove('active');
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
