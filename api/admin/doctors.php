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
            // Get all doctors with pagination or single doctor by ID
            $doctorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $specialty = $_GET['specialty'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $whereClause = ' WHERE 1=1';
            $params = [];
            
            // If specific doctor ID requested
            if ($doctorId > 0) {
                $whereClause .= ' AND d.id = ?';
                $params[] = $doctorId;
            }
            
            if ($specialty && $specialty !== 'all') {
                $whereClause .= ' AND d.specialty = ?';
                $params[] = $specialty;
            }
            
            if ($search) {
                $whereClause .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR d.specialty LIKE ? OR d.department LIKE ?)';
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Get total count
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM doctors d 
                LEFT JOIN users u ON d.user_id = u.id
                " . $whereClause
            );
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get doctors
            $sql = "
                SELECT 
                    d.id,
                    d.user_id,
                    d.license_number,
                    d.specialty,
                    d.department,
                    d.qualification,
                    d.experience_years,
                    d.consultation_fee,
                    d.bio,
                    d.profile_image,
                    d.is_available,
                    d.created_at,
                    COALESCE(u.first_name, 'Dr.') as first_name,
                    COALESCE(u.last_name, COALESCE(d.specialty, 'Unknown')) as last_name,
                    COALESCE(u.email, '') as email,
                    COALESCE(u.phone, '') as phone,
                    COALESCE(u.is_active, 1) as is_active,
                    COUNT(DISTINCT a.id) as total_appointments
                FROM doctors d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN appointments a ON d.id = a.doctor_id
                " . $whereClause . "
                GROUP BY d.id
                ORDER BY d.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'doctors' => $doctors,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'create':
            // Create new doctor and user account
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $licenseNumber = $_POST['license_number'] ?? '';
            $specialty = $_POST['specialty'] ?? '';
            $department = $_POST['department'] ?? '';
            $qualification = $_POST['qualification'] ?? '';
            $experienceYears = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
            $consultationFee = isset($_POST['consultation_fee']) ? (float)$_POST['consultation_fee'] : 0;
            $bio = $_POST['bio'] ?? '';
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
            
            // Check if email exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                throw new Exception('Email already exists');
            }
            
            // Check if license number exists
            $checkStmt = $pdo->prepare("SELECT id FROM doctors WHERE license_number = ?");
            $checkStmt->execute([$licenseNumber]);
            if ($checkStmt->fetch()) {
                throw new Exception('License number already exists');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Create user account with default password (doctor should change it)
                $username = strtolower($firstName . '.' . $lastName);
                $defaultPassword = password_hash('Doctor@123', PASSWORD_DEFAULT);
                
                $userStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, created_at, updated_at, is_active, email_verified)
                    VALUES (?, ?, ?, 'doctor', ?, ?, ?, NOW(), NOW(), 1, 0)
                ");
                
                $userStmt->execute([$username, $email, $defaultPassword, $firstName, $lastName, $phone]);
                $userId = $pdo->lastInsertId();
                
                // Create doctor record
                $doctorStmt = $pdo->prepare("
                    INSERT INTO doctors (user_id, license_number, specialty, department, qualification, experience_years, consultation_fee, bio, is_available, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                ");
                
                $doctorStmt->execute([$userId, $licenseNumber, $specialty, $department, $qualification, $experienceYears, $consultationFee, $bio]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Doctor created successfully. Default password: Doctor@123',
                    'doctor_id' => $pdo->lastInsertId(),
                    'user_id' => $userId
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'update':
            $doctorId = $_POST['id'] ?? 0;
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $licenseNumber = $_POST['license_number'] ?? '';
            $specialty = $_POST['specialty'] ?? '';
            $department = $_POST['department'] ?? '';
            $qualification = $_POST['qualification'] ?? '';
            $experienceYears = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
            $consultationFee = isset($_POST['consultation_fee']) ? (float)$_POST['consultation_fee'] : 0;
            $bio = $_POST['bio'] ?? '';
            $isAvailable = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;
            
            // Get user_id for this doctor
            $userStmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
            $userStmt->execute([$doctorId]);
            $doctor = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doctor) {
                throw new Exception('Doctor not found');
            }
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
            
            // Check if email exists for another user
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $doctor['user_id']]);
            if ($checkStmt->fetch()) {
                throw new Exception('Email already exists');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Update user
                $userUpdateStmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?,
                        last_name = ?,
                        email = ?,
                        phone = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $userUpdateStmt->execute([$firstName, $lastName, $email, $phone, $doctor['user_id']]);
                
                // Update doctor
                $doctorUpdateStmt = $pdo->prepare("
                    UPDATE doctors 
                    SET license_number = ?,
                        specialty = ?,
                        department = ?,
                        qualification = ?,
                        experience_years = ?,
                        consultation_fee = ?,
                        bio = ?,
                        is_available = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $doctorUpdateStmt->execute([$licenseNumber, $specialty, $department, $qualification, $experienceYears, $consultationFee, $bio, $isAvailable, $doctorId]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Doctor updated successfully'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'delete':
            $doctorId = $_POST['id'] ?? 0;
            
            // Get user_id for this doctor
            $userStmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
            $userStmt->execute([$doctorId]);
            $doctor = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doctor) {
                throw new Exception('Doctor not found');
            }
            
            // Delete doctor (will cascade to user based on foreign key)
            $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt->execute([$doctorId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Doctor deleted successfully'
            ]);
            break;
            
        case 'toggleAvailability':
            $doctorId = $_POST['id'] ?? 0;
            
            // Toggle availability
            $stmt = $pdo->prepare("
                UPDATE doctors 
                SET is_available = NOT is_available,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$doctorId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Doctor availability updated successfully'
            ]);
            break;
            
        case 'getStats':
            // Get doctor statistics
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available,
                    COUNT(DISTINCT specialty) as specialties,
                    COUNT(DISTINCT department) as departments
                FROM doctors
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
    error_log("Admin doctors API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
