<?php 
require_once __DIR__ . '/../includes/auth.php';

// Require login and admin role
requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Determine current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($currentPage); ?> - Admin Dashboard</title>
    <link rel="stylesheet" href="../app-styles.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- Confirm Dialog -->
    <div class="confirm-overlay">
        <div class="confirm-dialog">
            <div class="confirm-icon"></div>
            <h3 class="confirm-title"></h3>
            <p class="confirm-message"></p>
            <div class="confirm-buttons">
                <button class="confirm-btn-cancel">Cancel</button>
                <button class="confirm-btn-ok">OK</button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <h2>📋 Click Set Book</h2>
        </div>
        <nav class="admin-nav">
            <a href="overview.php" class="admin-nav-item <?php echo $currentPage == 'overview' ? 'active' : ''; ?>">
                <span>📊</span>
                <span>Overview</span>
            </a>
            <a href="appointments.php" class="admin-nav-item <?php echo $currentPage == 'appointments' ? 'active' : ''; ?>">
                <span>📅</span>
                <span>Appointments</span>
            </a>
            <a href="users.php" class="admin-nav-item <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                <span>👥</span>
                <span>Users</span>
            </a>
            <a href="doctors.php" class="admin-nav-item <?php echo $currentPage == 'doctors' ? 'active' : ''; ?>">
                <span>👨‍⚕️</span>
                <span>Doctors</span>
            </a>
            <a href="services.php" class="admin-nav-item <?php echo $currentPage == 'services' ? 'active' : ''; ?>">
                <span>🔬</span>
                <span>Services</span>
            </a>
            <a href="settings.php" class="admin-nav-item <?php echo $currentPage == 'settings' ? 'active' : ''; ?>">
                <span>⚙️</span>
                <span>Settings</span>
            </a>
            <a href="logs.php" class="admin-nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>">
                <span>📜</span>
                <span>History & Logs</span>
            </a>
        </nav>
    </aside>

    <div class="admin-container">
        <!-- Top Navigation Bar -->
        <div class="admin-topnav" id="adminTopnav">
            <div class="topnav-left">
                <button class="hamburger-menu" id="hamburgerMenu">
                    <span>☰</span>
                </button>
                <div class="topnav-title">
                    <h1><?php echo ucfirst($currentPage); ?></h1>
                    <p>Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</p>
                </div>
            </div>
            <div class="topnav-right">
                <div class="user-menu">
                    <button class="user-menu-button" id="userMenuButton">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['first_name'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                        <span>▾</span>
                    </button>
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <a href="../profile.php">
                            <span>👤</span>
                            <span>My Profile</span>
                        </a>
                        <a href="settings.php">
                            <span>⚙️</span>
                            <span>Settings</span>
                        </a>
                        <button onclick="confirmLogout()">
                            <span>🚪</span>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Content -->
        <div class="admin-content">
