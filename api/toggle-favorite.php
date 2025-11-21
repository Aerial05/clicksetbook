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

$type = $input['type'] ?? '';
$itemId = $input['id'] ?? 0;

// Validate inputs
if (!in_array($type, ['doctor', 'service']) || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
    $stmt->execute([$userId, $type, $itemId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
        $stmt->execute([$existing['id']]);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Removed from favorites'
        ]);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, item_type, item_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $type, $itemId]);
        
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'Added to favorites'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Toggle favorite error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
