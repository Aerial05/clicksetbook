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

try {
    // Mark all notifications as read for this user
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'All notifications marked as read'
    ]);
    
} catch (PDOException $e) {
    error_log("Mark all read error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
