-- Add profile_image column to users table
-- Run this SQL in your database to add profile picture support

ALTER TABLE `users` 
ADD COLUMN `profile_image` varchar(255) DEFAULT NULL AFTER `last_login`;

-- Optional: Add index for better performance
ALTER TABLE `users` 
ADD INDEX `idx_profile_image` (`profile_image`);
