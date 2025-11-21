<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$currentUser = getCurrentUser();
$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("SELECT email_notifications, appointment_reminders FROM user_preferences WHERE user_id = ? LIMIT 1");
    $stmt->execute([$currentUser['id']]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prefs) {
        // Return defaults if not set
        $prefs = [
            'email_notifications' => 1,
            'appointment_reminders' => 1
        ];
    }

    echo json_encode(['success' => true, 'preferences' => $prefs]);
} catch (PDOException $e) {
    error_log('Get preferences error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load preferences']);
}

?>
