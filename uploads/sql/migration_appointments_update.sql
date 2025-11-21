-- Migration: Add referrer and purpose fields to appointments table
-- Date: October 7, 2025

-- Add new fields to appointments table (check if they don't exist first)
SET @dbname = DATABASE();
SET @tablename = 'appointments';
SET @columnname = 'referrer';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `referrer` VARCHAR(200) DEFAULT NULL COMMENT "Who referred the patient or requested the service" AFTER `symptoms`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'appointment_purpose';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `appointment_purpose` TEXT DEFAULT NULL COMMENT "Purpose or reason for the appointment" AFTER `referrer`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add indexes for better query performance (skip if already exist)
CREATE INDEX IF NOT EXISTS `idx_status` ON `appointments`(`status`);
CREATE INDEX IF NOT EXISTS `idx_appointment_date` ON `appointments`(`appointment_date`);
CREATE INDEX IF NOT EXISTS `idx_patient_id` ON `appointments`(`patient_id`);

-- Create reviews table for completed appointments
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` INT(11) NOT NULL,
  `patient_id` INT(11) NOT NULL,
  `doctor_id` INT(11) DEFAULT NULL,
  `service_id` INT(11) NOT NULL,
  `rating` INT(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_text` TEXT DEFAULT NULL,
  `is_anonymous` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_appointment_review` (`appointment_id`),
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create favorites table for doctors and services
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `item_type` ENUM('doctor', 'service') NOT NULL,
  `item_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`user_id`, `item_type`, `item_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update notifications table to support user_id
ALTER TABLE `notifications`
ADD COLUMN `user_id` INT(11) DEFAULT NULL AFTER `id`,
ADD COLUMN `title` VARCHAR(255) DEFAULT NULL AFTER `user_id`,
ADD COLUMN `is_read` TINYINT(1) DEFAULT 0 AFTER `status`,
ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- Insert sample doctors (if needed for testing)
INSERT IGNORE INTO `doctors` (`user_id`, `license_number`, `specialty`, `department`, `qualification`, `experience_years`, `consultation_fee`, `bio`, `is_available`) VALUES
(NULL, 'MD-2021-001', 'Cardiologist', 'Cardiology', 'MD, FACC', 10, 150.00, 'Dr. John Doe is a dedicated cardiologist with expertise in heart disease prevention and treatment.', 1),
(NULL, 'MD-2021-002', 'Gynecologist', 'Obstetrics & Gynecology', 'MD, FACOG', 8, 120.00, 'Specializing in women\'s health with compassionate care.', 1),
(NULL, 'MD-2021-003', 'Orthopedic Surgeon', 'Orthopedics', 'MD, FAAOS', 12, 180.00, 'Expert in joint replacement and sports medicine.', 1),
(NULL, 'MD-2021-004', 'Pediatrician', 'Pediatrics', 'MD, FAAP', 6, 100.00, 'Providing quality healthcare for infants, children, and adolescents.', 1);

-- Insert sample services (imaging and more laboratory tests)
INSERT IGNORE INTO `services` (`name`, `description`, `category`, `duration_minutes`, `base_cost`, `requires_doctor`, `is_active`) VALUES
('Ultrasound', 'Non-invasive imaging used to visualize internal organs and monitor pregnancies', 'radiology', 30, 120.00, 0, 1),
('Pediatric Consultation', 'Expert medical care for infants, children, and adolescents', 'consultation', 30, 100.00, 1, 1),
('ECG (Electrocardiogram)', 'Test to check heart rhythm and electrical activity', 'laboratory', 15, 50.00, 0, 1),
('Urinalysis', 'Comprehensive urine test', 'laboratory', 10, 25.00, 0, 1);
