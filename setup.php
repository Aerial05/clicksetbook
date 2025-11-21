<?php
require_once 'config/database.php';

// Simple setup page to test database connection and provide instructions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Set Book - Setup</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .status {
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }
        .status-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .status-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .code-block {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1e40af;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Click Set Book - Database Setup</h1>
        
        <h2>Database Connection Test</h2>
        <?php
        try {
            $pdo = getDBConnection();
            echo '<div class="status status-success">';
            echo '<i class="fas fa-check-circle"></i> Database connection successful!';
            echo '</div>';
            
            // Test if tables exist
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="status status-success">';
                echo '<i class="fas fa-check-circle"></i> Users table exists!';
                echo '</div>';
                
                // Check if admin user exists
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                $result = $stmt->fetch();
                if ($result['count'] > 0) {
                    echo '<div class="status status-success">';
                    echo '<i class="fas fa-check-circle"></i> Admin user found!';
                    echo '</div>';
                } else {
                    echo '<div class="status status-error">';
                    echo '<i class="fas fa-exclamation-circle"></i> No admin user found. Please import the SQL file.';
                    echo '</div>';
                }
            } else {
                echo '<div class="status status-error">';
                echo '<i class="fas fa-exclamation-circle"></i> Users table not found. Please import the SQL file.';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status status-error">';
            echo '<i class="fas fa-exclamation-circle"></i> Database connection failed: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <h2>Setup Instructions</h2>
        <ol>
            <li>Make sure XAMPP is running (Apache and MySQL services)</li>
            <li>Create a database named <code>next_gen_db</code> in phpMyAdmin</li>
            <li>Import the SQL file <code>dfoxjlfx_click_set_book.sql</code> into the database</li>
            <li>Verify the connection settings in <code>config/database.php</code></li>
        </ol>
        
        <h3>Database Configuration</h3>
        <div class="code-block">
Host: <?php echo DB_HOST; ?><br>
Database: <?php echo DB_NAME; ?><br>
Username: <?php echo DB_USER; ?><br>
Password: <?php echo empty(DB_PASS) ? '(empty)' : '(set)'; ?>
        </div>
        
        <h3>Quick Links</h3>
        <p>
            <a href="index.php" class="btn">Go to App</a>
            <a href="signin.php" class="btn" style="margin-left: 12px;">Sign In</a>
            <a href="create-account.php" class="btn" style="margin-left: 12px;">Create Account</a>
        </p>
        
        <h3>Default Admin Credentials</h3>
        <div class="code-block">
Email: admin@clicksetbook.com<br>
Password: password
        </div>
        <p><small>Note: Change these credentials after first login!</small></p>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
