<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    ini_set('session.save_path', $sessionPath);
    session_start();
}
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$pdo = getDBConnection();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? '';
$bookingId = $input['id'] ?? 0;
$cancelReason = $input['cancel_reason'] ?? null;
$cancelDetails = $input['cancel_details'] ?? null;

// Reschedule fields
$requestedDate = $input['requested_date'] ?? null;
$requestedTime = $input['requested_time'] ?? null;
$rescheduleReason = $input['reschedule_reason'] ?? null;

// Validate inputs
if (!$bookingId || !in_array($action, ['cancel', 'complete', 'reschedule'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Verify booking belongs to user
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND patient_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    if ($action == 'cancel') {
        // Get full appointment details for notification
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                s.name as service_name,
                s.category as service_category,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), CONCAT('Dr. ', d.specialty)) as doctor_name,
                d.specialty as doctor_specialty
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$bookingId]);
        $appointmentDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update status to cancelled with reason
        $stmt = $pdo->prepare("UPDATE appointments SET 
                               status = 'cancelled', 
                               cancelled_at = NOW(), 
                               cancelled_by = ?, 
                               cancel_request = 1,
                               cancel_reason = ?,
                               cancel_details = ?,
                               cancel_requested_at = NOW(),
                               updated_at = NOW() 
                               WHERE id = ?");
        $stmt->execute([$userId, $cancelReason, $cancelDetails, $bookingId]);
        
        // Format appointment details for notification
        $appointmentDate = date('F j, Y', strtotime($appointmentDetails['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($appointmentDetails['appointment_time']));
        $appointmentFor = $appointmentDetails['service_name'] 
            ? $appointmentDetails['service_name'] . ' (' . ucfirst($appointmentDetails['service_category']) . ')'
            : 'Dr. ' . $appointmentDetails['doctor_name'] . ' - ' . $appointmentDetails['doctor_specialty'];
        
        // Build simple notification message - only summary, details will be in dropdown
        $notificationMessage = "Your appointment has been cancelled. Appointment Details: " . $appointmentFor . " Date: " . $appointmentDate . " Time: " . $appointmentTime . " Reason: " . $cancelReason;
        
        if ($cancelDetails) {
            $notificationMessage .= " Cancelled on: " . date('F j, Y g:i A');
        }
        
        $notificationMessage .= " If you wish to reschedule, please book a new appointment.";
        
        $notificationTitle = "Appointment Cancelled - " . $appointmentDate;
        
        // Create detailed notification with appointment_id
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, appointment_id, title, message_content, template_type, notification_type, is_read, created_at) 
                               VALUES (?, ?, ?, ?, 'cancellation', 'email', 0, NOW())");
        $stmt->execute([$userId, $bookingId, $notificationTitle, $notificationMessage]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment cancelled successfully'
        ]);
    } elseif ($action == 'complete') {
        // Update status to completed
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$bookingId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment marked as completed'
        ]);
    } elseif ($action == 'reschedule') {
        // Validate reschedule data
        if (!$requestedDate || !$requestedTime) {
            echo json_encode(['success' => false, 'message' => 'Date and time are required']);
            exit();
        }
        
        // Validate date format and ensure it's in the future
        $requestedDateTime = strtotime($requestedDate . ' ' . $requestedTime);
        if (!$requestedDateTime || $requestedDateTime < time()) {
            echo json_encode(['success' => false, 'message' => 'Invalid date/time or date is in the past']);
            exit();
        }
        
        // Check if there's already a pending reschedule request
        if ($booking['reschedule_request'] == 1 && $booking['reschedule_status'] == 'pending') {
            echo json_encode(['success' => false, 'message' => 'You already have a pending reschedule request for this appointment']);
            exit();
        }
        
        // Get full appointment details
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                s.name as service_name,
                s.category as service_category,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), CONCAT('Dr. ', d.specialty)) as doctor_name,
                d.specialty as doctor_specialty
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$bookingId]);
        $appointmentDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update appointment with reschedule request
        $stmt = $pdo->prepare("
            UPDATE appointments SET 
                reschedule_request = 1,
                requested_date = ?,
                requested_time = ?,
                reschedule_reason = ?,
                reschedule_requested_at = NOW(),
                reschedule_status = 'pending',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$requestedDate, $requestedTime, $rescheduleReason, $bookingId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reschedule request submitted successfully. Waiting for admin approval.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Manage booking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
