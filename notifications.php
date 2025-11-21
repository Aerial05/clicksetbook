<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get all notifications for the user
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$currentUser['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group notifications by date
$grouped = [];
foreach ($notifications as $notification) {
    $date = date('Y-m-d', strtotime($notification['created_at']));
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($date == $today) {
        $dateLabel = 'TODAY';
    } elseif ($date == $yesterday) {
        $dateLabel = 'YESTERDAY';
    } else {
        $dateLabel = strtoupper(date('F j, Y', strtotime($date)));
    }
    
    if (!isset($grouped[$dateLabel])) {
        $grouped[$dateLabel] = [];
    }
    $grouped[$dateLabel][] = $notification;
}

// Count unread
$unreadCount = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .notification-item {
            display: flex;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }
        .notification-item:hover {
            background: var(--bg-secondary);
        }
        .notification-item.unread {
            background: #eff6ff;
        }
        .notification-item.unread:hover {
            background: #dbeafe;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .notification-icon.success {
            background: #d1fae5;
            color: #10b981;
        }
        .notification-icon.danger {
            background: #fee2e2;
            color: #ef4444;
        }
        .notification-icon.info {
            background: #dbeafe;
            color: #3b82f6;
        }
        .notification-icon.warning {
            background: #fef3c7;
            color: #f59e0b;
        }
        .date-divider {
            padding: 16px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-light);
            background: var(--bg-secondary);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            color: var(--text-light);
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700;">Notifications</h1>
                <?php if ($unreadCount > 0): ?>
                <p style="font-size: 14px; color: var(--text-light); margin-top: 4px;"><?php echo $unreadCount; ?> unread notification<?php echo $unreadCount > 1 ? 's' : ''; ?></p>
                <?php endif; ?>
            </div>
            <?php if ($unreadCount > 0): ?>
            <button onclick="markAllAsRead()" style="background: var(--primary-color); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                Mark all as read
            </button>
            <?php endif; ?>
        </div>

        <?php if (count($notifications) > 0): ?>
            <div class="card" style="padding: 0;">
                <?php foreach ($grouped as $dateLabel => $dateNotifications): ?>
                    <div class="date-divider"><?php echo $dateLabel; ?></div>
                    <?php foreach ($dateNotifications as $notification): ?>
                        <?php 
                        // Map template_type to icon type
                        $iconType = match($notification['template_type'] ?? 'info') {
                            'booking_confirmation' => 'success',
                            'reminder' => 'info',
                            'cancellation' => 'danger',
                            'reschedule' => 'warning',
                            'status_update' => 'info',
                            default => 'info'
                        };
                        ?>
                        <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                            <div class="notification-icon <?php echo $iconType; ?>">
                                <?php 
                                $icons = [
                                    'success' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px"><polyline points="20 6 9 17 4 12"></polyline></svg>',
                                    'danger' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
                                    'info' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
                                    'warning' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
                                ];
                                echo $icons[$iconType] ?? $icons['info'];
                                ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                    <h3 style="font-size: 16px; font-weight: 700;"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                    <?php if (!$notification['is_read']): ?>
                                    <div style="width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; margin-top: 6px;"></div>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($notification['message_content'] ?? ''); ?>
                                </p>
                                <span style="font-size: 12px; color: var(--text-light);">
                                    <?php 
                                    $time = strtotime($notification['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . ' minutes ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . ' hours ago';
                                    } else {
                                        echo date('g:i A', $time);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">No Notifications</h3>
                <p style="font-size: 14px; color: var(--text-light);">You're all caught up! Check back later for updates.</p>
            </div>
        <?php endif; ?>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <script>
    async function markAsRead(id) {
        try {
            const response = await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove unread styling
                const item = event.currentTarget;
                item.classList.remove('unread');
                
                // Remove blue dot
                const dot = item.querySelector('div[style*="background: var(--primary-color)"]');
                if (dot) dot.remove();
                
                // Update badge in navigation
                location.reload();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async function markAllAsRead() {
        try {
            const response = await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mark_all: true })
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
            alert('Error marking notifications as read');
        }
    }
    </script>
</body>
</html>
