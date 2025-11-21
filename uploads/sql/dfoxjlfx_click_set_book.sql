-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 06, 2025 at 07:30 AM
-- Server version: 10.11.14-MariaDB-cll-lve
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dfoxjlfx_click_set_book`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appointment_number` varchar(20) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'pending',
  `patient_name` varchar(100) NOT NULL,
  `patient_email` varchar(100) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
  `patient_dob` date DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `booking_source` enum('online','phone','walk_in','admin') DEFAULT 'online',
  `total_cost` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','partial','cancelled') DEFAULT 'pending',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `confirmation_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `generate_appointment_number` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    DECLARE next_num INT;
    DECLARE date_part VARCHAR(8);
    
    SET date_part = DATE_FORMAT(NEW.appointment_date, '%Y%m%d');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(appointment_number, -4) AS UNSIGNED)), 0) + 1 
    INTO next_num 
    FROM appointments 
    WHERE appointment_number LIKE CONCAT('APT', date_part, '%');
    
    SET NEW.appointment_number = CONCAT('APT', date_part, LPAD(next_num, 4, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `appointment_details`
-- (See below for the actual view)
--
CREATE TABLE `appointment_details` (
`id` int(11)
,`appointment_number` varchar(20)
,`appointment_date` date
,`appointment_time` time
,`end_time` time
,`status` enum('pending','confirmed','in_progress','completed','cancelled','no_show')
,`patient_name` varchar(100)
,`patient_email` varchar(100)
,`patient_phone` varchar(20)
,`notes` text
,`total_cost` decimal(10,2)
,`created_at` timestamp
,`service_name` varchar(100)
,`service_category` enum('consultation','laboratory','radiology','physiotherapy','surgery','emergency')
,`doctor_name` varchar(101)
,`doctor_specialty` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_history`
--

CREATE TABLE `appointment_history` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `license_number` varchar(50) NOT NULL,
  `specialty` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `schedule_template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule_template`)),
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) DEFAULT 30,
  `is_available` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_services`
--

CREATE TABLE `doctor_services` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `custom_fee` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `recipient_email` varchar(100) DEFAULT NULL,
  `recipient_phone` varchar(20) DEFAULT NULL,
  `notification_type` enum('email','sms','both') NOT NULL,
  `template_type` enum('booking_confirmation','reminder','cancellation','reschedule','status_update') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message_content` text DEFAULT NULL,
  `status` enum('pending','sent','failed','bounced') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('consultation','laboratory','radiology','physiotherapy','surgery','emergency') NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `base_cost` decimal(10,2) DEFAULT NULL,
  `requires_doctor` tinyint(1) DEFAULT 1,
  `preparation_instructions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `category`, `duration_minutes`, `base_cost`, `requires_doctor`, `preparation_instructions`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General Consultation', 'General medical consultation and checkup', 'consultation', 30, 75.00, 1, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(2, 'Specialist Consultation', 'Consultation with medical specialist', 'consultation', 45, 150.00, 1, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(3, 'Blood Test - Basic Panel', 'Complete blood count and basic metabolic panel', 'laboratory', 15, 35.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(4, 'Blood Test - Comprehensive', 'Comprehensive metabolic panel with lipid profile', 'laboratory', 20, 65.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(5, 'X-Ray - Chest', 'Chest X-ray imaging', 'radiology', 20, 85.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(6, 'X-Ray - Extremity', 'X-ray of arm, leg, or joint', 'radiology', 15, 75.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(7, 'CT Scan', 'Computed tomography imaging', 'radiology', 30, 250.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(8, 'MRI Scan', 'Magnetic resonance imaging', 'radiology', 45, 450.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(9, 'Physical Therapy Session', 'Individual physiotherapy session', 'physiotherapy', 60, 85.00, 1, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(10, 'ECG/EKG', 'Electrocardiogram test', 'consultation', 15, 45.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(11, 'Ultrasound', 'Diagnostic ultrasound imaging', 'radiology', 30, 125.00, 0, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07'),
(12, 'Vaccination', 'Immunization services', 'consultation', 15, 25.00, 1, NULL, 1, '2025-09-27 16:27:07', '2025-09-27 16:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `updated_by`, `updated_at`) VALUES
(1, 'clinic_name', 'Click-Set-Book Healthcare', 'string', 'Name of the healthcare facility', 1, NULL, '2025-09-27 16:27:07'),
(2, 'clinic_phone', '+1-800-HEALTH', 'string', 'Main clinic phone number', 1, NULL, '2025-09-27 16:27:07'),
(3, 'clinic_email', 'info@clicksetbook.com', 'string', 'Main clinic email address', 1, NULL, '2025-09-27 16:27:07'),
(4, 'clinic_address', '123 Healthcare Ave, Medical District, City', 'string', 'Clinic physical address', 1, NULL, '2025-09-27 16:27:07'),
(5, 'appointment_reminder_hours', '24', 'integer', 'Hours before appointment to send reminder', 0, NULL, '2025-09-27 16:27:07'),
(6, 'max_appointments_per_day', '50', 'integer', 'Maximum appointments per day', 0, NULL, '2025-09-27 16:27:07'),
(7, 'booking_window_days', '30', 'integer', 'Days in advance patients can book', 1, NULL, '2025-09-27 16:27:07'),
(8, 'cancellation_window_hours', '24', 'integer', 'Minimum hours before appointment to allow cancellation', 1, NULL, '2025-09-27 16:27:07'),
(9, 'email_notifications_enabled', 'true', 'boolean', 'Enable email notifications', 0, NULL, '2025-09-27 16:27:07'),
(10, 'sms_notifications_enabled', 'true', 'boolean', 'Enable SMS notifications', 0, NULL, '2025-09-27 16:27:07'),
(11, 'auto_confirm_appointments', 'false', 'boolean', 'Automatically confirm appointments', 0, NULL, '2025-09-27 16:27:07'),
(12, 'working_hours_start', '08:00', 'string', 'Clinic opening time', 1, NULL, '2025-09-27 16:27:07'),
(13, 'working_hours_end', '17:00', 'string', 'Clinic closing time', 1, NULL, '2025-09-27 16:27:07'),
(14, 'working_days', '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', 'json', 'Days clinic is open', 1, NULL, '2025-09-27 16:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin','staff') DEFAULT 'patient',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `date_of_birth`, `address`, `emergency_contact`, `emergency_phone`, `created_at`, `updated_at`, `is_active`, `email_verified`, `last_login`) VALUES
(1, 'admin', 'admin@clicksetbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Administrator', '+1-800-ADMIN', NULL, NULL, NULL, NULL, '2025-09-27 16:27:07', '2025-09-27 16:27:07', 1, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_number` (`appointment_number`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `idx_appointment_number` (`appointment_number`),
  ADD KEY `idx_patient_email` (`patient_email`),
  ADD KEY `idx_doctor_date` (`doctor_id`,`appointment_date`),
  ADD KEY `idx_date_time` (`appointment_date`,`appointment_time`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_appointments_composite` (`appointment_date`,`appointment_time`,`status`),
  ADD KEY `idx_appointments_patient_lookup` (`patient_email`,`appointment_date`);

--
-- Indexes for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_appointment` (`appointment_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_specialty` (`specialty`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_available` (`is_available`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_date` (`doctor_id`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_doctor_schedules_composite` (`doctor_id`,`date`,`is_available`);

--
-- Indexes for table `doctor_services`
--
ALTER TABLE `doctor_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doctor_service` (`doctor_id`,`service_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_service` (`service_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointment` (`appointment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_history`
--
ALTER TABLE `appointment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_services`
--
ALTER TABLE `doctor_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Structure for view `appointment_details`
--
DROP TABLE IF EXISTS `appointment_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointment_details`  AS SELECT `a`.`id` AS `id`, `a`.`appointment_number` AS `appointment_number`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`end_time` AS `end_time`, `a`.`status` AS `status`, `a`.`patient_name` AS `patient_name`, `a`.`patient_email` AS `patient_email`, `a`.`patient_phone` AS `patient_phone`, `a`.`notes` AS `notes`, `a`.`total_cost` AS `total_cost`, `a`.`created_at` AS `created_at`, `s`.`name` AS `service_name`, `s`.`category` AS `service_category`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `doctor_name`, `d`.`specialty` AS `doctor_specialty` FROM (((`appointments` `a` left join `services` `s` on(`a`.`service_id` = `s`.`id`)) left join `doctors` `d` on(`a`.`doctor_id` = `d`.`id`)) left join `users` `u` on(`d`.`user_id` = `u`.`id`)) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_5` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD CONSTRAINT `appointment_history_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_services`
--
ALTER TABLE `doctor_services`
  ADD CONSTRAINT `doctor_services_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
