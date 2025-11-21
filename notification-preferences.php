<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .settings-section {
            margin-bottom: 24px;
        }
        .settings-section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 16px;
        }
        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-info {
            flex: 1;
        }
        .setting-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 4px;
        }
        .setting-desc {
            font-size: 13px;
            color: var(--text-light);
        }
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 28px;
            background: #d1d5db;
            border-radius: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-switch.active {
            background: var(--primary-color);
        }
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch.active::after {
            transform: translateX(20px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <button onclick="window.history.back()" class="icon-btn">
                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Notification Preferences</h1>
        </div>

        <!-- Notification Preferences -->
        <div class="settings-section">
            <h2 class="settings-section-title">Notification Preferences</h2>
            <div class="card" style="padding: 0;">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-title">Email Notifications</div>
                        <div class="setting-desc">Receive appointment reminders via email</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSetting(this, 'email_notifications')"></div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-title">Appointment Reminders</div>
                        <div class="setting-desc">Remind me 24 hours before appointments</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSetting(this, 'appointment_reminders')"></div>
                </div>
            </div>
        </div>

        <div style="height: 80px;"></div>
    </div>

    <?php include 'includes/navigation.php'; ?>

    <script>
    // Helper to show a toast message
    function showToast(message) {
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text-color);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    async function loadPreferences() {
        try {
            const res = await fetch('api/get-preferences.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.success && data.preferences) {
                const prefs = data.preferences;
                mapPref('email_notifications', prefs.email_notifications);
                mapPref('appointment_reminders', prefs.appointment_reminders);
            }
        } catch (e) {
            console.error('Failed to load preferences', e);
        }
    }

    function mapPref(settingName, value) {
        const toggle = document.querySelector(`[onclick*="${settingName}"]`);
        if (!toggle) return;
        if (Number(value) === 1) toggle.classList.add('active');
        else toggle.classList.remove('active');
    }

    function getCurrentPrefs() {
        return {
            email_notifications: document.querySelector(`[onclick*="email_notifications"]`).classList.contains('active') ? 1 : 0,
            appointment_reminders: document.querySelector(`[onclick*="appointment_reminders"]`).classList.contains('active') ? 1 : 0
        };
    }

    async function savePreferences() {
        const prefs = getCurrentPrefs();
        try {
            const res = await fetch('api/update-preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(prefs)
            });
            const data = await res.json();
            if (data.success) {
                showToast('Preferences saved');
            } else {
                showToast('Failed to save preferences');
            }
        } catch (e) {
            console.error('Save preferences error', e);
            showToast('Failed to save preferences');
        }
    }

    function toggleSetting(element, settingName) {
        element.classList.toggle('active');
        // Save to backend
        savePreferences();
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPreferences();
    });
    </script>
</body>
</html>
