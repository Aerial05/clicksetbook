<?php
/**
 * Email Notification Helper
 * Sends email notifications to users based on their preferences
 */

require_once __DIR__ . '/config/database.php';

class EmailNotification {
    private $pdo;
    private $fromEmail = 'noreply@clicksetbook.com';
    private $fromName = 'Click Set Book';
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Send appointment reminder email
     * @param int $userId User ID
     * @param array $appointmentData Appointment details
     * @return bool Success status
     */
    public function sendAppointmentReminder($userId, $appointmentData) {
        // Check if user has email notifications enabled
        if (!$this->hasEmailNotificationsEnabled($userId)) {
            return false;
        }
        
        // Get user email
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        
        $to = $user['email'];
        $subject = 'Appointment Reminder - Click Set Book';
        
        $message = $this->buildAppointmentReminderEmail($user, $appointmentData);
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    /**
     * Send appointment confirmation email
     * @param int $userId User ID
     * @param array $appointmentData Appointment details
     * @return bool Success status
     */
    public function sendAppointmentConfirmation($userId, $appointmentData) {
        if (!$this->hasEmailNotificationsEnabled($userId)) {
            return false;
        }
        
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        
        $to = $user['email'];
        $subject = 'Appointment Confirmation - Click Set Book';
        
        $message = $this->buildAppointmentConfirmationEmail($user, $appointmentData);
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    /**
     * Send test results notification
     * @param int $userId User ID
     * @param array $testData Test details
     * @return bool Success status
     */
    public function sendTestResultsNotification($userId, $testData) {
        if (!$this->hasEmailNotificationsEnabled($userId)) {
            return false;
        }
        
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        
        $to = $user['email'];
        $subject = 'Test Results Available - Click Set Book';
        
        $message = $this->buildTestResultsEmail($user, $testData);
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    /**
     * Check if user has email notifications enabled
     * @param int $userId User ID
     * @return bool
     */
    private function hasEmailNotificationsEnabled($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT email_notifications FROM user_preferences WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Default to true if no preferences set
            if (!$prefs) {
                return true;
            }
            
            return (int)$prefs['email_notifications'] === 1;
        } catch (PDOException $e) {
            error_log('Error checking email preferences: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|null User data
     */
    private function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching user: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Build appointment reminder email HTML
     */
    private function buildAppointmentReminderEmail($user, $appointmentData) {
        $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        $date = htmlspecialchars($appointmentData['date'] ?? 'N/A');
        $time = htmlspecialchars($appointmentData['time'] ?? 'N/A');
        $service = htmlspecialchars($appointmentData['service'] ?? 'N/A');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Appointment Reminder</h1>
                </div>
                <div class='content'>
                    <p>Hi {$name},</p>
                    <p>This is a reminder about your upcoming appointment at Click Set Book.</p>
                    
                    <div class='info-box'>
                        <strong>Appointment Details:</strong><br>
                        <strong>Service:</strong> {$service}<br>
                        <strong>Date:</strong> {$date}<br>
                        <strong>Time:</strong> {$time}
                    </div>
                    
                    <p>Please arrive 10 minutes early to complete any necessary paperwork.</p>
                    
                    <a href='http://yourdomain.com/dashboard.php' class='button'>View Appointment</a>
                    
                    <p>If you need to reschedule or cancel, please contact us as soon as possible.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Click Set Book. All rights reserved.</p>
                    <p>You received this email because you have email notifications enabled in your account preferences.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build appointment confirmation email HTML
     */
    private function buildAppointmentConfirmationEmail($user, $appointmentData) {
        $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        $date = htmlspecialchars($appointmentData['date'] ?? 'N/A');
        $time = htmlspecialchars($appointmentData['time'] ?? 'N/A');
        $service = htmlspecialchars($appointmentData['service'] ?? 'N/A');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
                .success { color: #10b981; font-size: 20px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='success'>✓</div>
                    <h1>Appointment Confirmed</h1>
                </div>
                <div class='content'>
                    <p>Hi {$name},</p>
                    <p>Your appointment has been successfully scheduled!</p>
                    
                    <div class='info-box'>
                        <strong>Appointment Details:</strong><br>
                        <strong>Service:</strong> {$service}<br>
                        <strong>Date:</strong> {$date}<br>
                        <strong>Time:</strong> {$time}
                    </div>
                    
                    <p>We'll send you a reminder 24 hours before your appointment.</p>
                    
                    <a href='http://yourdomain.com/dashboard.php' class='button'>View My Appointments</a>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Click Set Book. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build test results email HTML
     */
    private function buildTestResultsEmail($user, $testData) {
        $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        $testName = htmlspecialchars($testData['name'] ?? 'N/A');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Test Results Available</h1>
                </div>
                <div class='content'>
                    <p>Hi {$name},</p>
                    <p>Your test results are now available to view.</p>
                    
                    <div class='info-box'>
                        <strong>Test:</strong> {$testName}
                    </div>
                    
                    <p>Please log in to your account to view your results. If you have any questions or concerns, please contact your healthcare provider.</p>
                    
                    <a href='http://yourdomain.com/dashboard.php' class='button'>View Results</a>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Click Set Book. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send email using PHP mail function
     * In production, use a service like SendGrid, AWS SES, or similar
     */
    private function sendEmail($to, $subject, $htmlMessage) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>" . "\r\n";
        $headers .= "Reply-To: {$this->fromEmail}" . "\r\n";
        
        // In production, replace with proper email service
        // For now, log the email instead of actually sending
        error_log("Email would be sent to: {$to}");
        error_log("Subject: {$subject}");
        
        // Uncomment to actually send emails (requires mail server configuration)
        // return mail($to, $subject, $htmlMessage, $headers);
        
        // For development, just return true
        return true;
    }
}

// Usage example:
// $emailNotif = new EmailNotification();
// $emailNotif->sendAppointmentReminder($userId, [
//     'date' => '2025-10-15',
//     'time' => '10:00 AM',
//     'service' => 'Blood Test'
// ]);
