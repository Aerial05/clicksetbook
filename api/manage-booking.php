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

// Validate inputs
if (!$bookingId || !in_array($action, ['cancel', 'reschedule', 'complete'])) {
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
        // Update status to cancelled
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled', cancelled_at = NOW(), cancelled_by = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$userId, $bookingId]);
        
        // Create notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message_content, template_type, notification_type, is_read, created_at) 
                               VALUES (?, 'Appointment Cancelled', 'Your appointment has been cancelled successfully', 'cancellation', 'email', 0, NOW())");
        $stmt->execute([$userId]);
        
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
        // TODO: Implement reschedule logic
        echo json_encode([
            'success' => false,
            'message' => 'Reschedule functionality not yet implemented'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Manage booking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
