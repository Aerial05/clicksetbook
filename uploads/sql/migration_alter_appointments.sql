-- Alter appointments table to add missing columns and update enum
-- This script updates the existing appointments table

USE `u112535700_next_gen_db`;

-- First, update the status enum to include 'deleted'
ALTER TABLE `appointments` 
MODIFY `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show','deleted') DEFAULT 'pending';

-- Columns already exist - no need to add them
-- The table already has: cancel_request, reschedule_request, cancel_reason, cancel_details, cancel_requested_at

-- Update the view to include the new status enum value
DROP VIEW IF EXISTS `appointment_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointment_details`  AS SELECT `a`.`id` AS `id`, `a`.`appointment_number` AS `appointment_number`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`end_time` AS `end_time`, `a`.`status` AS `status`, `a`.`patient_name` AS `patient_name`, `a`.`patient_email` AS `patient_email`, `a`.`patient_phone` AS `patient_phone`, `a`.`notes` AS `notes`, `a`.`total_cost` AS `total_cost`, `a`.`created_at` AS `created_at`, `s`.`name` AS `service_name`, `s`.`category` AS `service_category`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `doctor_name`, `d`.`specialty` AS `doctor_specialty` FROM (((`appointments` `a` left join `services` `s` on(`a`.`service_id` = `s`.`id`)) left join `doctors` `d` on(`a`.`doctor_id` = `d`.`id`)) left join `users` `u` on(`d`.`user_id` = `u`.`id`)) ;
