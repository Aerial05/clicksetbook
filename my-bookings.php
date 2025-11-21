<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get all bookings for the user
$stmt = $pdo->prepare("
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.appointment_purpose,
        a.referrer,
        CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
        d.specialty as doctor_specialty,
        s.name as service_name,
        s.category as service_category,
        CASE 
            WHEN a.doctor_id IS NOT NULL THEN 'doctor'
            WHEN a.service_id IS NOT NULL THEN 'service'
        END as appointment_type
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$currentUser['id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate bookings by status
$upcoming = array_filter($bookings, function($b) {
    $appointmentDateTime = strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
    return ($b['status'] == 'pending' || $b['status'] == 'confirmed') && $appointmentDateTime >= time();
});
$completed = array_filter($bookings, function($b) {
    return $b['status'] == 'completed';
});
$cancelled = array_filter($bookings, function($b) {
    $appointmentDateTime = strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
    return $b['status'] == 'cancelled' || ($appointmentDateTime < time() && ($b['status'] == 'pending' || $b['status'] == 'confirmed'));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .booking-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            background: white;
        }
        .booking-card .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .booking-card .info {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .booking-card .icon {
            width: 48px;
            height: 48px;
            background: var(--bg-secondary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .booking-card .actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .booking-card .actions button {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .booking-card .actions button:hover {
            background: var(--bg-secondary);
        }
        .booking-card .actions button.primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .booking-card .actions button.danger {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }
        .booking-card .actions button.danger:hover {
            background: #fee2e2;
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
        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 24px; font-weight: 700;">My Bookings</h1>
            <p style="font-size: 14px; color: var(--text-light); margin-top: 4px;">View and manage your appointments</p>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab(event, 'upcoming')">Upcoming</button>
            <button class="tab-btn" onclick="switchTab(event, 'completed')">Completed</button>
            <button class="tab-btn" onclick="switchTab(event, 'cancelled')">Cancelled</button>
        </div>

        <!-- Upcoming Tab -->
        <div class="tab-content active" id="upcoming-tab">
            <?php if (count($upcoming) > 0): ?>
                <?php foreach ($upcoming as $booking): ?>
                    <div class="booking-card">
                        <div class="header">
                            <div class="info">
                                <div class="icon">
                                    <?php echo $booking['appointment_type'] == 'doctor' ? '👨‍⚕️' : '🔬'; ?>
                                </div>
                                <div>
                                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($booking['doctor_name'] ?? $booking['service_name']); ?>
                                    </h3>
                                    <p style="font-size: 14px; color: var(--text-light);">
                                        <?php echo htmlspecialchars($booking['doctor_specialty'] ?? ucfirst($booking['service_category'])); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="badge badge-primary">Scheduled</span>
                        </div>
                        <div style="display: flex; gap: 16px; padding: 12px; background: var(--bg-secondary); border-radius: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span style="font-size: 14px; font-weight: 600;"><?php echo date('M j, Y', strtotime($booking['appointment_date'])); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span style="font-size: 14px; font-weight: 600;"><?php echo date('g:i A', strtotime($booking['appointment_time'])); ?></span>
                            </div>
                        </div>
                        <?php if ($booking['appointment_purpose']): ?>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 12px;">
                            <strong>Purpose:</strong> <?php echo htmlspecialchars($booking['appointment_purpose']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($booking['referrer']): ?>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 12px;">
                            <strong>Referrer:</strong> <?php echo htmlspecialchars($booking['referrer']); ?>
                        </p>
                        <?php endif; ?>
                        <div class="actions">
                            <button class="danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">Cancel</button>
                            <button onclick="rescheduleBooking(<?php echo $booking['id']; ?>)">Reschedule</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">No Upcoming Bookings</h3>
                    <p style="font-size: 14px; color: var(--text-light); margin-bottom: 24px;">You don't have any upcoming appointments</p>
                    <a href="dashboard.php" class="btn btn-primary">Book an Appointment</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed Tab -->
        <div class="tab-content" id="completed-tab">
            <?php if (count($completed) > 0): ?>
                <?php foreach ($completed as $booking): ?>
                    <div class="booking-card">
                        <div class="header">
                            <div class="info">
                                <div class="icon">
                                    <?php echo $booking['appointment_type'] == 'doctor' ? '👨‍⚕️' : '🔬'; ?>
                                </div>
                                <div>
                                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($booking['doctor_name'] ?? $booking['service_name']); ?>
                                    </h3>
                                    <p style="font-size: 14px; color: var(--text-light);">
                                        <?php echo htmlspecialchars($booking['doctor_specialty'] ?? ucfirst($booking['service_category'])); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="badge badge-success">Completed</span>
                        </div>
                        <div style="display: flex; gap: 16px; padding: 12px; background: var(--bg-secondary); border-radius: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span style="font-size: 14px; font-weight: 600;"><?php echo date('M j, Y', strtotime($booking['appointment_date'])); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span style="font-size: 14px; font-weight: 600;"><?php echo date('g:i A', strtotime($booking['appointment_time'])); ?></span>
                            </div>
                        </div>
                        <div class="actions">
                            <?php if ($booking['appointment_type'] == 'doctor'): ?>
                            <button onclick="addReview(<?php echo $booking['id']; ?>)">Add Review</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">No Completed Bookings</h3>
                    <p style="font-size: 14px; color: var(--text-light);">Your completed appointments will appear here</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cancelled Tab -->
        <div class="tab-content" id="cancelled-tab">
            <?php if (count($cancelled) > 0): ?>
                <?php foreach ($cancelled as $booking): ?>
                    <div class="booking-card">
                        <div class="header">
                            <div class="info">
                                <div class="icon">
                                    <?php echo $booking['appointment_type'] == 'doctor' ? '👨‍⚕️' : '🔬'; ?>
                                </div>
                                <div>
                                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($booking['doctor_name'] ?? $booking['service_name']); ?>
                                    </h3>
                                    <p style="font-size: 14px; color: var(--text-light);">
                                        <?php echo htmlspecialchars($booking['doctor_specialty'] ?? ucfirst($booking['service_category'])); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="badge badge-danger">Cancelled</span>
                        </div>
                        <div style="display: flex; gap: 16px; padding: 12px; background: var(--bg-secondary); border-radius: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span style="font-size: 14px; font-weight: 600;"><?php echo date('M j, Y', strtotime($booking['appointment_date'])); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: var(--text-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span style="font-size: 14px; font-weight: 600;"><?php echo date('g:i A', strtotime($booking['appointment_time'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">No Cancelled Bookings</h3>
                    <p style="font-size: 14px; color: var(--text-light);">Your cancelled appointments will appear here</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <script>
    function switchTab(event, tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById(tab + '-tab').classList.add('active');
    }

    async function cancelBooking(id) {
        if (!confirm('Are you sure you want to cancel this appointment?')) return;
        
        try {
            const response = await fetch('api/manage-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'cancel', id: id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Error cancelling appointment: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error cancelling appointment');
        }
    }

    function rescheduleBooking(id) {
        // TODO: Implement reschedule functionality
        alert('Reschedule functionality coming soon!');
    }

    function addReview(id) {
        // TODO: Implement review functionality
        alert('Review functionality coming soon!');
    }
    </script>
</body>
</html>
