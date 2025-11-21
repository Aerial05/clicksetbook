-- Fix the users table - add AUTO_INCREMENT to id column
USE `u112535700_next_gen_db`;

-- First, drop the primary key constraint
ALTER TABLE `users` DROP PRIMARY KEY;

-- Add it back with AUTO_INCREMENT
ALTER TABLE `users` 
ADD PRIMARY KEY (`id`),
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Verify the change
SHOW CREATE TABLE `users`;
