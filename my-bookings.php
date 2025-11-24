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
    // Show pending or confirmed appointments regardless of date
    return ($b['status'] == 'pending' || $b['status'] == 'confirmed');
});
$completed = array_filter($bookings, function($b) {
    return $b['status'] == 'completed';
});
$cancelled = array_filter($bookings, function($b) {
    // Only show appointments that are actually cancelled
    return $b['status'] == 'cancelled';
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
                            <button class="secondary" onclick="rescheduleBooking(<?php echo $booking['id']; ?>)" style="background: var(--bg-secondary); color: var(--primary-color); border: 1px solid var(--primary-color);">
                                <svg style="width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="1 4 1 10 7 10"></polyline>
                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                </svg>
                                Reschedule
                            </button>
                            <button class="danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">Cancel</button>
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

    let currentCancelId = null;
    let currentRescheduleId = null;
    let rescheduleDate = new Date();
    let selectedRescheduleDate = null;
    let selectedRescheduleTime = null;

    function rescheduleBooking(id) {
        currentRescheduleId = id;
        document.getElementById('rescheduleModal').style.display = 'flex';
        renderRescheduleCalendar();
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').style.display = 'none';
        currentRescheduleId = null;
        selectedRescheduleDate = null;
        selectedRescheduleTime = null;
    }

    function renderRescheduleCalendar() {
        const grid = document.getElementById('rescheduleCalendarGrid');
        const monthDisplay = document.getElementById('rescheduleCurrentMonth');
        const year = rescheduleDate.getFullYear();
        const month = rescheduleDate.getMonth();
        monthDisplay.textContent = rescheduleDate.toLocaleDateString('en-US', {month: 'long', year: 'numeric'});

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 6);

        grid.innerHTML = '';
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(name => {
            const h = document.createElement('div');
            h.style.cssText = 'font-size:10px;font-weight:700;color:var(--text-light);text-align:center;padding:8px 0;';
            h.textContent = name;
            grid.appendChild(h);
        });

        for (let i = 0; i < firstDay; i++) {
            grid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateObj = new Date(year, month, day);
            const isPast = dateObj < today;
            const isFuture = dateObj > maxDate;
            const isDisabled = isPast || isFuture;
            const isSelected = selectedRescheduleDate && selectedRescheduleDate.getTime() === dateObj.getTime();

            const cell = document.createElement('div');
            cell.textContent = day;
            cell.style.cssText = `
                padding: 12px;
                text-align: center;
                border-radius: 8px;
                cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
                font-weight: 600;
                font-size: 14px;
                background: ${isSelected ? 'var(--primary-color)' : 'transparent'};
                color: ${isSelected ? 'white' : (isDisabled ? 'var(--text-light)' : 'var(--text-primary)')};
                opacity: ${isDisabled ? '0.3' : '1'};
                transition: all 0.2s;
            `;

            if (!isDisabled) {
                cell.onmouseenter = () => {
                    if (!isSelected) {
                        cell.style.background = 'var(--bg-secondary)';
                    }
                };
                cell.onmouseleave = () => {
                    if (!isSelected) {
                        cell.style.background = 'transparent';
                    }
                };
                cell.onclick = () => {
                    selectedRescheduleDate = dateObj;
                    selectedRescheduleTime = null;
                    renderRescheduleCalendar();
                    loadRescheduleTimeSlots();
                };
            }

            grid.appendChild(cell);
        }
    }

    function loadRescheduleTimeSlots() {
        const grid = document.getElementById('rescheduleTimeSlotsGrid');
        const timeSlots = [
            '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
            '02:00 PM', '02:30 PM', '03:00 PM', '03:30 PM', '04:00 PM', '04:30 PM', '05:00 PM', '05:30 PM'
        ];

        grid.innerHTML = '';
        
        const now = new Date();
        const isToday = selectedRescheduleDate && selectedRescheduleDate.toDateString() === now.toDateString();
        
        timeSlots.forEach(time => {
            const slot = document.createElement('div');
            const isSelected = selectedRescheduleTime === time;
            
            // Check if time slot is in the past (for today only)
            let isPastTime = false;
            if (isToday) {
                // Convert time string to 24-hour format for comparison
                const timeParts = time.match(/(\d+):(\d+) (AM|PM)/);
                if (timeParts) {
                    let hours = parseInt(timeParts[1]);
                    const minutes = parseInt(timeParts[2]);
                    const period = timeParts[3];
                    
                    if (period === 'PM' && hours !== 12) hours += 12;
                    if (period === 'AM' && hours === 12) hours = 0;
                    
                    const slotDateTime = new Date(selectedRescheduleDate);
                    slotDateTime.setHours(hours, minutes, 0, 0);
                    isPastTime = slotDateTime <= now;
                }
            }
            
            const isDisabled = isPastTime;
            
            slot.textContent = time;
            slot.style.cssText = `
                padding: 12px;
                text-align: center;
                border: 2px solid ${isSelected ? 'var(--primary-color)' : 'var(--border-color)'};
                border-radius: 8px;
                cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
                font-weight: 600;
                font-size: 14px;
                background: ${isSelected ? 'var(--primary-color)' : (isDisabled ? 'var(--bg-tertiary)' : 'white')};
                color: ${isSelected ? 'white' : 'var(--text-primary)'};
                opacity: ${isDisabled ? '0.4' : '1'};
                transition: all 0.2s;
            `;

            if (!isDisabled) {
                slot.onmouseenter = () => {
                    if (!isSelected) {
                        slot.style.borderColor = 'var(--primary-color)';
                        slot.style.background = 'var(--bg-secondary)';
                    }
                };
                slot.onmouseleave = () => {
                    if (!isSelected) {
                        slot.style.borderColor = 'var(--border-color)';
                        slot.style.background = 'white';
                    }
                };
                slot.onclick = () => {
                    selectedRescheduleTime = time;
                    loadRescheduleTimeSlots();
                    updateRescheduleButton();
                };
            }

            grid.appendChild(slot);
        });
    }

    function updateRescheduleButton() {
        const btn = document.getElementById('confirmRescheduleBtn');
        if (selectedRescheduleDate && selectedRescheduleTime) {
            btn.disabled = false;
            btn.style.opacity = '1';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        }
    }

    function previousRescheduleMonth() {
        rescheduleDate.setMonth(rescheduleDate.getMonth() - 1);
        renderRescheduleCalendar();
    }

    function nextRescheduleMonth() {
        rescheduleDate.setMonth(rescheduleDate.getMonth() + 1);
        renderRescheduleCalendar();
    }

    function confirmReschedule() {
        if (selectedRescheduleDate && selectedRescheduleTime) {
            const dateStr = selectedRescheduleDate.toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
            const reason = document.getElementById('rescheduleReason').value.trim();
            let message = `Reschedule confirmed!\nAppointment ID: ${currentRescheduleId}\nNew Date: ${dateStr}\nNew Time: ${selectedRescheduleTime}`;
            if (reason) {
                message += `\nReason: ${reason}`;
            }
            message += '\n\n(Database integration pending)';
            alert(message);
            closeRescheduleModal();
        }
    }

    function cancelBooking(id) {
        currentCancelId = id;
        document.getElementById('cancelModal').style.display = 'flex';
        document.getElementById('cancelReason').value = '';
        document.getElementById('cancelDetails').value = '';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
        currentCancelId = null;
    }

    async function submitCancellation() {
        const reason = document.getElementById('cancelReason').value;
        const details = document.getElementById('cancelDetails').value;
        
        if (!reason) {
            alert('Please select a cancellation reason');
            return;
        }
        
        const submitBtn = document.getElementById('submitCancelBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Cancelling...';
        
        try {
            const response = await fetch('api/manage-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'cancel', 
                    id: currentCancelId,
                    cancel_reason: reason,
                    cancel_details: details
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeCancelModal();
                showSuccessModal();
            } else {
                alert('Error cancelling appointment: ' + (data.message || 'Unknown error'));
                submitBtn.disabled = false;
                submitBtn.textContent = 'Cancel Appointment';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error cancelling appointment');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Cancel Appointment';
        }
    }
    
    function showSuccessModal() {
        document.getElementById('successModal').style.display = 'flex';
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
    
    function closeSuccessModal() {
        document.getElementById('successModal').style.display = 'none';
        location.reload();
    }
    </script>
    
    <!-- Reschedule Modal -->
    <div id="rescheduleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
        <div style="background: white; border-radius: 16px; padding: 24px; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; margin: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 20px; font-weight: 700; margin: 0;">Reschedule Appointment</h3>
                <button onclick="closeRescheduleModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-light);">&times;</button>
            </div>
            
            <!-- Calendar and Time Slots -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                
                <!-- Time Slots -->
                <div>
                    <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Available Time Slots</h4>
                    <div id="rescheduleTimeSlotsGrid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 400px; overflow-y: auto;">
                        <div style="grid-column: 1/-1; text-align: center; padding: 40px 20px; color: var(--text-light); font-size: 14px;">
                            Select a date to view available time slots
                        </div>
                    </div>
                </div>

                <!-- Calendar -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <button onclick="previousRescheduleMonth()" style="background: var(--bg-tertiary); border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <span id="rescheduleCurrentMonth" style="font-weight: 700; font-size: 15px;"></span>
                        <button onclick="nextRescheduleMonth()" style="background: var(--bg-tertiary); border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                    <div id="rescheduleCalendarGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;"></div>
                </div>
            </div>
            
            <!-- Reschedule Reason (Optional) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">
                    Reason for Rescheduling <span style="color: var(--text-light); font-weight: 400;">(Optional)</span>
                </label>
                <textarea id="rescheduleReason" placeholder="e.g., Schedule conflict, personal emergency, etc." style="width: 100%; padding: 12px; font-size: 14px; border: 1px solid var(--border-color); border-radius: 8px; resize: vertical; min-height: 70px; font-family: inherit;"></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px;">
                <button onclick="closeRescheduleModal()" style="flex: 1; padding: 12px; background: var(--bg-tertiary); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button id="confirmRescheduleBtn" onclick="confirmReschedule()" disabled style="flex: 1; padding: 12px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; opacity: 0.5;">Confirm Reschedule</button>
            </div>
        </div>
    </div>
    
    <!-- Cancellation Modal -->
    <div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 24px; max-width: 400px; width: 90%; margin: 20px;">
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 16px;">Cancel Appointment</h3>
            <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px;">Please select a reason for cancelling this appointment:</p>
            
            <select id="cancelReason" style="width: 100%; padding: 12px; font-size: 14px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 16px;">
                <option value="">Select a reason...</option>
                <option value="Schedule Conflict">Schedule Conflict</option>
                <option value="Personal Emergency">Personal Emergency</option>
                <option value="Feeling Better">Feeling Better</option>
                <option value="Financial Reasons">Financial Reasons</option>
                <option value="Found Another Provider">Found Another Provider</option>
                <option value="Transportation Issues">Transportation Issues</option>
                <option value="Weather Conditions">Weather Conditions</option>
                <option value="Other">Other</option>
            </select>
            
            <textarea id="cancelDetails" placeholder="Additional details (optional)" style="width: 100%; padding: 12px; font-size: 14px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 16px; resize: vertical; min-height: 80px;"></textarea>
            
            <div style="display: flex; gap: 12px;">
                <button onclick="closeCancelModal()" style="flex: 1; padding: 12px; background: var(--bg-tertiary); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Go Back</button>
                <button onclick="submitCancellation()" id="submitCancelBtn" style="flex: 1; padding: 12px; background: var(--danger-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel Appointment</button>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 32px; max-width: 400px; width: 90%; margin: 20px; text-align: center;">
            <div style="width: 64px; height: 64px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg style="width: 32px; height: 32px; color: #16a34a;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #16a34a;">Appointment Cancelled</h3>
            <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">Your appointment has been successfully cancelled. You will be redirected shortly.</p>
            <button onclick="closeSuccessModal()" style="padding: 12px 24px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">
                OK
            </button>
        </div>
    </div>
</body>
</html>
