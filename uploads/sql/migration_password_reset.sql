-- Password Reset System - Database Migration
-- Run this script if the password_resets table is not auto-created

-- Create password_resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL,
    `code` VARCHAR(5) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_code` (`code`),
    INDEX `idx_token` (`token`),
    INDEX `idx_used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Clean up expired and used reset requests older than 24 hours
-- Run this periodically via cron job
DELETE FROM `password_resets` 
WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Optional: Verify users table has required fields for profile completion
-- These should already exist, but you can verify:
-- ALTER TABLE `users` ADD COLUMN `date_of_birth` DATE DEFAULT NULL;
-- ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL;
-- ALTER TABLE `users` ADD COLUMN `address` TEXT DEFAULT NULL;

-- Check if fields exist (no changes will be made, just checking)
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM 
    INFORMATION_SCHEMA.COLUMNS
WHERE 
    TABLE_SCHEMA = 'next_gen_db' 
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME IN ('date_of_birth', 'phone', 'address');
