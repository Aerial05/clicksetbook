<?php
require_once 'includes/auth.php';

// Test registration manually
$auth = new Auth();

$testData = [
    'username' => 'testuser3',
    'email' => 'jameslouisople3@gmail.com',
    'password' => '123456789',
    'firstName' => 'Test User2',
    'lastName' => '',
    'phone' => null,
    'dateOfBirth' => null
];

echo "<h1>Registration Debug Test</h1>";
echo "<pre>";

// Check if username exists (public method)
$usernameExists = $auth->usernameExists($testData['username']);
echo "Username exists check: " . ($usernameExists ? "YES" : "NO") . "\n\n";

// Try registration
echo "Attempting registration...\n";
$result = $auth->register(
    $testData['username'],
    $testData['email'],
    $testData['password'],
    $testData['firstName'],
    $testData['lastName'],
    $testData['phone'],
    $testData['dateOfBirth']
);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

echo "</pre>";

// Also check what's in the users table
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $stmt->fetch();
    echo "<p>Total users in database: " . $row['count'] . "</p>";
    
    // List all users
    $stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<h3>Last 5 users:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr><td>" . $user['id'] . "</td><td>" . $user['username'] . "</td><td>" . $user['email'] . "</td><td>" . $user['role'] . "</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking users: " . $e->getMessage() . "</p>";
}
?>
