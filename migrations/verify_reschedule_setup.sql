-- ============================================
-- RESCHEDULE FEATURE VERIFICATION SCRIPT
-- ============================================
-- Run this to verify your database is properly set up
-- ============================================

-- Check if all reschedule columns exist
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'u112535700_u112535700_'
  AND TABLE_NAME = 'appointments'
  AND COLUMN_NAME IN (
      'requested_date',
      'requested_time',
      'reschedule_reason',
      'reschedule_requested_at',
      'reschedule_approved_by',
      'reschedule_response_at',
      'reschedule_status'
  )
ORDER BY ORDINAL_POSITION;

-- Expected Results: Should return 7 rows
-- ============================================

-- Check if indexes exist
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'u112535700_u112535700_'
  AND TABLE_NAME = 'appointments'
  AND INDEX_NAME IN ('idx_reschedule_request', 'idx_reschedule_dates')
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- Expected Results: Should return 4 rows (2 indexes with 2 columns each)
-- ============================================

-- Check if foreign key exists
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'u112535700_u112535700_'
  AND TABLE_NAME = 'appointments'
  AND CONSTRAINT_NAME = 'fk_reschedule_approved_by';

-- Expected Results: Should return 1 row
-- ============================================

-- Count current reschedule requests by status
SELECT 
    reschedule_status,
    COUNT(*) as count
FROM appointments
WHERE reschedule_request = 1 OR reschedule_status IS NOT NULL
GROUP BY reschedule_status;

-- This shows you current state of reschedule requests
-- ============================================

-- Sample reschedule appointments (if any exist)
SELECT 
    id,
    patient_name,
    appointment_date,
    appointment_time,
    requested_date,
    requested_time,
    reschedule_status,
    reschedule_reason,
    reschedule_requested_at
FROM appointments
WHERE reschedule_request = 1 OR reschedule_status IS NOT NULL
ORDER BY reschedule_requested_at DESC
LIMIT 5;

-- Shows your most recent reschedule activity
-- ============================================

-- Check appointment_history for reschedule logs
SELECT 
    ah.id,
    ah.appointment_id,
    ah.old_status,
    ah.new_status,
    ah.change_reason,
    ah.created_at,
    CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
FROM appointment_history ah
LEFT JOIN users u ON ah.changed_by = u.id
WHERE ah.new_status IN ('reschedule_approved', 'reschedule_declined', 'reschedule_pending')
   OR ah.change_reason LIKE '%rescheduled%'
ORDER BY ah.created_at DESC
LIMIT 10;

-- Shows recent reschedule-related history entries
-- ============================================

-- ✅ VERIFICATION CHECKLIST:
-- [ ] Query 1: Returns 7 columns (all reschedule fields exist)
-- [ ] Query 2: Returns 4 rows (both indexes exist)
-- [ ] Query 3: Returns 1 row (foreign key exists)
-- [ ] Query 4: Shows your current reschedule counts
-- [ ] Query 5: Shows sample reschedule appointments
-- [ ] Query 6: Shows reschedule history logs

-- If all checks pass, your database is ready! ✅
