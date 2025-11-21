<?php
require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Log request details for debugging
error_log("Upload request received - POST: " . print_r($_POST, true));
error_log("FILES: " . print_r($_FILES, true));

try {
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // Check if file was uploaded (check both possible field names)
    $fileField = isset($_FILES['profile_image']) ? 'profile_image' : 'profile_picture';
    
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        // Log more details about the error
        $errorMsg = 'No file uploaded or upload error occurred';
        if (isset($_FILES[$fileField])) {
            $uploadError = $_FILES[$fileField]['error'];
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $errorMsg .= ' (Error: ' . ($errorMessages[$uploadError] ?? 'Unknown error code ' . $uploadError) . ')';
        } else {
            $errorMsg .= ' (No file field found in request)';
        }
        throw new Exception($errorMsg);
    }
    
    $file = $_FILES[$fileField];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB limit');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Get old profile image to delete it
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldImage = $stmt->fetchColumn();
    
    // Log old image info
    if ($oldImage) {
        error_log("Old profile image found: " . $oldImage);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database with new profile image path
    $relativePath = 'uploads/profiles/' . $filename;
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$relativePath, $userId]);
    
    // Update session with new profile image
    $_SESSION['user']['profile_image'] = $relativePath;
    
    // Delete old profile image if it exists
    if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
        $deleted = @unlink(__DIR__ . '/../' . $oldImage); // @ suppresses errors if file doesn't exist
        if ($deleted) {
            error_log("Successfully deleted old profile image: " . $oldImage);
        } else {
            error_log("Failed to delete old profile image: " . $oldImage);
        }
    } elseif ($oldImage) {
        error_log("Old profile image path exists in DB but file not found: " . __DIR__ . '/../' . $oldImage);
    }
    
    // Add cache-busting timestamp to the URL
    $imageUrlWithTimestamp = $relativePath . '?v=' . time();
    
    $response['success'] = true;
    $response['message'] = 'Profile picture updated successfully';
    $response['image_url'] = $imageUrlWithTimestamp;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Profile picture upload error: " . $e->getMessage());
}

echo json_encode($response);
