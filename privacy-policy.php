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
    <title>Privacy Policy - Click Set Book</title>
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
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Privacy Policy</h1>
        </div>

        <div class="card">
            <p class="last-updated">Last Updated: October 8, 2025</p>

            <div class="content-section">
                <h2>Introduction</h2>
                <p>Welcome to Click Set Book. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our application and tell you about your privacy rights and how the law protects you.</p>
            </div>

            <div class="content-section">
                <h2>Information We Collect</h2>
                <p>We may collect, use, store and transfer different kinds of personal data about you:</p>
                <ul>
                    <li><strong>Identity Data:</strong> First name, last name, username or similar identifier</li>
                    <li><strong>Contact Data:</strong> Email address, telephone numbers, and address</li>
                    <li><strong>Medical Data:</strong> Appointment history, test results, and related medical information</li>
                    <li><strong>Technical Data:</strong> IP address, browser type, device information</li>
                    <li><strong>Usage Data:</strong> Information about how you use our application</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>How We Use Your Information</h2>
                <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
                <ul>
                    <li>To provide and maintain our services</li>
                    <li>To manage your appointments and medical records</li>
                    <li>To send you appointment reminders and notifications</li>
                    <li>To improve our services and user experience</li>
                    <li>To comply with legal obligations</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>Data Security</h2>
                <p>We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed. We limit access to your personal data to those employees, agents, contractors and other third parties who have a business need to know.</p>
            </div>

            <div class="content-section">
                <h2>Data Retention</h2>
                <p>We will only retain your personal data for as long as necessary to fulfill the purposes we collected it for, including for the purposes of satisfying any legal, accounting, or reporting requirements.</p>
            </div>

            <div class="content-section">
                <h2>Your Rights</h2>
                <p>Under certain circumstances, you have rights under data protection laws in relation to your personal data:</p>
                <ul>
                    <li>Request access to your personal data</li>
                    <li>Request correction of your personal data</li>
                    <li>Request erasure of your personal data</li>
                    <li>Object to processing of your personal data</li>
                    <li>Request restriction of processing your personal data</li>
                    <li>Request transfer of your personal data</li>
                    <li>Right to withdraw consent</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>Third-Party Links</h2>
                <p>This application may include links to third-party websites, plug-ins and applications. Clicking on those links or enabling those connections may allow third parties to collect or share data about you. We do not control these third-party websites and are not responsible for their privacy statements.</p>
            </div>

            <div class="content-section">
                <h2>Contact Us</h2>
                <p>If you have any questions about this privacy policy or our privacy practices, please contact us at:</p>
                <p><strong>Email:</strong> privacy@clicksetbook.com<br>
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
