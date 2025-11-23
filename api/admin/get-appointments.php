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

// Get filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
$recent = isset($_GET['recent']) && $_GET['recent'] === 'true';

// Build query
$query = "SELECT 
    a.*,
    CONCAT(u.first_name, ' ', u.last_name) as patient_name,
    u.email as patient_email,
    u.phone as patient_phone,
    CONCAT(ud.first_name, ' ', ud.last_name) as doctor_name,
    s.name as service_name,
    CASE 
        WHEN a.doctor_id IS NOT NULL THEN 'doctor'
        ELSE 'service'
    END as type
FROM appointments a
INNER JOIN users u ON a.patient_id = u.id
LEFT JOIN doctors d ON a.doctor_id = d.id
LEFT JOIN users ud ON d.user_id = ud.id
LEFT JOIN services s ON a.service_id = s.id
WHERE 1=1";

$params = [];

if ($status) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

if ($date) {
    $query .= " AND DATE(a.appointment_date) = ?";
    $params[] = $date;
}

if ($recent) {
    $query .= " ORDER BY a.created_at DESC";
} else {
    $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

if ($limit) {
    // Cast to int for security, then add directly to query (LIMIT doesn't work with placeholders in older MySQL/MariaDB)
    $query .= " LIMIT " . intval($limit);
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
