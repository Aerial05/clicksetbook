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

try {
    // Check if marking all as read
    if (isset($input['mark_all']) && $input['mark_all'] === true) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read',
            'updated' => $stmt->rowCount()
        ]);
        exit();
    }
    
    // Mark single notification as read
    $notificationId = $input['id'] ?? 0;
    
    if (!$notificationId) {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit();
    }
    
    // Verify notification belongs to user and mark as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Notification not found or already read'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
