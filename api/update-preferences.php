<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !is_array($input)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$email = isset($input['email_notifications']) ? (int)$input['email_notifications'] : 0;
$appt = isset($input['appointment_reminders']) ? (int)$input['appointment_reminders'] : 0;

try {
    // Upsert style: try update, if no rows affected insert
    $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, email_notifications, appointment_reminders) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE email_notifications=VALUES(email_notifications), appointment_reminders=VALUES(appointment_reminders)");
    $stmt->execute([$currentUser['id'], $email, $appt]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Update preferences error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to update preferences']);
}

?>
