-- Update Services with Actual Philippine Peso Prices
-- Based on typical Philippine medical service rates (2025)

UPDATE `services` SET `base_cost` = 500.00 WHERE `id` = 1;  -- General Consultation: ₱500
UPDATE `services` SET `base_cost` = 1200.00 WHERE `id` = 2; -- Specialist Consultation: ₱1,200
UPDATE `services` SET `base_cost` = 350.00 WHERE `id` = 3;  -- Blood Test - Basic Panel: ₱350
UPDATE `services` SET `base_cost` = 800.00 WHERE `id` = 4;  -- Blood Test - Comprehensive: ₱800
UPDATE `services` SET `base_cost` = 450.00 WHERE `id` = 5;  -- X-Ray - Chest: ₱450
UPDATE `services` SET `base_cost` = 400.00 WHERE `id` = 6;  -- X-Ray - Extremity: ₱400
UPDATE `services` SET `base_cost` = 3500.00 WHERE `id` = 7; -- CT Scan: ₱3,500
UPDATE `services` SET `base_cost` = 8000.00 WHERE `id` = 8; -- MRI Scan: ₱8,000
UPDATE `services` SET `base_cost` = 600.00 WHERE `id` = 9;  -- Physical Therapy Session: ₱600
UPDATE `services` SET `base_cost` = 300.00 WHERE `id` = 10; -- ECG/EKG: ₱300
UPDATE `services` SET `base_cost` = 800.00 WHERE `id` = 11; -- Ultrasound: ₱800
UPDATE `services` SET `base_cost` = 250.00 WHERE `id` = 12; -- Vaccination: ₱250
UPDATE `services` SET `base_cost` = 800.00 WHERE `id` = 13; -- Ultrasound (duplicate): ₱800
UPDATE `services` SET `base_cost` = 800.00 WHERE `id` = 14; -- Pediatric Consultation: ₱800
UPDATE `services` SET `base_cost` = 300.00 WHERE `id` = 15; -- ECG (Electrocardiogram): ₱300
UPDATE `services` SET `base_cost` = 150.00 WHERE `id` = 16; -- Urinalysis: ₱150

-- Update the SQL dump file with new prices
