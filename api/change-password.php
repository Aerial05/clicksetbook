<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

try {
    // Validate required fields
    if (empty($_POST['current_password']) || empty($_POST['new_password'])) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        exit();
    }
    
    // Get current user's password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Verify current password
    if (!password_verify($_POST['current_password'], $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
    
    // Validate new password length
    if (strlen($_POST['new_password']) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
        exit();
    }
    
    // Hash new password
    $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([$newPasswordHash, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
