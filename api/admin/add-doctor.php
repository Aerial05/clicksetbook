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
    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'email', 'license_number', 'specialty', 'experience_years', 'consultation_fee'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
            exit();
        }
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Check if license number already exists
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE license_number = ?");
    $stmt->execute([$_POST['license_number']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'License number already exists']);
        exit();
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Generate username from email
    $username = explode('@', $_POST['email'])[0];
    $originalUsername = $username;
    $counter = 1;
    
    // Ensure username is unique
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            break;
        }
        $username = $originalUsername . $counter;
        $counter++;
    }
    
    // Generate default password (doctor will need to change it)
    $defaultPassword = 'Doctor@' . rand(1000, 9999);
    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    // Insert into users table
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, is_active, email_verified)
        VALUES (?, ?, ?, 'doctor', ?, ?, ?, 1, 1)
    ");
    $stmt->execute([
        $username,
        $_POST['email'],
        $passwordHash,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['phone'] ?? null
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Insert into doctors table
    $stmt = $pdo->prepare("
        INSERT INTO doctors (
            user_id, 
            license_number, 
            specialty, 
            department, 
            qualification, 
            experience_years, 
            consultation_fee, 
            bio, 
            is_available
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $_POST['license_number'],
        $_POST['specialty'],
        $_POST['department'] ?? null,
        $_POST['qualification'] ?? null,
        intval($_POST['experience_years']),
        floatval($_POST['consultation_fee']),
        $_POST['bio'] ?? null,
        isset($_POST['is_available']) ? 1 : 0
    ]);
    
    $doctorId = $pdo->lastInsertId();
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Doctor added successfully',
        'doctor_id' => $doctorId,
        'user_id' => $userId,
        'default_password' => $defaultPassword // Return this so admin can share with doctor
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
