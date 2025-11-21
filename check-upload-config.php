<?php
/**
 * PHP Upload Configuration Checker
 * This script helps diagnose file upload issues
 * Access this file directly in your browser to check upload settings
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Configuration Check</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #1f2937;
            margin-top: 0;
        }
        h2 {
            color: #3b82f6;
            font-size: 18px;
            margin-top: 0;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        td:first-child {
            font-weight: 600;
            color: #4b5563;
            width: 50%;
        }
        .ok {
            color: #059669;
            font-weight: 600;
        }
        .warning {
            color: #d97706;
            font-weight: 600;
        }
        .error {
            color: #dc2626;
            font-weight: 600;
        }
        .info {
            background: #dbeafe;
            padding: 12px;
            border-radius: 8px;
            margin-top: 16px;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📋 PHP Upload Configuration</h1>
        
        <h2>File Upload Settings</h2>
        <table>
            <tr>
                <td>File Uploads Enabled</td>
                <td class="<?php echo ini_get('file_uploads') ? 'ok' : 'error'; ?>">
                    <?php echo ini_get('file_uploads') ? 'YES ✓' : 'NO ✗ (This is a problem!)'; ?>
                </td>
            </tr>
            <tr>
                <td>Upload Max Filesize</td>
                <td class="<?php 
                    $uploadMax = ini_get('upload_max_filesize');
                    $bytes = return_bytes($uploadMax);
                    echo ($bytes >= 5 * 1024 * 1024) ? 'ok' : 'warning';
                ?>">
                    <?php echo $uploadMax; ?>
                    <?php if ($bytes < 5 * 1024 * 1024): ?>
                        (Warning: Less than 5MB)
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Post Max Size</td>
                <td class="<?php 
                    $postMax = ini_get('post_max_size');
                    $bytes = return_bytes($postMax);
                    echo ($bytes >= 8 * 1024 * 1024) ? 'ok' : 'warning';
                ?>">
                    <?php echo $postMax; ?>
                    <?php if ($bytes < 8 * 1024 * 1024): ?>
                        (Warning: Should be larger than upload_max_filesize)
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Max File Uploads</td>
                <td><?php echo ini_get('max_file_uploads'); ?></td>
            </tr>
            <tr>
                <td>Memory Limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <td>Max Execution Time</td>
                <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
            </tr>
            <tr>
                <td>Upload Tmp Directory</td>
                <td class="<?php 
                    $tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
                    echo (is_writable($tmpDir)) ? 'ok' : 'error';
                ?>">
                    <?php 
                    echo $tmpDir ?: sys_get_temp_dir();
                    echo is_writable($tmpDir) ? ' ✓' : ' ✗ (Not writable!)';
                    ?>
                </td>
            </tr>
        </table>

        <h2>Directory Permissions</h2>
        <table>
            <?php
            $uploadDir = __DIR__ . '/uploads/profiles/';
            $uploadsExists = file_exists($uploadDir);
            $uploadsWritable = $uploadsExists && is_writable($uploadDir);
            ?>
            <tr>
                <td>uploads/profiles/ directory</td>
                <td class="<?php echo $uploadsExists ? ($uploadsWritable ? 'ok' : 'warning') : 'error'; ?>">
                    <?php 
                    if (!$uploadsExists) {
                        echo 'Does not exist ✗ (Will be created on first upload)';
                    } elseif (!$uploadsWritable) {
                        echo 'Exists but NOT WRITABLE ✗';
                    } else {
                        echo 'Exists and writable ✓';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>api/ directory</td>
                <td class="<?php echo is_readable(__DIR__ . '/api/') ? 'ok' : 'error'; ?>">
                    <?php echo is_readable(__DIR__ . '/api/') ? 'Readable ✓' : 'Not readable ✗'; ?>
                </td>
            </tr>
        </table>

        <h2>PHP Configuration</h2>
        <table>
            <tr>
                <td>PHP Version</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Server Software</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
            </tr>
            <tr>
                <td>PHP SAPI</td>
                <td><?php echo php_sapi_name(); ?></td>
            </tr>
        </table>

        <div class="info">
            <strong>💡 Recommendations:</strong><br>
            • upload_max_filesize should be at least 5M for profile pictures<br>
            • post_max_size should be larger than upload_max_filesize (e.g., 8M)<br>
            • Make sure uploads/profiles/ directory is writable by PHP<br>
            • If values need to be changed, edit your php.ini file and restart Apache
        </div>
    </div>

    <div class="card">
        <h2>Test File Upload</h2>
        <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
            <input type="file" name="test_file" accept="image/*" style="margin-bottom: 12px;">
            <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">
                Test Upload
            </button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
            echo '<div style="margin-top: 16px; padding: 12px; background: #f3f4f6; border-radius: 8px;">';
            echo '<strong>Upload Test Result:</strong><br>';
            echo '<pre style="margin-top: 8px; overflow-x: auto;">';
            print_r($_FILES['test_file']);
            echo '</pre>';
            
            if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
                echo '<p class="ok">✓ File uploaded successfully to temporary location!</p>';
            } else {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'PHP extension stopped the upload'
                ];
                echo '<p class="error">✗ Upload failed: ' . ($errors[$_FILES['test_file']['error']] ?? 'Unknown error') . '</p>';
            }
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

<?php
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}
?>
