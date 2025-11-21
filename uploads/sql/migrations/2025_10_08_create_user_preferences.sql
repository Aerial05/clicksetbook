-- Migration: create user_preferences table
-- Run this in your database to add backend storage for notification preferences

CREATE TABLE IF NOT EXISTS user_preferences (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  email_notifications TINYINT(1) NOT NULL DEFAULT 1,
  sms_notifications TINYINT(1) NOT NULL DEFAULT 1,
  appointment_reminders TINYINT(1) NOT NULL DEFAULT 1,
  promotional_updates TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user (user_id),
  CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
