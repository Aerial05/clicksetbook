<!-- Bottom Navigation for Mobile / Sidebar for Desktop -->
<nav class="bottom-nav">
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <!-- Admin Navigation -->
    <a href="admin-dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php' ? 'active' : ''; ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span class="nav-label">Admin</span>
    </a>
    <?php else: ?>
    <!-- User Navigation -->
    <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span class="nav-label">Home</span>
    </a>
    <?php endif; ?>
    
    <a href="notifications.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span class="nav-label">Notifications</span>
        <?php
        // Show notification badge if there are unread notifications
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadCount = $stmt->fetch()['count'];
        if ($unreadCount > 0):
        ?>
        <span class="notification-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
        <?php endif; ?>
    </a>
    
    <a href="my-bookings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'my-bookings.php' ? 'active' : ''; ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <span class="nav-label">My Bookings</span>
    </a>
    
    <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span class="nav-label">Profile</span>
    </a>
</nav>

<style>
/* Bottom Navigation Styles */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-around;
    padding: 8px 0;
    z-index: 1000;
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #9ca3af;
    padding: 8px 16px;
    transition: color 0.3s;
    position: relative;
}

.nav-item.active {
    color: #1e3a8a;
}

.nav-icon {
    width: 24px;
    height: 24px;
    margin-bottom: 4px;
}

.nav-label {
    font-size: 12px;
    font-weight: 500;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 12px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

/* Desktop Sidebar Styles */
@media (min-width: 768px) {
    .bottom-nav {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 240px;
        flex-direction: column;
        justify-content: flex-start;
        padding: 20px 0;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .nav-item {
        flex-direction: row;
        justify-content: flex-start;
        padding: 16px 24px;
        margin: 4px 12px;
        border-radius: 8px;
    }
    
    .nav-item:hover {
        background: #f3f4f6;
    }
    
    .nav-item.active {
        background: #e0e7ff;
        color: #1e3a8a;
    }
    
    .nav-icon {
        margin-bottom: 0;
        margin-right: 12px;
    }
    
    .nav-label {
        font-size: 16px;
    }
    
    .notification-badge {
        right: 24px;
        top: 12px;
    }
}
</style>
