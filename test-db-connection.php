<?php
/**
 * Database Connection Test Page
 * 
 * WARNING: DELETE THIS FILE AFTER TESTING IN PRODUCTION!
 * This file exposes sensitive database information.
 */

require_once 'config/database.php';

// Check if password parameter is provided for security
$accessPassword = 'test123'; // Change this!
$providedPassword = $_GET['password'] ?? '';

if ($providedPassword !== $accessPassword) {
    die('Access denied. Provide password in URL: ?password=' . $accessPassword);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1e3a8a;
            margin-bottom: 10px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .warning strong {
            color: #f59e0b;
            display: block;
            margin-bottom: 8px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        .section h2 {
            color: #333;
            margin-bottom: 16px;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            gap: 12px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 16px;
            padding: 10px;
            background: white;
            border-radius: 6px;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        .info-value {
            color: #111827;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin: 20px 0;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 12px;
        }
        .status.ok {
            background: #10b981;
            color: white;
        }
        .status.fail {
            background: #ef4444;
            color: white;
        }
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 12px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1e3a8a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 10px 10px 0;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #1e40af;
        }
        .log-section {
            margin-top: 30px;
        }
        pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Database Connection Test</h1>
        <p style="color: #6b7280; margin-bottom: 20px;">Testing database connectivity and configuration</p>
        
        <div class="warning">
            <strong>⚠️ SECURITY WARNING</strong>
            <p>This page exposes sensitive database information. Delete this file after testing in production!</p>
        </div>

        <?php
        // Test connection
        echo '<div class="section">';
        echo '<h2>📊 Connection Test Result</h2>';
        
        $testResult = testDBConnection();
        
        if ($testResult['success']) {
            echo '<div class="success">';
            echo '<strong>✅ Connection Successful!</strong><br>';
            echo 'Successfully connected to the database.<br>';
            echo 'Server Version: ' . htmlspecialchars($testResult['server_version']) . '<br>';
            echo 'Client Version: ' . htmlspecialchars($testResult['client_version']);
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<strong>❌ Connection Failed</strong><br>';
            echo 'Error: ' . htmlspecialchars($testResult['message']) . '<br>';
            echo 'Error Code: ' . htmlspecialchars($testResult['error_code']);
            echo '</div>';
        }
        echo '</div>';

        // Connection Info
        $info = getDBConnectionInfo();
        echo '<div class="section">';
        echo '<h2>⚙️ Configuration Details</h2>';
        echo '<div class="info-grid">';
        
        foreach ($info as $key => $value) {
            echo '<div class="info-row">';
            echo '<div class="info-label">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ':</div>';
            echo '<div class="info-value">';
            
            if ($key === 'pdo_mysql_available') {
                echo '<span class="status ' . ($value ? 'ok' : 'fail') . '">';
                echo $value ? '✓ Available' : '✗ Not Available';
                echo '</span>';
            } else if ($key === 'environment') {
                echo '<span class="status ' . ($value === 'PRODUCTION' ? 'fail' : 'ok') . '">';
                echo $value;
                echo '</span>';
            } else {
                echo htmlspecialchars($value);
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        // PHP Extensions
        echo '<div class="section">';
        echo '<h2>🔧 PHP Extensions</h2>';
        echo '<div class="info-grid">';
        
        $extensions = ['pdo', 'pdo_mysql', 'mysqli', 'mysqlnd'];
        foreach ($extensions as $ext) {
            echo '<div class="info-row">';
            echo '<div class="info-label">' . $ext . ':</div>';
            echo '<div class="info-value">';
            if (extension_loaded($ext)) {
                echo '<span class="status ok">✓ Loaded</span>';
            } else {
                echo '<span class="status fail">✗ Not Loaded</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        // Server Info
        echo '<div class="section">';
        echo '<h2>🖥️ Server Information</h2>';
        echo '<div class="info-grid">';
        
        $serverInfo = [
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'PHP Version' => PHP_VERSION,
            'Operating System' => PHP_OS,
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        ];
        
        foreach ($serverInfo as $label => $value) {
            echo '<div class="info-row">';
            echo '<div class="info-label">' . htmlspecialchars($label) . ':</div>';
            echo '<div class="info-value">' . htmlspecialchars($value) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        // Hostinger Specific Tips
        if (IS_PRODUCTION) {
            echo '<div class="section">';
            echo '<h2>💡 Hostinger Deployment Tips</h2>';
            echo '<ul style="line-height: 2; color: #374151;">';
            echo '<li><strong>Database Host:</strong> Use "localhost" (not 127.0.0.1 or IP address)</li>';
            echo '<li><strong>Database Name:</strong> Usually format: username_dbname (e.g., u112535700_next_gen_db)</li>';
            echo '<li><strong>Username:</strong> Usually format: username_user (e.g., u112535700_ngd)</li>';
            echo '<li><strong>Create Database:</strong> cPanel → MySQL Databases → Create New Database</li>';
            echo '<li><strong>Create User:</strong> cPanel → MySQL Databases → Add New User</li>';
            echo '<li><strong>Grant Privileges:</strong> cPanel → MySQL Databases → Add User To Database</li>';
            echo '<li><strong>Import SQL:</strong> cPanel → phpMyAdmin → Import your .sql file</li>';
            echo '</ul>';
            echo '</div>';
        }

        // Connection Log
        $logFile = __DIR__ . '/logs/db_connection.log';
        if (file_exists($logFile)) {
            echo '<div class="log-section">';
            echo '<h2>📋 Recent Connection Logs</h2>';
            $logs = file_get_contents($logFile);
            $logsArray = explode("\n[", $logs);
            $recentLogs = array_slice($logsArray, -3); // Last 3 connection attempts
            
            echo '<pre>';
            echo htmlspecialchars(implode("\n[", $recentLogs));
            echo '</pre>';
            
            echo '<a href="?password=' . urlencode($accessPassword) . '&action=clear" class="btn" onclick="return confirm(\'Clear all logs?\')">';
            echo '🗑️ Clear Logs';
            echo '</a>';
            echo '</div>';
            
            // Clear logs action
            if (isset($_GET['action']) && $_GET['action'] === 'clear') {
                file_put_contents($logFile, '');
                echo '<meta http-equiv="refresh" content="0;url=?password=' . urlencode($accessPassword) . '">';
            }
        }
        ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
            <a href="dashboard.php" class="btn">← Back to Dashboard</a>
            <a href="?password=<?php echo urlencode($accessPassword); ?>&refresh=1" class="btn">🔄 Refresh Test</a>
        </div>

        <div style="margin-top: 20px; padding: 16px; background: #fef3c7; border-radius: 8px;">
            <strong style="color: #f59e0b;">⚠️ Remember:</strong>
            <p style="color: #92400e; margin-top: 8px;">Delete this file (<code>test-db-connection.php</code>) after testing in production!</p>
        </div>
    </div>
</body>
</html>
