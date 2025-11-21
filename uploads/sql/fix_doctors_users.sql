-- Fix existing doctors by creating user accounts for them
-- Run this SQL script in your next_gen_db database

-- Create user for Dr. John Doe (Cardiologist)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, is_active, email_verified)
VALUES ('john.doe', 'john.doe@clicksetbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'John', 'Doe', '+1-555-0101', 1, 1);

-- Update doctor record to link with user
UPDATE doctors SET user_id = LAST_INSERT_ID() WHERE id = 1;

-- Create user for Dr. Jane Smith (Gynecologist)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, is_active, email_verified)
VALUES ('jane.smith', 'jane.smith@clicksetbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Jane', 'Smith', '+1-555-0102', 1, 1);

-- Update doctor record to link with user
UPDATE doctors SET user_id = LAST_INSERT_ID() WHERE id = 2;

-- Create user for Dr. Michael Johnson (Orthopedic Surgeon)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, is_active, email_verified)
VALUES ('michael.johnson', 'michael.johnson@clicksetbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Michael', 'Johnson', '+1-555-0103', 1, 1);

-- Update doctor record to link with user
UPDATE doctors SET user_id = LAST_INSERT_ID() WHERE id = 3;

-- Create user for Dr. Emily Davis (Pediatrician)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, is_active, email_verified)
VALUES ('emily.davis', 'emily.davis@clicksetbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Emily', 'Davis', '+1-555-0104', 1, 1);

-- Update doctor record to link with user
UPDATE doctors SET user_id = LAST_INSERT_ID() WHERE id = 4;

-- Verify the fix
SELECT 
    d.id as doctor_id,
    d.user_id,
    u.username,
    u.email,
    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
    d.specialty,
    d.license_number
FROM doctors d
INNER JOIN users u ON d.user_id = u.id
ORDER BY d.id;
