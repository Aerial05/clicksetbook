<?php
/**
 * Database Configuration
 * 
 * For local development, use localhost settings
 * For production (Hostinger), uncomment the production settings
 */

// Production settings (Hostinger) - Uncomment for deployment
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'u112535700_next_gen_db');
// define('DB_USER', 'u112535700_ngd');
// define('DB_PASS', '#J3r3my890#');

// Local development settings - Comment out for production
define('DB_HOST', 'localhost');
define('DB_NAME', 'u112535700_u112535700_');
define('DB_USER', 'root');
define('DB_PASS', '');

// Environment detection
define('IS_LOCAL', DB_HOST === 'localhost' && DB_USER === 'root');
define('IS_PRODUCTION', !IS_LOCAL);

/**
 * Enhanced Database Connection with Detailed Logging
 */
function getDBConnection() {
    $logFile = __DIR__ . '/../logs/db_connection.log';
    $logsDir = __DIR__ . '/../logs';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }
    
    try {
        // Log connection attempt
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "\n[{$timestamp}] Database Connection Attempt\n";
        $logMessage .= "Environment: " . (IS_PRODUCTION ? "PRODUCTION" : "LOCAL") . "\n";
        $logMessage .= "Host: " . DB_HOST . "\n";
        $logMessage .= "Database: " . DB_NAME . "\n";
        $logMessage .= "User: " . DB_USER . "\n";
        
        // Build DSN
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $logMessage .= "DSN: " . $dsn . "\n";
        
        // Attempt connection
        $startTime = microtime(true);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10, // 10 second timeout
        ]);
        $endTime = microtime(true);
        $connectionTime = round(($endTime - $startTime) * 1000, 2);
        
        // Test connection
        $pdo->query('SELECT 1');
        
        // Log success
        $logMessage .= "Status: SUCCESS\n";
        $logMessage .= "Connection Time: {$connectionTime}ms\n";
        $logMessage .= "Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        $logMessage .= "Client Version: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "\n";
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Detailed error logging
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        $logMessage .= "Status: FAILED\n";
        $logMessage .= "Error Code: " . $errorCode . "\n";
        $logMessage .= "Error Message: " . $errorMessage . "\n";
        
        // Diagnose common issues
        $logMessage .= "\n--- Diagnostic Information ---\n";
        
        if (strpos($errorMessage, 'Access denied') !== false) {
            $logMessage .= "Issue: Authentication Failed\n";
            $logMessage .= "- Check username and password\n";
            $logMessage .= "- Verify user has permissions for database '{" . DB_NAME . "}'\n";
            $logMessage .= "- In Hostinger cPanel, check MySQL Databases section\n";
        }
        
        if (strpos($errorMessage, 'Unknown database') !== false) {
            $logMessage .= "Issue: Database Not Found\n";
            $logMessage .= "- Database '{" . DB_NAME . "}' does not exist\n";
            $logMessage .= "- Create database in Hostinger cPanel > MySQL Databases\n";
            $logMessage .= "- Import your SQL file to create tables\n";
        }
        
        if (strpos($errorMessage, "Can't connect") !== false || strpos($errorMessage, 'Connection refused') !== false) {
            $logMessage .= "Issue: Cannot Connect to MySQL Server\n";
            $logMessage .= "- Check if host '{" . DB_HOST . "}' is correct\n";
            $logMessage .= "- For Hostinger, use 'localhost' (not 127.0.0.1)\n";
            $logMessage .= "- Check if MySQL service is running\n";
            $logMessage .= "- Verify firewall settings\n";
        }
        
        if (strpos($errorMessage, 'timeout') !== false) {
            $logMessage .= "Issue: Connection Timeout\n";
            $logMessage .= "- Server may be overloaded\n";
            $logMessage .= "- Check network connectivity\n";
            $logMessage .= "- Contact Hostinger support if persists\n";
        }
        
        // PHP Configuration
        $logMessage .= "\n--- PHP Configuration ---\n";
        $logMessage .= "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
        $logMessage .= "PHP Version: " . PHP_VERSION . "\n";
        
        // Server Information
        $logMessage .= "\n--- Server Information ---\n";
        $logMessage .= "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
        $logMessage .= "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
        $logMessage .= "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "\n";
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Log to PHP error log as well
        error_log("Database connection failed: " . $errorMessage);
        
        // User-friendly error message
        if (IS_PRODUCTION) {
            die("Database connection failed. Please contact support. Error logged.");
        } else {
            die("Database connection failed: " . $errorMessage . "\n\nCheck logs/db_connection.log for details.");
        }
    }
}

/**
 * Test Database Connection with Detailed Report
 */
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        return [
            'success' => true,
            'message' => 'Database connection successful',
            'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'client_version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getCode()
        ];
    }
}

/**
 * Get Database Connection Details (for debugging)
 * WARNING: Remove or protect this in production!
 */
function getDBConnectionInfo() {
    return [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'user' => DB_USER,
        'environment' => IS_PRODUCTION ? 'PRODUCTION' : 'LOCAL',
        'pdo_mysql_available' => extension_loaded('pdo_mysql'),
        'php_version' => PHP_VERSION,
    ];
}
?>
