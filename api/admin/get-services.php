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
        s.*,
        (SELECT COUNT(*) FROM appointments WHERE service_id = s.id) as total_bookings
    FROM services s
    ORDER BY s.category, s.name";
    
    $stmt = $pdo->query($query);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
