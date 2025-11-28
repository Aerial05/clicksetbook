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
                    a.cancel_request,
                    a.cancel_reason,
                    a.cancel_details,
                    a.cancel_requested_at,
                    a.reschedule_request,
                    a.requested_date,
                    a.requested_time,
                    a.reschedule_reason,
                    a.reschedule_requested_at,
                    a.reschedule_status,
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
            
            // Get appointment details first
            $aptStmt = $pdo->prepare("SELECT patient_id, patient_name, patient_email, appointment_date, appointment_time, status FROM appointments WHERE id = ?");
            $aptStmt->execute([$appointmentId]);
            $appointment = $aptStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            $oldStatus = $appointment['status'];
            
            // Update appointment status
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
                VALUES (?, ?, ?, ?, NOW())
            ");
            $historyStmt->execute([$appointmentId, $oldStatus, $newStatus, getCurrentUser()['id']]);
            
            // Create notification for the patient
            if ($appointment['patient_id']) {
                $notificationTitle = '';
                $notificationMessage = '';
                $templateType = 'status_update';
                
                // Format date and time for better readability
                $formattedDate = date('F j, Y', strtotime($appointment['appointment_date']));
                $formattedTime = date('g:i A', strtotime($appointment['appointment_time']));
                
                switch ($newStatus) {
                    case 'confirmed':
                        $notificationTitle = 'Appointment Confirmed';
                        $notificationMessage = "Your appointment scheduled for {$formattedDate} at {$formattedTime} has been confirmed. Please arrive 10 minutes early.";
                        $templateType = 'booking_confirmation';
                        break;
                    case 'cancelled':
                        $notificationTitle = 'Appointment Cancelled';
                        $notificationMessage = "Your appointment scheduled for {$formattedDate} at {$formattedTime} has been cancelled. If you did not request this cancellation, please contact us immediately.";
                        $templateType = 'cancellation';
                        break;
                    case 'completed':
                        $notificationTitle = 'Appointment Completed';
                        $notificationMessage = "Your appointment on {$formattedDate} at {$formattedTime} has been marked as completed. Thank you for choosing our services.";
                        break;
                    case 'archived':
                        $notificationTitle = 'Appointment Archived';
                        $notificationMessage = "Your appointment scheduled for {$formattedDate} at {$formattedTime} has been archived.";
                        break;
                    case 'in_progress':
                        $notificationTitle = 'Appointment In Progress';
                        $notificationMessage = "Your appointment scheduled for {$formattedDate} at {$formattedTime} is currently in progress.";
                        break;
                    case 'no_show':
                        $notificationTitle = 'Missed Appointment';
                        $notificationMessage = "You missed your appointment scheduled for {$formattedDate} at {$formattedTime}. Please contact us to reschedule.";
                        break;
                    default:
                        $notificationTitle = 'Appointment Status Updated';
                        $notificationMessage = "Your appointment status has been updated to {$newStatus}.";
                }
                
                // Insert notification
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, title, appointment_id, recipient_email, notification_type, template_type, subject, message_content, status, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 'email', ?, ?, ?, 'pending', 0, NOW())
                ");
                $notifStmt->execute([
                    $appointment['patient_id'],
                    $notificationTitle,
                    $appointmentId,
                    $appointment['patient_email'],
                    $templateType,
                    $notificationTitle,
                    $notificationMessage
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment status updated successfully'
            ]);
            break;
            
        case 'archive':
            $appointmentId = $_POST['id'] ?? 0;
            
            // Get appointment details first
            $aptStmt = $pdo->prepare("SELECT patient_id, patient_name, patient_email, appointment_date, appointment_time, status FROM appointments WHERE id = ?");
            $aptStmt->execute([$appointmentId]);
            $appointment = $aptStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            $oldStatus = $appointment['status'];
            
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
                VALUES (?, ?, 'archived', ?, NOW())
            ");
            $historyStmt->execute([$appointmentId, $oldStatus, getCurrentUser()['id']]);
            
            // Create notification for the patient
            if ($appointment['patient_id']) {
                $formattedDate = date('F j, Y', strtotime($appointment['appointment_date']));
                $formattedTime = date('g:i A', strtotime($appointment['appointment_time']));
                
                $notificationTitle = 'Appointment Archived';
                $notificationMessage = "Your appointment scheduled for {$formattedDate} at {$formattedTime} has been archived.";
                
                // Insert notification
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, title, appointment_id, recipient_email, notification_type, template_type, subject, message_content, status, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 'email', 'status_update', ?, ?, 'pending', 0, NOW())
                ");
                $notifStmt->execute([
                    $appointment['patient_id'],
                    $notificationTitle,
                    $appointmentId,
                    $appointment['patient_email'],
                    $notificationTitle,
                    $notificationMessage
                ]);
            }
            
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
                    SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN reschedule_request = 1 AND reschedule_status = 'pending' THEN 1 ELSE 0 END) as pending_reschedules
                FROM appointments
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'approveReschedule':
            $appointmentId = $_POST['id'] ?? 0;
            
            // Get appointment details
            $aptStmt = $pdo->prepare("
                SELECT 
                    a.*,
                    s.name as service_name,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE a.id = ?
            ");
            $aptStmt->execute([$appointmentId]);
            $appointment = $aptStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            // Verify there's a pending reschedule request
            if ($appointment['reschedule_request'] != 1 || $appointment['reschedule_status'] != 'pending') {
                throw new Exception('No pending reschedule request found');
            }
            
            // Verify requested date and time exist
            if (!$appointment['requested_date'] || !$appointment['requested_time']) {
                throw new Exception('Invalid reschedule request data');
            }
            
            // Calculate new end_time based on service duration (default 30 minutes)
            $requestedDateTime = new DateTime($appointment['requested_date'] . ' ' . $appointment['requested_time']);
            $endDateTime = clone $requestedDateTime;
            $endDateTime->modify('+30 minutes'); // TODO: Use actual service duration
            
            $oldDate = $appointment['appointment_date'];
            $oldTime = $appointment['appointment_time'];
            
            // Update appointment with new date/time and mark reschedule as approved
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET appointment_date = ?,
                    appointment_time = ?,
                    end_time = ?,
                    reschedule_request = 0,
                    reschedule_status = 'approved',
                    reschedule_approved_by = ?,
                    reschedule_response_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $appointment['requested_date'],
                $appointment['requested_time'],
                $endDateTime->format('H:i:s'),
                getCurrentUser()['id'],
                $appointmentId
            ]);
            
            // Log to appointment_history
            $historyStmt = $pdo->prepare("
                INSERT INTO appointment_history (appointment_id, old_status, new_status, changed_by, change_reason, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $changeReason = "Rescheduled from {$oldDate} {$oldTime} to {$appointment['requested_date']} {$appointment['requested_time']}";
            $historyStmt->execute([
                $appointmentId,
                'reschedule_pending',
                'reschedule_approved',
                getCurrentUser()['id'],
                $changeReason
            ]);
            
            // Send notification to patient
            $newDate = date('F j, Y', strtotime($appointment['requested_date']));
            $newTime = date('g:i A', strtotime($appointment['requested_time']));
            $oldDateFormatted = date('F j, Y', strtotime($oldDate));
            $oldTimeFormatted = date('g:i A', strtotime($oldTime));
            
            $serviceName = $appointment['service_name'] ?: 'Dr. ' . $appointment['doctor_name'];
            
            $notificationTitle = "Reschedule Request Approved";
            $notificationMessage = "Good news! Your reschedule request has been approved. Your appointment for {$serviceName} has been moved from {$oldDateFormatted} at {$oldTimeFormatted} to {$newDate} at {$newTime}.";
            
            if ($appointment['patient_email']) {
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, title, appointment_id, recipient_email, notification_type, template_type, subject, message_content, status, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 'email', 'reschedule_approved', ?, ?, 'pending', 0, NOW())
                ");
                $notifStmt->execute([
                    $appointment['patient_id'],
                    $notificationTitle,
                    $appointmentId,
                    $appointment['patient_email'],
                    $notificationTitle,
                    $notificationMessage
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Reschedule request approved successfully'
            ]);
            break;
            
        case 'declineReschedule':
            $appointmentId = $_POST['id'] ?? 0;
            $declineReason = $_POST['decline_reason'] ?? 'No reason provided';
            
            // Get appointment details
            $aptStmt = $pdo->prepare("
                SELECT 
                    a.*,
                    s.name as service_name,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE a.id = ?
            ");
            $aptStmt->execute([$appointmentId]);
            $appointment = $aptStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            // Verify there's a pending reschedule request
            if ($appointment['reschedule_request'] != 1 || $appointment['reschedule_status'] != 'pending') {
                throw new Exception('No pending reschedule request found');
            }
            
            // Keep original date/time, just mark reschedule as declined and clear requested fields
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET reschedule_request = 0,
                    reschedule_status = 'declined',
                    reschedule_approved_by = ?,
                    reschedule_response_at = NOW(),
                    requested_date = NULL,
                    requested_time = NULL,
                    reschedule_reason = CONCAT(COALESCE(reschedule_reason, ''), ' [Declined: ', ?, ']'),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                getCurrentUser()['id'],
                $declineReason,
                $appointmentId
            ]);
            
            // Log to appointment_history
            $historyStmt = $pdo->prepare("
                INSERT INTO appointment_history (appointment_id, old_status, new_status, changed_by, change_reason, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $historyStmt->execute([
                $appointmentId,
                'reschedule_pending',
                'reschedule_declined',
                getCurrentUser()['id'],
                $declineReason
            ]);
            
            // Send notification to patient
            $originalDate = date('F j, Y', strtotime($appointment['appointment_date']));
            $originalTime = date('g:i A', strtotime($appointment['appointment_time']));
            $requestedDate = date('F j, Y', strtotime($appointment['requested_date']));
            $requestedTime = date('g:i A', strtotime($appointment['requested_time']));
            
            $serviceName = $appointment['service_name'] ?: 'Dr. ' . $appointment['doctor_name'];
            
            $notificationTitle = "Reschedule Request Declined";
            $notificationMessage = "Your reschedule request for {$serviceName} has been declined. Your appointment remains scheduled for {$originalDate} at {$originalTime}. Requested date was {$requestedDate} at {$requestedTime}. Reason: {$declineReason}. You may submit a new reschedule request with a different date if needed.";
            
            if ($appointment['patient_email']) {
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, title, appointment_id, recipient_email, notification_type, template_type, subject, message_content, status, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 'email', 'reschedule_declined', ?, ?, 'pending', 0, NOW())
                ");
                $notifStmt->execute([
                    $appointment['patient_id'],
                    $notificationTitle,
                    $appointmentId,
                    $appointment['patient_email'],
                    $notificationTitle,
                    $notificationMessage
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Reschedule request declined successfully'
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
