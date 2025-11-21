<?php
require 'config/database.php';

$pdo = getDBConnection();

// Doctor data
$doctors = [
    ['name' => 'Jennifer Yu', 'specialty' => 'Pediatrician', 'department' => 'Pediatrics'],
    ['name' => 'Jinkee Fernande-Lazaro', 'specialty' => 'Opthalmologist', 'department' => 'Ophthalmology'],
    ['name' => 'Rabinald Resurreccion', 'specialty' => 'Urologist', 'department' => 'Urology'],
    ['name' => 'Robertino Siccion', 'specialty' => 'Internal Medicine', 'department' => 'Internal Medicine'],
    ['name' => 'Woody Zapanta', 'specialty' => 'Ears Nose Throat', 'department' => 'Otolaryngology']
];

try {
    $pdo->beginTransaction();
    
    foreach ($doctors as $index => $doctor) {
        // Split name into first and last
        $nameParts = explode(' ', $doctor['name'], 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Create user account
        $username = strtolower(str_replace(' ', '.', $doctor['name']));
        $email = $username . '@clicksetbook.com';
        $password = password_hash('Doctor@123', PASSWORD_DEFAULT);
        
        $userStmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role, first_name, last_name, created_at, updated_at, is_active, email_verified)
            VALUES (?, ?, ?, 'doctor', ?, ?, NOW(), NOW(), 1, 0)
        ");
        
        $userStmt->execute([$username, $email, $password, $firstName, $lastName]);
        $userId = $pdo->lastInsertId();
        
        // Update doctor with user_id
        $doctorStmt = $pdo->prepare("
            UPDATE doctors 
            SET user_id = ? 
            WHERE id = ? + 1
        ");
        
        $doctorStmt->execute([$userId, $index]);
        
        echo "Created user: $firstName $lastName (ID: $userId)\n";
    }
    
    $pdo->commit();
    echo "\nAll doctors updated successfully!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
