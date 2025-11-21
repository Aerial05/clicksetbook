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
            // Get all users with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $role = $_GET['role'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $whereClause = ' WHERE 1=1';
            $params = [];
            
            if ($role && $role !== 'all') {
                $whereClause .= ' AND u.role = ?';
                $params[] = $role;
            }
            
            if ($search) {
                $whereClause .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Get total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u" . $whereClause);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get users
            $sql = "
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.role,
                    u.first_name,
                    u.last_name,
                    u.phone,
                    u.date_of_birth,
                    u.address,
                    u.emergency_contact,
                    u.emergency_phone,
                    u.is_active,
                    u.email_verified,
                    u.created_at,
                    u.last_login,
                    COUNT(DISTINCT a.id) as total_appointments
                FROM users u
                LEFT JOIN appointments a ON u.id = a.patient_id
                " . $whereClause . "
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'create':
            // Create a new user
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'patient';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $dateOfBirth = $_POST['date_of_birth'] ?? null;
            $address = trim($_POST['address'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            // Validate required fields
            if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
                throw new Exception('Username, email, password, first name, and last name are required.');
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            
            // Validate password length
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception('Username already exists.');
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Email already exists.');
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "
                INSERT INTO users (
                    username, email, password, role, first_name, last_name, 
                    phone, date_of_birth, address, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $username, $email, $hashedPassword, $role, 
                $firstName, $lastName, $phone, 
                $dateOfBirth ?: null, $address, $isActive
            ]);
            
            $userId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'User created successfully!',
                'user_id' => $userId
            ]);
            break;
            
        case 'update':
            $userId = $_POST['id'] ?? 0;
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $role = $_POST['role'] ?? '';
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
            
            // Check if email exists for another user
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $userId]);
            if ($checkStmt->fetch()) {
                throw new Exception('Email already exists');
            }
            
            // Update user
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    role = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$firstName, $lastName, $email, $phone, $role, $isActive, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
            break;
            
        case 'delete':
            $userId = $_POST['id'] ?? 0;
            $currentUserId = getCurrentUser()['id'];
            
            // Prevent deleting self
            if ($userId == $currentUserId) {
                throw new Exception('You cannot delete your own account');
            }
            
            // Delete user (will cascade to related records based on foreign key constraints)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            break;
            
        case 'toggleActive':
            $userId = $_POST['id'] ?? 0;
            $currentUserId = getCurrentUser()['id'];
            
            // Prevent deactivating self
            if ($userId == $currentUserId) {
                throw new Exception('You cannot deactivate your own account');
            }
            
            // Toggle active status
            $stmt = $pdo->prepare("
                UPDATE users 
                SET is_active = NOT is_active,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User status updated successfully'
            ]);
            break;

        case 'changeRole':
            $userId = $_POST['id'] ?? 0;
            $newRole = $_POST['role'] ?? '';
            $currentUserId = getCurrentUser()['id'];
            
            // Validate role
            $validRoles = ['patient', 'doctor', 'admin', 'staff'];
            if (!in_array($newRole, $validRoles)) {
                throw new Exception('Invalid role. Valid roles are: patient, doctor, admin, staff');
            }
            
            // Prevent changing own role
            if ($userId == $currentUserId) {
                throw new Exception('You cannot change your own role');
            }
            
            // Get current user info
            $stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $oldRole = $user['role'];
            
            // Update role
            $stmt = $pdo->prepare("
                UPDATE users 
                SET role = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$newRole, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => "User role changed from '{$oldRole}' to '{$newRole}' successfully"
            ]);
            break;
            
        case 'getStats':
            // Get user statistics
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN role = 'patient' THEN 1 ELSE 0 END) as patients,
                    SUM(CASE WHEN role = 'doctor' THEN 1 ELSE 0 END) as doctors,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
                FROM users
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
    error_log("Admin users API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
