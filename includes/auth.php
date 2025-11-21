<?php
// Configure custom session path to avoid permission issues
if (session_status() === PHP_SESSION_NONE) {
    // Use project directory for session storage
    $sessionPath = __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    ini_set('session.save_path', $sessionPath);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// User authentication functions
class Auth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // Register a new user
    public function register($username, $email, $password, $firstName, $lastName, $phone = null, $dateOfBirth = null) {
        try {
            // Check if email already exists
            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Check if username already exists
            if ($this->usernameExists($username)) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, date_of_birth, created_at, updated_at, is_active, email_verified) 
                VALUES (?, ?, ?, 'patient', ?, ?, ?, ?, NOW(), NOW(), 1, 0)
            ");
            
            $executed = $stmt->execute([$username, $email, $passwordHash, $firstName, $lastName, $phone, $dateOfBirth]);
            
            if (!$executed) {
                $errorInfo = $stmt->errorInfo();
                error_log("SQL Error: " . json_encode($errorInfo));
                return ['success' => false, 'message' => 'Database error: ' . $errorInfo[2]];
            }
            
            $userId = $this->pdo->lastInsertId();
            
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
            
        } catch (PDOException $e) {
            error_log("Registration PDOException: " . $e->getMessage() . " Code: " . $e->getCode());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Registration Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    // Login user
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password_hash, role, first_name, last_name, phone, date_of_birth, is_active, email_verified 
                FROM users 
                WHERE email = ? AND is_active = 1
            ");
            
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Remove password hash from returned data
                unset($user['password_hash']);
                
                return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    // Update user profile
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'phone', 'date_of_birth', 'address', 'emergency_contact', 'emergency_phone'];
            $updateFields = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields) && $value !== null && $value !== '') {
                    $updateFields[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $values[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profile update failed'];
        }
    }
    
    // Check if email exists
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    // Check if username exists (public method for external use)
    public function usernameExists($username) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }
    
    // Update last login timestamp
    private function updateLastLogin($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    // Get user by ID
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, role, first_name, last_name, phone, date_of_birth, address, emergency_contact, emergency_phone, created_at, last_login, is_active, email_verified 
                FROM users 
                WHERE id = ? AND is_active = 1
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if user profile is complete
    public function isProfileComplete($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT date_of_birth, phone, address 
                FROM users 
                WHERE id = ?
            ");
            
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();
            
            if (!$profile) {
                return false;
            }
            
            // Profile is complete if date_of_birth, phone, and address are all filled
            $isComplete = !empty($profile['date_of_birth']) && 
                         !empty($profile['phone']) && 
                         !empty($profile['address']);
            
            return $isComplete;
            
        } catch (PDOException $e) {
            error_log("Profile check error: " . $e->getMessage());
            return false;
        }
    }
    
    // Create password reset request
    public function createPasswordReset($email) {
        try {
            // Check if email exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            // Generate 5-digit verification code
            $code = sprintf('%05d', mt_rand(0, 99999));
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Create password_resets table if it doesn't exist
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) NOT NULL,
                    code VARCHAR(5) NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_code (code),
                    INDEX idx_token (token)
                )
            ");
            
            // Delete old unused reset requests for this email
            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE email = ? AND used = 0");
            $stmt->execute([$email]);
            
            // Insert new reset request
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (email, code, token, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$email, $code, $token, $expiresAt]);
            
            // In production, send actual email here
            // For now, we'll just return the code for testing
            return [
                'success' => true, 
                'code' => $code, 
                'token' => $token,
                'message' => 'Verification code sent to your email'
            ];
            
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create reset request'];
        }
    }
    
    // Verify reset code
    public function verifyResetCode($email, $code) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, token, expires_at, used 
                FROM password_resets 
                WHERE email = ? AND code = ? AND used = 0
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$email, $code]);
            $reset = $stmt->fetch();
            
            if (!$reset) {
                return ['success' => false, 'message' => 'Invalid verification code'];
            }
            
            // Check if code is expired
            if (strtotime($reset['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verification code has expired'];
            }
            
            return [
                'success' => true, 
                'token' => $reset['token'],
                'message' => 'Code verified successfully'
            ];
            
        } catch (PDOException $e) {
            error_log("Verify code error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed'];
        }
    }
    
    // Reset password with token
    public function resetPassword($token, $newPassword) {
        try {
            // Find reset request by token
            $stmt = $this->pdo->prepare("
                SELECT id, email, expires_at, used 
                FROM password_resets 
                WHERE token = ? AND used = 0
            ");
            
            $stmt->execute([$token]);
            $reset = $stmt->fetch();
            
            if (!$reset) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            // Check if token is expired
            if (strtotime($reset['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Reset token has expired'];
            }
            
            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$passwordHash, $reset['email']]);
            
            // Mark reset request as used
            $stmt = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->execute([$reset['id']]);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (PDOException $e) {
            error_log("Reset password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
}

// Session management functions
function startUserSession($user) {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['logged_in'] = true;
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Get basic data from session
    $sessionUser = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name']
    ];
    
    // Fetch additional data from database
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, username, email, role, first_name, last_name, phone, 
                   date_of_birth, address, emergency_contact, emergency_phone,
                   profile_image, is_active, email_verified, 
                   created_at, last_login
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return database data if found, otherwise return session data
        return $dbUser ? $dbUser : $sessionUser;
    } catch (Exception $e) {
        // If database query fails, return session data
        error_log("getCurrentUser error: " . $e->getMessage());
        return $sessionUser;
    }
}

function logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear onboarding status from localStorage (will be handled by JavaScript)
}

