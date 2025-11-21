<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['admin']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $doctorId = $_POST['doctor_id'] ?? 0;
    
    if (!$doctorId) {
        throw new Exception('Doctor ID is required');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['doctor_image']) || $_FILES['doctor_image']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No file uploaded or upload error occurred';
        if (isset($_FILES['doctor_image'])) {
            $uploadError = $_FILES['doctor_image']['error'];
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $errorMsg .= ' (' . ($errorMessages[$uploadError] ?? 'Unknown error code ' . $uploadError) . ')';
        }
        throw new Exception($errorMsg);
    }
    
    $file = $_FILES['doctor_image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB limit');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/doctors/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'doctor_' . $doctorId . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Get old profile image to delete it
    $stmt = $pdo->prepare("SELECT profile_image FROM doctors WHERE id = ?");
    $stmt->execute([$doctorId]);
    $oldImage = $stmt->fetchColumn();
    
    if (!$stmt->rowCount()) {
        throw new Exception('Doctor not found');
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database with new profile image path
    $relativePath = 'uploads/doctors/' . $filename;
    $stmt = $pdo->prepare("UPDATE doctors SET profile_image = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$relativePath, $doctorId]);
    
    // Delete old profile image if it exists
    if ($oldImage && file_exists(__DIR__ . '/../../' . $oldImage)) {
        @unlink(__DIR__ . '/../../' . $oldImage);
    }
    
    // Add cache-busting timestamp to the URL
    $imageUrlWithTimestamp = $relativePath . '?v=' . time();
    
    $response['success'] = true;
    $response['message'] = 'Doctor profile picture updated successfully';
    $response['image_url'] = $imageUrlWithTimestamp;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Doctor image upload error: " . $e->getMessage());
}

echo json_encode($response);
