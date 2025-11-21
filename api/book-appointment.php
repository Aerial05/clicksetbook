<?php
// Configure session BEFORE starting
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    ini_set('session.save_path', $sessionPath);
    session_start();
}

require_once '../config/database.php';
require_once '../config/emailjs.php';

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

$type = $input['type'] ?? '';
$itemId = $input['item_id'] ?? 0;
$date = $input['date'] ?? '';
$time = $input['time'] ?? '';
$purpose = $input['purpose'] ?? null;

// Validate inputs
if (!in_array($type, ['doctor', 'service']) || !$itemId || !$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Get user details for patient fields
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $patientName = $user['first_name'] . ' ' . $user['last_name'];
    $patientEmail = $user['email'];
    $patientPhone = $user['phone'] ?? '';
    
    // Calculate end time (assume 30 minutes if not specified)
    $endTime = date('H:i:s', strtotime($time) + 1800);
    
    // Insert appointment
    if ($type == 'doctor') {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, end_time, 
                               patient_name, patient_email, patient_phone, appointment_purpose, status, created_at) 
                               VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$userId, $itemId, $date, $time, $endTime, $patientName, $patientEmail, $patientPhone, $purpose]);
    } else {
        // Service appointments also use appointment_purpose field
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, service_id, appointment_date, appointment_time, end_time, 
                               patient_name, patient_email, patient_phone, appointment_purpose, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$userId, $itemId, $date, $time, $endTime, $patientName, $patientEmail, $patientPhone, $purpose]);
    }
    
    $appointmentId = $pdo->lastInsertId();
    
    // Create notification
    $notificationTitle = $type == 'doctor' ? 'Doctor Appointment Booked' : 'Service Appointment Booked';
    if ($type == 'service') {
        $notificationMessage = "Your appointment has been scheduled for " . date('F j, Y \a\t g:i A', strtotime($date . ' ' . $time)) . ". Your appointment is pending admin approval.";
    } else {
        $notificationMessage = "Your appointment has been scheduled for " . date('F j, Y \a\t g:i A', strtotime($date . ' ' . $time));
    }
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, appointment_id, recipient_email, recipient_phone, 
                           notification_type, template_type, subject, message_content, status, is_read, created_at) 
                           VALUES (?, ?, ?, ?, ?, 'email', 'booking_confirmation', ?, ?, 'pending', 0, NOW())");
    $stmt->execute([$userId, $notificationTitle, $appointmentId, $patientEmail, $patientPhone, $notificationTitle, $notificationMessage]);
    
    // Get appointment details for email
    $appointmentDetails = [
        'type' => $type == 'doctor' ? 'Doctor Consultation' : 'Service Appointment',
        'date' => date('F j, Y', strtotime($date)),
        'time' => date('g:i A', strtotime($time)),
        'location' => 'Click Set Book',
        'notes' => $purpose
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully',
        'appointment_id' => $appointmentId,
        'send_email' => true,  // Signal to frontend to send email
        'email_data' => [
            'to_email' => $patientEmail,
            'to_name' => $patientName,
            'appointment' => $appointmentDetails
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Booking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
