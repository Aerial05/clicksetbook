<?php
/**
 * EmailJS Configuration
 * 
 * EmailJS is a frontend email service that doesn't require PHP mail configuration.
 * Get your credentials from: https://www.emailjs.com/
 * 
 * Setup Steps:
 * 1. Sign up at https://www.emailjs.com/
 * 2. Create an Email Service (Gmail, Outlook, etc.)
 * 3. Create Email Templates
 * 4. Get your Public Key from Account page
 * 5. Update the constants below
 */

class EmailJSConfig {
    // EmailJS Account Settings
    // Get these from: https://dashboard.emailjs.com/
    const PUBLIC_KEY = '-oVtzEO0MwVsRZrO9';  // From Account > General
    const SERVICE_ID = 'service_vyxkeue';  // From Email Services
    
    // Template IDs for different email types
    // Create these templates in EmailJS Dashboard > Email Templates
    const TEMPLATE_PASSWORD_RESET = 'template_09pelsw';
    const TEMPLATE_APPOINTMENT_CONFIRMATION = 'template_osva7fb';
    const TEMPLATE_WELCOME = '';  // Not enabled
    const TEMPLATE_TEST = '';  // Not enabled
    
    // Application Settings
    const APP_NAME = 'Click Set Book';
    const APP_URL = 'http://localhost/clicksetbook';  // Update with your domain
    const SUPPORT_EMAIL = 'support@clicksetbook.com';  // Update with your email
    
    /**
     * Check if EmailJS is configured
     */
    public static function isConfigured() {
        return self::PUBLIC_KEY !== 'YOUR_PUBLIC_KEY_HERE' 
            && self::SERVICE_ID !== 'YOUR_SERVICE_ID_HERE';
    }
    
    /**
     * Get configuration for JavaScript
     */
    public static function getJSConfig() {
        return [
            'publicKey' => self::PUBLIC_KEY,
            'serviceId' => self::SERVICE_ID,
            'templates' => [
                'passwordReset' => self::TEMPLATE_PASSWORD_RESET,
                'appointment' => self::TEMPLATE_APPOINTMENT_CONFIRMATION,
                'welcome' => self::TEMPLATE_WELCOME,
                'test' => self::TEMPLATE_TEST
            ],
            'app' => [
                'name' => self::APP_NAME,
                'url' => self::APP_URL,
                'supportEmail' => self::SUPPORT_EMAIL
            ]
        ];
    }
    
    /**
     * Output configuration as JSON for JavaScript
     */
    public static function outputJSON() {
        header('Content-Type: application/json');
        echo json_encode(self::getJSConfig());
    }
}

/**
 * Helper function to include EmailJS configuration in pages
 */
function getEmailJSConfig() {
    return EmailJSConfig::getJSConfig();
}

/**
 * Helper function to check if EmailJS is ready
 */
function isEmailJSReady() {
    return EmailJSConfig::isConfigured();
}
