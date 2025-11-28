-- Rollback Migration: Remove Reschedule Tracking Fields from Appointments Table
-- Purpose: Undo the reschedule tracking migration if needed
-- Date: November 28, 2025

USE `u112535700_u112535700_`;

-- Drop indexes first
DROP INDEX IF EXISTS `idx_reschedule_request` ON `appointments`;
DROP INDEX IF EXISTS `idx_reschedule_dates` ON `appointments`;

-- Drop foreign key constraint
ALTER TABLE `appointments` DROP FOREIGN KEY IF EXISTS `fk_reschedule_approved_by`;

-- Remove reschedule tracking columns
ALTER TABLE `appointments` 
DROP COLUMN IF EXISTS `reschedule_status`,
DROP COLUMN IF EXISTS `reschedule_response_at`,
DROP COLUMN IF EXISTS `reschedule_approved_by`,
DROP COLUMN IF EXISTS `reschedule_requested_at`,
DROP COLUMN IF EXISTS `reschedule_reason`,
DROP COLUMN IF EXISTS `requested_time`,
DROP COLUMN IF EXISTS `requested_date`;

-- Restore original appointment_details view
DROP VIEW IF EXISTS `appointment_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointment_details` AS 
SELECT 
    `a`.`id` AS `id`, 
    `a`.`appointment_number` AS `appointment_number`, 
    `a`.`appointment_date` AS `appointment_date`, 
    `a`.`appointment_time` AS `appointment_time`, 
    `a`.`end_time` AS `end_time`, 
    `a`.`status` AS `status`, 
    `a`.`patient_name` AS `patient_name`, 
    `a`.`patient_email` AS `patient_email`, 
    `a`.`patient_phone` AS `patient_phone`, 
    `a`.`notes` AS `notes`, 
    `a`.`total_cost` AS `total_cost`, 
    `a`.`created_at` AS `created_at`,
    `s`.`name` AS `service_name`, 
    `s`.`category` AS `service_category`, 
    CONCAT(`u`.`first_name`, ' ', `u`.`last_name`) AS `doctor_name`, 
    `d`.`specialty` AS `doctor_specialty` 
FROM (((`appointments` `a` 
    LEFT JOIN `services` `s` ON(`a`.`service_id` = `s`.`id`)) 
    LEFT JOIN `doctors` `d` ON(`a`.`doctor_id` = `d`.`id`)) 
    LEFT JOIN `users` `u` ON(`d`.`user_id` = `u`.`id`));

-- Success message
SELECT 'Rollback completed successfully! Reschedule tracking fields removed from appointments table.' AS status;
