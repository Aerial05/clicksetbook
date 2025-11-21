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

$pdo = getDBConnection();

try {
    $query = "SELECT 
        u.id,
        u.username,
        u.email,
        u.first_name,
        u.last_name,
        u.phone,
        u.role,
        u.created_at,
        (SELECT COUNT(*) FROM appointments WHERE patient_id = u.id) as total_appointments
    FROM users u
    ORDER BY u.created_at DESC";
    
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
