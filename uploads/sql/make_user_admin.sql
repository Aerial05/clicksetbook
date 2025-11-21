-- Make jameslouisople2@gmail.com an admin
USE `u112535700_next_gen_db`;

UPDATE `users` 
SET `role` = 'admin' 
WHERE `email` = 'jameslouisople2@gmail.com';

-- Verify the update
SELECT `id`, `email`, `username`, `role`, `first_name`, `last_name` 
FROM `users` 
WHERE `email` = 'jameslouisople2@gmail.com';
