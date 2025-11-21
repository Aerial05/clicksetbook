<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 4;
$offset = ($page - 1) * $perPage;

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$countStmt->execute([$currentUser['id']]);
$totalNotifications = $countStmt->fetchColumn();
$totalPages = ceil($totalNotifications / $perPage);

// Get notifications for current page with appointment details
$stmt = $pdo->prepare("
    SELECT 
        n.*,
        a.appointment_date,
        a.appointment_time,
        a.status as appointment_status,
        a.appointment_purpose,
        a.cancel_reason,
        a.cancel_details,
        a.cancel_requested_at,
        s.name as service_name,
        s.category as service_category,
        CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
        d.specialty as doctor_specialty
    FROM notifications n
    LEFT JOIN appointments a ON n.appointment_id = a.id
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$currentUser['id'], $perPage, $offset]);
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

// Count unread (from all notifications, not just current page)
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadStmt->execute([$currentUser['id']]);
$unreadCount = $unreadStmt->fetchColumn();
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
            flex-direction: column;
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
        .notification-header {
            display: flex;
            gap: 12px;
            width: 100%;
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
        .notification-details {
            display: none;
            margin-top: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        .notification-details.show {
            display: block;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
        }
        .detail-value {
            font-size: 13px;
            color: #1f2937;
            font-weight: 500;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-pending { background: #fef3c7; color: #f59e0b; }
        .badge-confirmed { background: #dbeafe; color: #3b82f6; }
        .badge-completed { background: #d1fae5; color: #10b981; }
        .badge-cancelled { background: #fee2e2; color: #ef4444; }
        .badge-archived { background: #e5e7eb; color: #6b7280; }
        .toggle-details {
            font-size: 13px;
            color: var(--primary-color);
            font-weight: 600;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 16px;
        }
        .pagination button {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .pagination button:hover:not(:disabled) {
            background: var(--bg-secondary);
        }
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pagination .page-info {
            padding: 0 12px;
            font-size: 14px;
            color: var(--text-secondary);
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
                        
                        $hasAppointment = !empty($notification['appointment_id']);
                        ?>
                        <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                            <div class="notification-header" onclick="markAsRead(<?php echo $notification['id']; ?>, event)">
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
                                    
                                    <?php if ($hasAppointment): ?>
                                    <div class="toggle-details" onclick="toggleDetails(<?php echo $notification['id']; ?>, event)">
                                        <span>View appointment details</span>
                                        <svg style="width: 14px; height: 14px; transition: transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($hasAppointment): ?>
                            <div class="notification-details" id="details-<?php echo $notification['id']; ?>">
                                <div style="margin-bottom: 12px;">
                                    <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Appointment Details</h4>
                                </div>
                                
                                <?php if ($notification['service_name']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($notification['service_name']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['doctor_name']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Doctor:</span>
                                    <span class="detail-value">Dr. <?php echo htmlspecialchars($notification['doctor_name']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['doctor_specialty']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Specialty:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($notification['doctor_specialty']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['appointment_date']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Date:</span>
                                    <span class="detail-value"><?php echo date('F j, Y', strtotime($notification['appointment_date'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['appointment_time']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Time:</span>
                                    <span class="detail-value"><?php echo date('g:i A', strtotime($notification['appointment_time'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['appointment_status']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value">
                                        <span class="badge badge-<?php echo $notification['appointment_status']; ?>">
                                            <?php echo ucfirst($notification['appointment_status']); ?>
                                        </span>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['appointment_purpose']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Purpose:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($notification['appointment_purpose']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['appointment_status'] === 'cancelled' && $notification['cancel_reason']): ?>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #fee2e2;">
                                    <h5 style="font-size: 13px; font-weight: 700; color: #dc2626; margin-bottom: 8px;">Cancellation Details</h5>
                                    <div class="detail-row">
                                        <span class="detail-label">Reason:</span>
                                        <span class="detail-value" style="color: #dc2626;"><?php echo htmlspecialchars($notification['cancel_reason']); ?></span>
                                    </div>
                                    <?php if ($notification['cancel_details']): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Details:</span>
                                        <span class="detail-value" style="color: #991b1b; font-style: italic;">"<?php echo htmlspecialchars($notification['cancel_details']); ?>"</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($notification['cancel_requested_at']): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Cancelled on:</span>
                                        <span class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($notification['cancel_requested_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <button onclick="location.href='?page=<?php echo max(1, $page - 1); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <button onclick="location.href='?page=<?php echo min($totalPages, $page + 1); ?>'" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
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
    function toggleDetails(id, event) {
        event.stopPropagation();
        const details = document.getElementById('details-' + id);
        const toggle = event.currentTarget;
        const arrow = toggle.querySelector('svg');
        
        details.classList.toggle('show');
        if (details.classList.contains('show')) {
            arrow.style.transform = 'rotate(180deg)';
            toggle.querySelector('span').textContent = 'Hide appointment details';
        } else {
            arrow.style.transform = 'rotate(0deg)';
            toggle.querySelector('span').textContent = 'View appointment details';
        }
    }
    
    async function markAsRead(id, event) {
        if (event) {
            // Check if click is on the toggle button
            if (event.target.closest('.toggle-details')) {
                return;
            }
        }
        
        try {
            const response = await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove unread styling
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    
                    // Remove blue dot
                    const dot = item.querySelector('div[style*="background: var(--primary-color)"]');
                    if (dot) dot.remove();
                }
                
                // Update unread count
                const unreadCountEl = document.querySelector('p');
                if (unreadCountEl && unreadCountEl.textContent.includes('unread')) {
                    const currentCount = parseInt(unreadCountEl.textContent);
                    const newCount = currentCount - 1;
                    if (newCount > 0) {
                        unreadCountEl.textContent = `${newCount} unread notification${newCount > 1 ? 's' : ''}`;
                    } else {
                        unreadCountEl.remove();
                        document.querySelector('button[onclick="markAllAsRead()"]')?.remove();
                    }
                }
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
