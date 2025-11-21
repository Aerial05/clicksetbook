<?php
/**
 * Quick Database Schema Checker
 * Checks if profile_image column exists in users table
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Check if profile_image column exists
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasProfileImage = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'profile_image') {
            $hasProfileImage = true;
            break;
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Schema Check</title>
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
            }
            .ok {
                color: #059669;
                font-weight: 600;
                font-size: 20px;
            }
            .error {
                color: #dc2626;
                font-weight: 600;
                font-size: 20px;
            }
            .sql-box {
                background: #1f2937;
                color: #f3f4f6;
                padding: 16px;
                border-radius: 8px;
                font-family: 'Courier New', monospace;
                font-size: 14px;
                margin: 16px 0;
                overflow-x: auto;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 16px;
            }
            th, td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
            }
            th {
                background: #f9fafb;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>🗄️ Database Schema Check</h1>
            
            <?php if ($hasProfileImage): ?>
                <p class="ok">✓ profile_image column exists in users table</p>
                <p>Your database is ready for profile picture uploads!</p>
            <?php else: ?>
                <p class="error">✗ profile_image column is MISSING from users table</p>
                <p><strong>Action Required:</strong> Run the SQL migration below in your database:</p>
                
                <div class="sql-box">
ALTER TABLE `users` <br>
ADD COLUMN `profile_image` varchar(255) DEFAULT NULL AFTER `last_login`;
                </div>
                
                <p><strong>How to run this:</strong></p>
                <ol>
                    <li>Open phpMyAdmin (usually at <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>)</li>
                    <li>Select your database (<?php echo DB_NAME; ?>)</li>
                    <li>Click on "SQL" tab</li>
                    <li>Paste the SQL above and click "Go"</li>
                </ol>
            <?php endif; ?>
            
            <h2>Current Users Table Structure:</h2>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Default</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $column): ?>
                        <tr style="<?php echo $column['Field'] === 'profile_image' ? 'background: #d1fae5;' : ''; ?>">
                            <td><?php echo htmlspecialchars($column['Field']); ?></td>
                            <td><?php echo htmlspecialchars($column['Type']); ?></td>
                            <td><?php echo htmlspecialchars($column['Null']); ?></td>
                            <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    echo '<div class="card">';
    echo '<h1>❌ Database Error</h1>';
    echo '<p class="error">Failed to connect to database</p>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>
