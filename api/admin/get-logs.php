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
$type = isset($_GET['type']) ? $_GET['type'] : 'appointment-history';
$fromDate = isset($_GET['from']) ? $_GET['from'] : '';
$toDate = isset($_GET['to']) ? $_GET['to'] : '';

$logs = [];

try {
    if ($type === 'appointment-history') {
        // Get appointment history
        $query = "SELECT 
            a.id,
            a.created_at,
            CONCAT('Appointment ', 
                CASE a.status
                    WHEN 'pending' THEN 'created'
                    WHEN 'confirmed' THEN 'confirmed'
                    WHEN 'completed' THEN 'completed'
                    WHEN 'cancelled' THEN 'cancelled'
                END
            ) as action,
            CONCAT(
                CONCAT(u.first_name, ' ', u.last_name),
                ' with ',
                CASE 
                    WHEN a.doctor_id IS NOT NULL THEN CONCAT('Dr. ', ud.first_name, ' ', ud.last_name)
                    ELSE s.name
                END,
                ' on ',
                DATE_FORMAT(a.appointment_date, '%M %d, %Y'),
                ' at ',
                a.appointment_time
            ) as details
        FROM appointments a
        INNER JOIN users u ON a.patient_id = u.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users ud ON d.user_id = ud.id
        LEFT JOIN services s ON a.service_id = s.id
        WHERE 1=1";
        
        $params = [];
        
        if ($fromDate) {
            $query .= " AND DATE(a.created_at) >= ?";
            $params[] = $fromDate;
        }
        
        if ($toDate) {
            $query .= " AND DATE(a.created_at) <= ?";
            $params[] = $toDate;
        }
        
        $query .= " ORDER BY a.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($type === 'user-activity') {
        // Get user activity logs
        $query = "SELECT 
            u.id,
            u.created_at,
            'User registered' as action,
            CONCAT(u.first_name, ' ', u.last_name, ' (', u.email, ') joined the platform') as details
        FROM users u
        WHERE u.role != 'admin'";
        
        $params = [];
        
        if ($fromDate) {
            $query .= " AND DATE(u.created_at) >= ?";
            $params[] = $fromDate;
        }
        
        if ($toDate) {
            $query .= " AND DATE(u.created_at) <= ?";
            $params[] = $toDate;
        }
        
        $query .= " ORDER BY u.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($type === 'system-changes') {
        // Get system changes (doctors and services added)
        $query = "(SELECT 
            d.id,
            d.created_at,
            'Doctor added' as action,
            CONCAT('Dr. ', u.first_name, ' ', u.last_name, ' (', d.specialty, ')') as details
        FROM doctors d
        INNER JOIN users u ON d.user_id = u.id
        WHERE 1=1";
        
        $params1 = [];
        $params2 = [];
        
        if ($fromDate) {
            $query .= " AND DATE(d.created_at) >= ?";
            $params1[] = $fromDate;
        }
        
        if ($toDate) {
            $query .= " AND DATE(d.created_at) <= ?";
            $params1[] = $toDate;
        }
        
        $query .= ") UNION ALL (SELECT 
            s.id,
            s.created_at,
            'Service added' as action,
            CONCAT(s.name, ' (', s.category, ')') as details
        FROM services s
        WHERE 1=1";
        
        if ($fromDate) {
            $query .= " AND DATE(s.created_at) >= ?";
            $params2[] = $fromDate;
        }
        
        if ($toDate) {
            $query .= " AND DATE(s.created_at) <= ?";
            $params2[] = $toDate;
        }
        
        $query .= ") ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(array_merge($params1, $params2));
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
