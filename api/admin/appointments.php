<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['admin']);

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            // Get all appointments with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? '';
            
            $whereClause = '';
            $params = [];
            
            if ($status) {
                $whereClause = ' WHERE a.status = ?';
                $params[] = $status;
            }
            
            // Get total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments a" . $whereClause);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get appointments
            $sql = "
                SELECT 
                    a.id,
                    a.appointment_number,
                    a.appointment_date,
                    a.appointment_time,
                    a.end_time,
                    a.status,
                    a.patient_name,
                    a.patient_email,
                    a.patient_phone,
                    a.patient_dob,
                    a.symptoms,
                    a.referrer,
                    a.appointment_purpose,
                    a.notes,
                    a.priority,
                    a.total_cost,
                    a.payment_status,
                    a.created_at,
                    s.name as service_name,
                    s.category as service_category,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                " . $whereClause . "
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'appointments' => $appointments,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'updateStatus':
            $appointmentId = $_POST['id'] ?? 0;
            $newStatus = $_POST['status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            $validStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show', 'archived'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET status = ?, 
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$newStatus, $notes, $appointmentId]);
            
            // Log status change to appointment_history
            $historyStmt = $pdo->prepare("
                INSERT INTO appointment_history (appointment_id, old_status, new_status, changed_by, created_at)
                SELECT ?, status, ?, ?, NOW()
                FROM appointments WHERE id = ?
            ");
            $historyStmt->execute([$appointmentId, $newStatus, getCurrentUser()['id'], $appointmentId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment status updated successfully'
            ]);
            break;
            
        case 'archive':
            $appointmentId = $_POST['id'] ?? 0;
            
            // Archive appointment by changing status to 'archived'
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET status = 'archived',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$appointmentId]);
            
            // Log status change to appointment_history
            $historyStmt = $pdo->prepare("
                INSERT INTO appointment_history (appointment_id, old_status, new_status, changed_by, created_at)
                SELECT ?, status, 'archived', ?, NOW()
                FROM appointments WHERE id = ?
            ");
            $historyStmt->execute([$appointmentId, getCurrentUser()['id'], $appointmentId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment archived successfully'
            ]);
            break;
            
        case 'delete':
            $appointmentId = $_POST['id'] ?? 0;
            
            // Delete appointment (will cascade to related records)
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ]);
            break;
            
        case 'getStats':
            // Get appointment statistics
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
                    SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) as today
                FROM appointments
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    error_log("Admin appointments API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
