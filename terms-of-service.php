<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .content-section {
            margin-bottom: 32px;
        }
        
        .content-section h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 12px;
        }
        
        .content-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
            margin-top: 16px;
        }
        
        .content-section p {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 12px;
        }
        
        .content-section ul {
            margin-left: 20px;
            margin-bottom: 12px;
        }
        
        .content-section li {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 8px;
        }
        
        .last-updated {
            font-size: 13px;
            color: var(--text-light);
            font-style: italic;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <button onclick="window.history.back()" class="icon-btn">
                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Terms of Service</h1>
        </div>

        <div class="card">
            <p class="last-updated">Last Updated: October 8, 2025</p>

            <div class="content-section">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using Click Set Book's services, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to these terms, please do not use our services.</p>
            </div>

            <div class="content-section">
                <h2>2. Description of Service</h2>
                <p>Click Set Book provides medical laboratory services including appointment scheduling, test result viewing, and health information management. We reserve the right to modify or discontinue, temporarily or permanently, the service with or without notice.</p>
            </div>

            <div class="content-section">
                <h2>3. User Account</h2>
                <h3>Registration</h3>
                <p>You must register for an account to use certain features of our service. You agree to:</p>
                <ul>
                    <li>Provide accurate, current, and complete information during registration</li>
                    <li>Maintain and update your information to keep it accurate and current</li>
                    <li>Maintain the security of your password and identification</li>
                    <li>Accept all responsibility for all activities that occur under your account</li>
                    <li>Notify us immediately of any unauthorized use of your account</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>4. User Responsibilities</h2>
                <p>As a user of Click Set Book, you agree to:</p>
                <ul>
                    <li>Use the service only for lawful purposes</li>
                    <li>Not interfere with or disrupt the service or servers</li>
                    <li>Not attempt to gain unauthorized access to any portion of the service</li>
                    <li>Not use the service to transmit any harmful or malicious code</li>
                    <li>Provide accurate medical information for the safety of your care</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>5. Medical Disclaimer</h2>
                <p>The information provided through Click Set Book is for informational purposes only and does not constitute medical advice. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition.</p>
            </div>

            <div class="content-section">
                <h2>6. Appointments and Cancellations</h2>
                <p>You may schedule, reschedule, or cancel appointments through our platform. We reserve the right to charge cancellation fees for appointments cancelled less than 24 hours before the scheduled time.</p>
            </div>

            <div class="content-section">
                <h2>7. Privacy and Data Protection</h2>
                <p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect, use, and protect your personal and medical information. By using our services, you consent to our data practices as described in the Privacy Policy.</p>
            </div>

            <div class="content-section">
                <h2>8. Intellectual Property</h2>
                <p>All content included in or made available through our service, such as text, graphics, logos, images, and software, is the property of Click Set Book or its content suppliers and is protected by copyright and other intellectual property laws.</p>
            </div>

            <div class="content-section">
                <h2>9. Limitation of Liability</h2>
                <p>Click Set Book shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service. In no event shall our total liability exceed the amount paid by you for the services in the past twelve months.</p>
            </div>

            <div class="content-section">
                <h2>10. Termination</h2>
                <p>We may terminate or suspend your account and access to the service immediately, without prior notice or liability, for any reason, including breach of these Terms. Upon termination, your right to use the service will immediately cease.</p>
            </div>

            <div class="content-section">
                <h2>11. Changes to Terms</h2>
                <p>We reserve the right to modify these terms at any time. We will notify you of any changes by posting the new Terms of Service on this page. You are advised to review these terms periodically for any changes.</p>
            </div>

            <div class="content-section">
                <h2>12. Governing Law</h2>
                <p>These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which Click Set Book operates, without regard to its conflict of law provisions.</p>
            </div>

            <div class="content-section">
                <h2>13. Contact Information</h2>
                <p>If you have any questions about these Terms of Service, please contact us at:</p>
                <p><strong>Email:</strong> legal@clicksetbook.com<br>
                <strong>Address:</strong> Click Set Book, 123 Medical Center Drive, Suite 100</p>
            </div>
        </div>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>
</body>
</html>
