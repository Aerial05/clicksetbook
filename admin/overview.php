<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$pdo = getDBConnection();

// Get statistics
$stats = [
    'total_users' => 0,
    'total_doctors' => 0,
    'total_services' => 0,
    'pending_appointments' => 0,
    'today_appointments' => 0,
    'total_appointments' => 0
];

// Get total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$stats['total_users'] = $stmt->fetch()['count'];

// Get total doctors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM doctors");
$stats['total_doctors'] = $stmt->fetch()['count'];

// Get total services
$stmt = $pdo->query("SELECT COUNT(*) as count FROM services");
$stats['total_services'] = $stmt->fetch()['count'];

// Get pending appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$stats['pending_appointments'] = $stmt->fetch()['count'];

// Get today's appointments
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()");
$stmt->execute();
$stats['today_appointments'] = $stmt->fetch()['count'];

// Get total appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments");
$stats['total_appointments'] = $stmt->fetch()['count'];

$currentPage = 'overview';
require_once 'header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon blue">👥</div>
        <div class="stat-card-value"><?php echo $stats['total_users']; ?></div>
        <div class="stat-card-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green">👨‍⚕️</div>
        <div class="stat-card-value"><?php echo $stats['total_doctors']; ?></div>
        <div class="stat-card-label">Doctors</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon purple">🔬</div>
        <div class="stat-card-value"><?php echo $stats['total_services']; ?></div>
        <div class="stat-card-label">Services</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange">⏳</div>
        <div class="stat-card-value"><?php echo $stats['pending_appointments']; ?></div>
        <div class="stat-card-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue">📅</div>
        <div class="stat-card-value"><?php echo $stats['today_appointments']; ?></div>
        <div class="stat-card-label">Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green">✅</div>
        <div class="stat-card-value"><?php echo $stats['total_appointments']; ?></div>
        <div class="stat-card-label">Total Bookings</div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="section-header">
        <h2 class="section-title">Recent Appointments</h2>
        <a href="appointments.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div id="recent-appointments">
        <div class="loading-state">
            <div class="loading-spinner"></div>
            <p>Loading appointments...</p>
        </div>
    </div>
</div>

<script>
// Load recent appointments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecentAppointments();
});

function loadRecentAppointments() {
    fetch('../api/admin/appointments.php?action=getAll&limit=5')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderAppointments(data.appointments, 'recent-appointments', true);
            }
        })
        .catch(err => console.error('Error loading appointments:', err));
}

function renderAppointments(appointments, containerId, isCompact) {
    const container = document.getElementById(containerId);
    
    if (appointments.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>No recent appointments</p></div>';
        return;
    }
    
    container.innerHTML = appointments.map(apt => `
        <div class="card" style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">${apt.patient_name}</div>
                    <div style="font-size: 14px; color: var(--text-light); margin-bottom: 4px;">
                        ${apt.service_name ? '🔬 ' + apt.service_name : (apt.doctor_name ? '👨‍⚕️ Dr. ' + apt.doctor_name : 'N/A')}
                    </div>
                    <div style="font-size: 13px; color: var(--text-light);">
                        📅 ${formatDate(apt.appointment_date)} at ${apt.appointment_time}
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="badge badge-${getStatusColor(apt.status)}">${apt.status}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function getStatusColor(status) {
    const colors = {
        'pending': 'orange',
        'confirmed': 'blue',
        'completed': 'green',
        'cancelled': 'gray',
        'archived': 'secondary'
    };
    return colors[status] || 'gray';
}
</script>

<?php require_once 'footer.php'; ?>
