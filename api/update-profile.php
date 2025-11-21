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
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
        echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
        exit();
    }
    
    // Check if email is already used by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$_POST['email'], $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email is already in use by another account']);
        exit();
    }
    
    // Update user profile
    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone'] ?? null,
        $userId
    ]);
    
    // Update session data
    $_SESSION['first_name'] = $_POST['first_name'];
    $_SESSION['last_name'] = $_POST['last_name'];
    $_SESSION['email'] = $_POST['email'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
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
