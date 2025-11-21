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
        d.id,
        d.user_id,
        u.first_name,
        u.last_name,
        u.email,
        d.specialty,
        d.department,
        d.experience_years,
        d.consultation_fee,
        d.license_number,
        d.qualification,
        d.profile_image,
        d.is_available,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id) as total_appointments
    FROM doctors d
    INNER JOIN users u ON d.user_id = u.id
    ORDER BY u.last_name, u.first_name";
    
    $stmt = $pdo->query($query);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
