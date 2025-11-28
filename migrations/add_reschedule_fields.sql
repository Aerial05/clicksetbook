-- Migration: Add Reschedule Tracking Fields to Appointments Table
-- Purpose: Enable admin approval/decline workflow for user reschedule requests
-- Date: November 28, 2025

USE `u112535700_u112535700_`;

-- Add reschedule tracking fields to appointments table
ALTER TABLE `appointments` 
ADD COLUMN `requested_date` DATE NULL COMMENT 'New date requested by user for reschedule' AFTER `reschedule_request`,
ADD COLUMN `requested_time` TIME NULL COMMENT 'New time requested by user for reschedule' AFTER `requested_date`,
ADD COLUMN `reschedule_reason` TEXT NULL COMMENT 'Reason provided by user for rescheduling' AFTER `requested_time`,
ADD COLUMN `reschedule_requested_at` DATETIME NULL COMMENT 'When reschedule was requested' AFTER `reschedule_reason`,
ADD COLUMN `reschedule_approved_by` INT(11) NULL COMMENT 'Admin ID who approved/declined the reschedule' AFTER `reschedule_requested_at`,
ADD COLUMN `reschedule_response_at` DATETIME NULL COMMENT 'When admin responded to reschedule request' AFTER `reschedule_approved_by`,
ADD COLUMN `reschedule_status` ENUM('pending', 'approved', 'declined') NULL COMMENT 'Status of reschedule request' AFTER `reschedule_response_at`;

-- Add foreign key constraint for reschedule_approved_by
ALTER TABLE `appointments`
ADD CONSTRAINT `fk_reschedule_approved_by` 
FOREIGN KEY (`reschedule_approved_by`) REFERENCES `users`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add index for better query performance on reschedule requests
CREATE INDEX `idx_reschedule_request` ON `appointments`(`reschedule_request`, `reschedule_status`);
CREATE INDEX `idx_reschedule_dates` ON `appointments`(`requested_date`, `requested_time`);

-- Update the appointment_details view to include new reschedule fields
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
    `a`.`reschedule_request` AS `reschedule_request`,
    `a`.`requested_date` AS `requested_date`,
    `a`.`requested_time` AS `requested_time`,
    `a`.`reschedule_reason` AS `reschedule_reason`,
    `a`.`reschedule_status` AS `reschedule_status`,
    `s`.`name` AS `service_name`, 
    `s`.`category` AS `service_category`, 
    CONCAT(`u`.`first_name`, ' ', `u`.`last_name`) AS `doctor_name`, 
    `d`.`specialty` AS `doctor_specialty` 
FROM (((`appointments` `a` 
    LEFT JOIN `services` `s` ON(`a`.`service_id` = `s`.`id`)) 
    LEFT JOIN `doctors` `d` ON(`a`.`doctor_id` = `d`.`id`)) 
    LEFT JOIN `users` `u` ON(`d`.`user_id` = `u`.`id`));

-- Success message
SELECT 'Migration completed successfully! Reschedule tracking fields added to appointments table.' AS status;
