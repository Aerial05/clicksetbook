<?php
// Suppress PHP warnings/notices in JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Require admin authentication
if (!isLoggedIn() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$appointmentId = intval($input['id']);
$status = $input['status'];

// Validate status
$validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$pdo = getDBConnection();

try {
    // Update appointment status
    $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $appointmentId]);
    
    // Get appointment details for notification
    $stmt = $pdo->prepare("SELECT 
        a.*,
        u.first_name,
        u.last_name,
        CASE 
            WHEN a.doctor_id IS NOT NULL THEN CONCAT('Dr. ', ud.first_name, ' ', ud.last_name)
            ELSE s.name
        END as provider_name
    FROM appointments a
    INNER JOIN users u ON a.patient_id = u.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users ud ON d.user_id = ud.id
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.id = ?");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        // Create notification for user
        $notificationTitle = '';
        $notificationMessage = '';
        
        if ($status === 'confirmed') {
            $notificationTitle = 'Appointment Confirmed';
            $notificationMessage = "Your appointment with {$appointment['provider_name']} on " . 
                                 date('M d, Y', strtotime($appointment['appointment_date'])) . 
                                 " at {$appointment['appointment_time']} has been confirmed.";
        } elseif ($status === 'cancelled') {
            $notificationTitle = 'Appointment Cancelled';
            $notificationMessage = "Your appointment with {$appointment['provider_name']} on " . 
                                 date('M d, Y', strtotime($appointment['appointment_date'])) . 
                                 " at {$appointment['appointment_time']} has been cancelled.";
        } elseif ($status === 'completed') {
            $notificationTitle = 'Appointment Completed';
            $notificationMessage = "Your appointment with {$appointment['provider_name']} has been completed. Thank you!";
        }
        
        if ($notificationTitle) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) 
                                  VALUES (?, ?, ?, 'appointment', NOW())");
            $stmt->execute([$appointment['patient_id'], $notificationTitle, $notificationMessage]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment status updated successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