function requireLogin() {
    error_log("=== requireLogin: Checking ===");
    error_log("isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false'));
    error_log("Session logged_in value: " . (isset($_SESSION['logged_in']) ? var_export($_SESSION['logged_in'], true) : 'NOT SET'));
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
    
    if (!isLoggedIn()) {
        error_log("NOT LOGGED IN - Redirecting to signin.php");
        header('Location: signin.php');
        exit();
    }
    error_log("Login check PASSED");
}

function requireRole($allowedRoles) {
    if (!isLoggedIn()) {
        header('Location: signin.php');
        exit();
    }
    
    $userRole = $_SESSION['role'];
    if (!in_array($userRole, $allowedRoles)) {
        // Redirect to appropriate dashboard based on role
        $currentUser = getCurrentUser();
        if ($currentUser) {
            switch ($currentUser['role']) {
                case 'admin':
                    header('Location: admin-dashboard.php');
                    break;
                case 'doctor':
                    header('Location: doctor-dashboard.php');
                    break;
                case 'staff':
                    header('Location: staff-dashboard.php');
                    break;
                default:
                    header('Location: dashboard.php');
            }
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }
}

function requireNotLoggedIn() {
    if (isLoggedIn()) {
        // Redirect logged-in users to their appropriate dashboard
        $currentUser = getCurrentUser();
        if ($currentUser) {
            switch ($currentUser['role']) {
                case 'admin':
                    header('Location: admin-dashboard.php');
                    break;
                case 'doctor':
                    header('Location: doctor-dashboard.php');
                    break;
                case 'staff':
                    header('Location: staff-dashboard.php');
                    break;
                default:
                    header('Location: dashboard.php');
            }
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }
}

// Check if profile is complete and redirect if necessary
function checkProfileAndRedirect() {
    if (!isLoggedIn()) {
        return;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        return;
    }
    
    $auth = new Auth();
    $isComplete = $auth->isProfileComplete($currentUser['id']);
    
    if (!$isComplete) {
        // Store current user info in session for profile completion
        $_SESSION['profile_incomplete'] = true;
        header('Location: fill-profile.php');
        exit();
    }
}

// Require profile to be complete
function requireProfileComplete() {
    if (!isLoggedIn()) {
        header('Location: signin.php');
        exit();
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        header('Location: signin.php');
        exit();
    }
    
    $auth = new Auth();
    $isComplete = $auth->isProfileComplete($currentUser['id']);
    
    if (!$isComplete) {
        $_SESSION['profile_incomplete'] = true;
        header('Location: fill-profile.php');
        exit();
    }
}
?>
