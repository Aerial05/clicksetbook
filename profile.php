<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get booking counts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status IN ('pending', 'confirmed')");
$stmt->execute([$currentUser['id']]);
$upcomingCount = $stmt->fetch()['count'];

// Get user initials for avatar
$fullName = trim($currentUser['first_name'] . ' ' . $currentUser['last_name']);
$nameParts = explode(' ', $fullName);
$initials = '';
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
} else {
    $initials = strtoupper(substr($fullName, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border-radius: 20px;
            padding: 32px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }
        
        .profile-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-info {
            flex: 1;
            text-align: left;
        }
        
        .profile-name {
            font-size: 22px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
        }
        
        .profile-email {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }
        .menu-item:hover {
            background: var(--bg-secondary);
        }
        .menu-item .icon {
            width: 40px;
            height: 40px;
            background: var(--bg-secondary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .menu-item.danger .icon {
            background: #fee2e2;
            color: var(--danger-color);
        }
        .menu-item .arrow {
            margin-left: auto;
            color: var(--text-light);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 12px;
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        .stat-card .label {
            font-size: 14px;
            color: var(--text-light);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-content">
                <div class="profile-avatar">
                    <?php if (!empty($currentUser['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($currentUser['profile_image']); ?>?v=<?php echo time(); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?php echo $initials; ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($fullName); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $upcomingCount; ?></div>
                <div class="label">Upcoming</div>
            </div>
        </div>

        <!-- Menu Items -->
        <div class="card" style="padding: 0;">
            <a href="edit-profile.php" class="menu-item" style="text-decoration: none; color: inherit;">
                <div class="icon">
                    <svg style="width: 20px; height: 20px; color: var(--primary-color);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Edit Profile</h3>
                    <p style="font-size: 13px; color: var(--text-light);">Update your personal information</p>
                </div>
                <div class="arrow">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </a>

            <a href="settings.php" class="menu-item" style="text-decoration: none; color: inherit;">
                <div class="icon">
                    <svg style="width: 20px; height: 20px; color: #6b7280;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                        <path d="M17 12h6"></path>
                        <path d="M1 12h6"></path>
                        <path d="M4.22 4.22l4.24 4.24"></path>
                        <path d="M15.54 15.54l4.24 4.24"></path>
                        <path d="M4.22 19.78l4.24-4.24"></path>
                        <path d="M15.54 8.46l4.24-4.24"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Settings</h3>
                    <p style="font-size: 13px; color: var(--text-light);">App settings and preferences</p>
                </div>
                <div class="arrow">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </a>

            <a href="#" class="menu-item" style="text-decoration: none; color: inherit;" onclick="event.preventDefault(); window.open('https://clicksetbook.com/help', '_blank');">
                <div class="icon">
                    <svg style="width: 20px; height: 20px; color: #10b981;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Help & Support</h3>
                    <p style="font-size: 13px; color: var(--text-light);">Get help or contact us</p>
                </div>
                <div class="arrow">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </a>

            <a href="#" class="menu-item" style="text-decoration: none; color: inherit;" onclick="event.preventDefault(); window.open('https://clicksetbook.com/terms', '_blank');">
                <div class="icon">
                    <svg style="width: 20px; height: 20px; color: #6b7280;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Terms & Conditions</h3>
                    <p style="font-size: 13px; color: var(--text-light);">Read our terms and policies</p>
                </div>
                <div class="arrow">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </a>

            <div class="menu-item danger" onclick="showLogoutModal()">
                <div class="icon">
                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 2px; color: var(--danger-color);">Logout</h3>
                    <p style="font-size: 13px; color: var(--text-light);">Sign out of your account</p>
                </div>
                <div class="arrow">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </div>
        </div>

        <!-- App Version -->
        <div style="text-align: center; padding: 20px; color: var(--text-light); font-size: 12px;">
            Version 1.0.0
        </div>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Logout Confirmation Modal -->
    <div class="modal" id="logoutModal">
        <div class="modal-content">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg style="width: 28px; height: 28px; color: var(--danger-color);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </div>
                <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 8px;">Logout</h2>
                <p style="font-size: 14px; color: var(--text-light);">Are you sure you want to logout?</p>
            </div>
            <button onclick="logout()" class="btn btn-danger btn-block">Yes, Logout</button>
            <button onclick="closeLogoutModal()" class="btn btn-secondary btn-block" style="margin-top: 12px;">Cancel</button>
        </div>
    </div>

    <script>
    function showLogoutModal() {
        document.getElementById('logoutModal').classList.add('active');
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.remove('active');
    }

    function logout() {
        window.location.href = 'logout.php';
    }
    </script>
</body>
</html>
