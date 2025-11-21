-- Sample Doctor Profile Images Update
-- This script demonstrates how to add profile images to doctors
-- In production, these will be uploaded via the admin panel

-- Note: Make sure the image files exist in uploads/doctors/ directory
-- For now, we'll set placeholder paths that admins can replace by uploading actual photos

-- Example: Update doctor with ID 1
-- UPDATE doctors SET profile_image = 'uploads/doctors/doctor_1_cardiologist.jpg' WHERE id = 1;

-- Example: Update doctor with ID 2
-- UPDATE doctors SET profile_image = 'uploads/doctors/doctor_2_gynecologist.jpg' WHERE id = 2;

-- Instructions for Admin:
-- 1. Go to Admin Dashboard → Doctors section
-- 2. Click the camera icon (📷) on each doctor card
-- 3. Select and upload a professional photo for each doctor
-- 4. The system will automatically resize and optimize the image
-- 5. Images are stored in uploads/doctors/ directory

-- Image Requirements:
-- - Format: JPG, PNG, GIF, or WebP
-- - Maximum size: 5MB
-- - Recommended dimensions: 400x400 pixels (square)
-- - File naming: doctor_{id}_{timestamp}.{extension}
