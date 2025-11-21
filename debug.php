<?php 
require_once 'includes/auth.php';

// Debug information
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Click Set Book</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            padding: 20px; 
            background: #1a1a1a; 
            color: #00ff00;
        }
        .debug-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: #000; 
            padding: 20px; 
            border-radius: 8px; 
            border: 2px solid #00ff00;
        }
        .section { 
            margin: 20px 0; 
            padding: 15px; 
            border: 1px solid #333; 
            border-radius: 5px; 
            background: #0a0a0a;
        }
        .success { background: #0a3d0a; border-color: #00ff00; }
        .error { background: #3d0a0a; border-color: #ff0000; }
        .info { background: #0a0a3d; border-color: #00ffff; }
        .warning { background: #3d3d0a; border-color: #ffff00; }
        h1, h2, h3 { color: #00ff00; border-bottom: 1px solid #00ff00; padding-bottom: 5px; }
        pre { 
            background: #0a0a0a; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto; 
            border: 1px solid #333;
            color: #00ffff;
        }
        .key { color: #ffff00; font-weight: bold; }
        .value { color: #00ffff; }
        .null { color: #ff0000; font-style: italic; }
        .btn {
            background: #00ff00;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { background: #00cc00; }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>🔍 Click Set Book - Debug Information</h1>
        
        <div class="section">
            <h3>🚀 Quick Actions</h3>
            <a href="signin.php" class="btn">Sign In</a>
            <a href="dashboard.php" class="btn">Dashboard</a>
            <a href="fill-profile.php" class="btn">Fill Profile</a>
            <a href="logout.php" class="btn">Logout</a>
            <a href="?" class="btn">Refresh Debug</a>
        </div>
        
        <div class="section info">
            <h3>📊 Session Information</h3>
            <?php if (empty($_SESSION)): ?>
                <p class="null">⚠️ Session is EMPTY</p>
            <?php else: ?>
                <p><span class="key">Session has data:</span> <span class="value"><?php echo count($_SESSION); ?> items</span></p>
                <pre><?php print_r($_SESSION); ?></pre>
            <?php endif; ?>
            <p style="margin-top: 15px;"><span class="key">Session ID:</span> <span class="value"><?php echo session_id() ?: 'NO SESSION ID'; ?></span></p>
            <p><span class="key">Session Status:</span> <span class="value"><?php 
                $status = session_status();
                echo $status == PHP_SESSION_ACTIVE ? 'ACTIVE' : ($status == PHP_SESSION_NONE ? 'NONE' : 'DISABLED');
            ?></span></p>
        </div>
        
        <div class="section <?php echo isLoggedIn() ? 'success' : 'error'; ?>">
            <h3>🔐 Login Status</h3>
            <p><span class="key">Is Logged In:</span> <span class="value"><?php echo isLoggedIn() ? '✅ YES' : '❌ NO'; ?></span></p>
            <?php if (isLoggedIn()): ?>
                <p><span class="key">Current User Data:</span></p>
                <pre><?php print_r(getCurrentUser()); ?></pre>
            <?php endif; ?>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <div class="section <?php 
                $currentUser = getCurrentUser();
                $auth = new Auth();
                $isComplete = $auth->isProfileComplete($currentUser['id']);
                echo $isComplete ? 'success' : 'warning'; 
            ?>">
                <h3>📝 Profile Completion Status</h3>
                <?php
                $fullUser = $auth->getUserById($currentUser['id']);
                
                echo '<p><span class="key">Profile Complete:</span> ';
                echo '<span class="value">' . ($isComplete ? '✅ YES' : '⚠️ NO - INCOMPLETE') . '</span></p>';
                
                if ($fullUser) {
                    echo '<div style="margin-top: 15px;"><span class="key">Required Profile Fields:</span></div>';
                    echo '<div style="margin-left: 20px;">';
                    
                    $fields = [
                        'date_of_birth' => 'Date of Birth',
                        'phone' => 'Phone',
                        'address' => 'Address'
                    ];
                    
                    foreach ($fields as $field => $label) {
                        $value = $fullUser[$field] ?? null;
                        $isEmpty = empty($value);
                        echo '<p>';
                        echo '<span class="key">' . $label . ':</span> ';
                        echo '<span class="' . ($isEmpty ? 'null' : 'value') . '">';
                        echo $isEmpty ? '❌ NULL/EMPTY' : '✅ ' . htmlspecialchars($value);
                        echo '</span>';
                        echo '</p>';
                    }
                    echo '</div>';
                    
                    echo '<div style="margin-top: 15px;"><span class="key">Full User Record:</span></div>';
                    echo '<pre>' . print_r($fullUser, true) . '</pre>';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="section info">
            <h3>🗄️ Database Connection Test</h3>
            <?php
            try {
                $pdo = getDBConnection();
                echo '<p style="color: #00ff00;">✅ Database connection successful</p>';
                
                // Test users table
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo '<p><span class="key">📊 Total users in database:</span> <span class="value">' . $result['count'] . '</span></p>';
                
                // Show all users with their profile status
                $stmt = $pdo->query("SELECT id, username, email, role, first_name, last_name, date_of_birth, phone, address FROM users");
                $users = $stmt->fetchAll();
                
                if (count($users) > 0) {
                    echo '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
                    echo '<tr style="border-bottom: 1px solid #00ff00;">';
                    echo '<th style="text-align: left; padding: 5px; color: #ffff00;">ID</th>';
                    echo '<th style="text-align: left; padding: 5px; color: #ffff00;">Email</th>';
                    echo '<th style="text-align: left; padding: 5px; color: #ffff00;">Name</th>';
                    echo '<th style="text-align: left; padding: 5px; color: #ffff00;">Role</th>';
                    echo '<th style="text-align: left; padding: 5px; color: #ffff00;">Profile Complete</th>';
                    echo '</tr>';
                    
                    foreach ($users as $user) {
                        $profileComplete = !empty($user['date_of_birth']) && !empty($user['phone']) && !empty($user['address']);
                        echo '<tr style="border-bottom: 1px solid #333;">';
                        echo '<td style="padding: 5px;">' . $user['id'] . '</td>';
                        echo '<td style="padding: 5px;">' . htmlspecialchars($user['email']) . '</td>';
                        echo '<td style="padding: 5px;">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
                        echo '<td style="padding: 5px;">' . htmlspecialchars($user['role']) . '</td>';
                        echo '<td style="padding: 5px; color: ' . ($profileComplete ? '#00ff00' : '#ff0000') . ';">';
                        echo $profileComplete ? '✅ YES' : '❌ NO';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
            } catch (Exception $e) {
                echo '<p style="color: #ff0000;">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <div class="section warning">
            <h3>⚙️ PHP Configuration</h3>
            <p><span class="key">PHP Version:</span> <span class="value"><?php echo phpversion(); ?></span></p>
            <p><span class="key">Session Save Path:</span> <span class="value"><?php echo session_save_path(); ?></span></p>
            <p><span class="key">Session ID:</span> <span class="value"><?php echo session_id(); ?></span></p>
            <p><span class="key">Error Reporting:</span> <span class="value"><?php echo error_reporting(); ?></span></p>
            <p><span class="key">Display Errors:</span> <span class="value"><?php echo ini_get('display_errors') ? 'ON' : 'OFF'; ?></span></p>
            <p><span class="key">Log Errors:</span> <span class="value"><?php echo ini_get('log_errors') ? 'ON' : 'OFF'; ?></span></p>
            <p><span class="key">Error Log File:</span> <span class="value"><?php 
                $logFile = ini_get('error_log');
                echo !empty($logFile) ? $logFile : 'Not configured';
            ?></span></p>
        </div>
        
        <div class="section info">
            <h3>📋 Recent Error Log (Last 30 Lines)</h3>
            <pre style="max-height: 400px; overflow-y: auto;"><?php
            $logFile = ini_get('error_log');
            if (empty($logFile) || $logFile == 'syslog') {
                // Try common locations
                $possibleLogs = [
                    __DIR__ . '/php_errors.log',
                    __DIR__ . '/../logs/error.log',
                    'C:/xampp/php/logs/php_error_log',
                    '/var/log/php_errors.log'
                ];
                
                foreach ($possibleLogs as $log) {
                    if (file_exists($log)) {
                        $logFile = $log;
                        break;
                    }
                }
            }
            
            if (!empty($logFile) && file_exists($logFile)) {
                $lines = file($logFile);
                $lastLines = array_slice($lines, -30);
                echo htmlspecialchars(implode('', $lastLines));
                echo "\n\n--- End of log (showing last 30 lines) ---";
            } else {
                echo "❌ Error log file not found.\n";
                echo "Searched for: " . htmlspecialchars($logFile) . "\n\n";
                echo "To enable error logging, add this to your php.ini:\n";
                echo "error_log = " . __DIR__ . "/php_errors.log\n";
                echo "log_errors = On\n";
                echo "display_errors = On";
            }
            ?></pre>
        </div>
        
        <div class="section info">
            <h3>🧪 Test Flow Instructions</h3>
            <ol style="line-height: 2;">
                <li><strong>Create New Account:</strong> Go to <a href="create-account.php">create-account.php</a> and register</li>
                <li><strong>Check Redirect:</strong> Should redirect to <code>signin.php</code> with success message</li>
                <li><strong>Login:</strong> Enter your credentials on <code>signin.php</code></li>
                <li><strong>Expected Flow:</strong> signin.php → dashboard.php → (if profile incomplete) → fill-profile.php</li>
                <li><strong>Check This Page:</strong> Refresh debug.php to see session and profile status</li>
                <li><strong>Check Error Log:</strong> Look for lines starting with "=== LOGIN DEBUG ===" above</li>
            </ol>
        </div>
        
        <div class="section success">
            <h3>✅ What to Look For</h3>
            <ul style="line-height: 2;">
                <li>Session Data should show: user_id, email, logged_in = 1</li>
                <li>Login Status should show: ✅ YES</li>
                <li>Profile Completion should show which fields are missing</li>
                <li>Error log should show the debug trace from signin.php through dashboard.php</li>
                <li>If stuck, error log will show where the redirect chain breaks</li>
            </ul>
        </div>
    </div>
</body>
</html>
