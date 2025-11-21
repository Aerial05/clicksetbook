/**
 * EmailJS Service
 * Handles all email sending functionality using EmailJS
 * 
 * Documentation: https://www.emailjs.com/docs/
 */

class EmailJSService {
    constructor(config) {
        this.config = config;
        this.initialized = false;
    }

    /**
     * Initialize EmailJS
     */
    async init() {
        console.log('init() called, initialized:', this.initialized);
        if (this.initialized) return true;

        try {
            console.log('init() - checking if emailjs is defined...');
            // Wait for EmailJS SDK to be available
            if (typeof emailjs === 'undefined') {
                console.log('init() - emailjs not defined, loading SDK...');
                await this.loadEmailJSSDK();
            } else {
                console.log('init() - emailjs already loaded');
            }

            // Wait a bit to ensure SDK is fully loaded
            console.log('init() - waiting for SDK to settle...');
            await new Promise(resolve => setTimeout(resolve, 100));

            // Verify emailjs is available
            if (typeof emailjs === 'undefined') {
                throw new Error('EmailJS SDK failed to load');
            }
            console.log('init() - emailjs confirmed available');

            // Initialize with public key using the correct method
            console.log('init() - calling emailjs.init with publicKey:', this.config.publicKey);
            emailjs.init({
                publicKey: this.config.publicKey
            });
            
            this.initialized = true;
            console.log('✓ EmailJS initialized successfully');
            return true;
        } catch (error) {
            console.error('✗ EmailJS initialization failed:', error);
            return false;
        }
    }

    /**
     * Load EmailJS SDK from CDN
     */
    loadEmailJSSDK() {
        return new Promise((resolve, reject) => {
            if (typeof emailjs !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Send email using EmailJS
     * @param {string} templateId - EmailJS template ID
     * @param {object} params - Template parameters
     * @returns {Promise} - EmailJS response
     */
    async send(templateId, params) {
        console.log('send() called - starting initialization...');
        await this.init();
        console.log('send() - initialization complete');

        try {
            console.log('send() - calling emailjs.send with:', {
                serviceId: this.config.serviceId,
                templateId: templateId,
                paramsKeys: Object.keys(params)
            });
            
            const response = await emailjs.send(
                this.config.serviceId,
                templateId,
                params
            );
            console.log('✓ Email sent successfully:', response);
            return { success: true, response };
        } catch (error) {
            console.error('✗ Email send failed:', error);
            console.error('Error details:', {
                text: error.text,
                status: error.status,
                message: error.message
            });
            return { success: false, error: error.text || error.message };
        }
    }

    /**
     * Send password reset email
     * @param {string} toEmail - Recipient email
     * @param {string} toName - Recipient name
     * @param {string} resetLink - Password reset link
     * @param {string} resetCode - Reset code (optional)
     */
    async sendPasswordResetEmail(toEmail, toName, resetLink, resetCode = '') {
        console.log('sendPasswordResetEmail() called with:', {
            toEmail,
            toName,
            resetCode,
            templateId: this.config.templates.passwordReset
        });
        
        const params = {
            to_email: toEmail,
            to_name: toName,
            from_name: this.config.app.name,
            reset_link: resetLink,
            reset_code: resetCode,
            app_name: this.config.app.name,
            support_email: this.config.app.supportEmail,
            // Also include versions without underscores (some templates use these)
            reply_to: toEmail,
            user_email: toEmail,
            name: toName
        };

        console.log('sendPasswordResetEmail() - calling send()...');
        const result = await this.send(this.config.templates.passwordReset, params);
        console.log('sendPasswordResetEmail() - send() returned:', result);
        return result;
    }

    /**
     * Send appointment confirmation email
     * @param {string} toEmail - Recipient email
     * @param {string} toName - Recipient name
     * @param {object} appointment - Appointment details
     */
    async sendAppointmentConfirmation(toEmail, toName, appointment) {
        const params = {
            to_email: toEmail,
            to_name: toName,
            from_name: this.config.app.name,
            appointment_date: appointment.date,
            appointment_time: appointment.time,
            appointment_type: appointment.type || 'Appointment',
            appointment_location: appointment.location || 'TBD',
            appointment_notes: appointment.notes || '',
            app_name: this.config.app.name,
            app_url: this.config.app.url
        };

        return await this.send(this.config.templates.appointment, params);
    }

    /**
     * Send welcome email
     * @param {string} toEmail - Recipient email
     * @param {string} toName - Recipient name
     * @param {string} loginUrl - Login URL
     */
    async sendWelcomeEmail(toEmail, toName, loginUrl = '') {
        const params = {
            to_email: toEmail,
            to_name: toName,
            from_name: this.config.app.name,
            login_url: loginUrl || this.config.app.url,
            app_name: this.config.app.name,
            support_email: this.config.app.supportEmail
        };

        return await this.send(this.config.templates.welcome, params);
    }

    /**
     * Send test email
     * @param {string} toEmail - Recipient email
     * @param {string} message - Test message
     */
    async sendTestEmail(toEmail, message = 'This is a test email from EmailJS') {
        const params = {
            to_email: toEmail,
            to_name: 'Test User',
            from_name: this.config.app.name,
            message: message,
            app_name: this.config.app.name,
            test_time: new Date().toLocaleString()
        };

        return await this.send(this.config.templates.test, params);
    }

    /**
     * Check if EmailJS is configured
     */
    isConfigured() {
        return this.config.publicKey && 
               this.config.publicKey !== 'YOUR_PUBLIC_KEY_HERE' &&
               this.config.serviceId && 
               this.config.serviceId !== 'YOUR_SERVICE_ID_HERE';
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EmailJSService;
}
