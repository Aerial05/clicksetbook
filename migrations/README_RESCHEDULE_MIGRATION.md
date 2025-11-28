# Database Migration: Reschedule Tracking System

## Overview
This migration adds comprehensive reschedule tracking fields to the `appointments` table, enabling a complete admin approval/decline workflow for user reschedule requests.

## Date
November 28, 2025

## Database
`u112535700_u112535700_` (Click Set Book)

---

## Changes Summary

### New Fields Added to `appointments` Table

| Field Name | Type | Null | Description |
|------------|------|------|-------------|
| `requested_date` | DATE | YES | New date requested by user for reschedule |
| `requested_time` | TIME | YES | New time requested by user for reschedule |
| `reschedule_reason` | TEXT | YES | Reason provided by user for rescheduling |
| `reschedule_requested_at` | DATETIME | YES | Timestamp when reschedule was requested |
| `reschedule_approved_by` | INT(11) | YES | Foreign key to users table (admin who responded) |
| `reschedule_response_at` | DATETIME | YES | Timestamp when admin responded |
| `reschedule_status` | ENUM | YES | Status: 'pending', 'approved', 'declined' |

### Indexes Added
- `idx_reschedule_request` - Composite index on (`reschedule_request`, `reschedule_status`)
- `idx_reschedule_dates` - Composite index on (`requested_date`, `requested_time`)

### Foreign Key Constraint
- `fk_reschedule_approved_by` - Links to `users.id` with CASCADE update and SET NULL on delete

### View Updated
- `appointment_details` - Updated to include new reschedule tracking fields

---

## How to Apply Migration

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin in your browser: http://localhost/phpmyadmin
2. Select database: `u112535700_u112535700_`
3. Go to "SQL" tab
4. Copy contents from `migrations/add_reschedule_fields.sql`
5. Paste and click "Go"
6. Verify success message

### Option 2: Using MySQL Command Line
```bash
mysql -u root -p u112535700_u112535700_ < migrations/add_reschedule_fields.sql
```

### Option 3: Using PowerShell (XAMPP)
```powershell
cd C:\xampp\htdocs\clicksetbook
C:\xampp\mysql\bin\mysql.exe -u root -p u112535700_u112535700_ < migrations/add_reschedule_fields.sql
```

---

## Workflow After Migration

### User Reschedule Request Flow
1. User selects new date/time in reschedule modal
2. System saves to:
   - `requested_date` ← new date
   - `requested_time` ← new time
   - `reschedule_reason` ← user's reason
   - `reschedule_requested_at` ← NOW()
   - `reschedule_request` ← 1
   - `reschedule_status` ← 'pending'
3. Original `appointment_date` and `appointment_time` remain unchanged
4. Status shows "Reschedule Pending" in UI

### Admin Approval Flow
1. Admin sees appointment with reschedule badge
2. Admin clicks "Approve Reschedule"
3. System updates:
   - `appointment_date` ← `requested_date`
   - `appointment_time` ← `requested_time`
   - `reschedule_status` ← 'approved'
   - `reschedule_request` ← 0
   - `reschedule_approved_by` ← admin user_id
   - `reschedule_response_at` ← NOW()
   - Clear requested fields (NULL)
4. Notification sent to user: "Reschedule Approved"

### Admin Decline Flow
1. Admin sees appointment with reschedule badge
2. Admin clicks "Decline Reschedule"
3. System updates:
   - `reschedule_status` ← 'declined'
   - `reschedule_request` ← 0
   - `reschedule_approved_by` ← admin user_id
   - `reschedule_response_at` ← NOW()
   - Clear requested fields (NULL)
   - Keep original `appointment_date` and `appointment_time`
4. Notification sent to user: "Reschedule Declined"

---

## Rollback Instructions

If you need to undo this migration:

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin
2. Select database: `u112535700_u112535700_`
3. Go to "SQL" tab
4. Copy contents from `migrations/rollback_reschedule_fields.sql`
5. Paste and click "Go"

### Option 2: Using MySQL Command Line
```bash
mysql -u root -p u112535700_u112535700_ < migrations/rollback_reschedule_fields.sql
```

### Option 3: Using PowerShell (XAMPP)
```powershell
cd C:\xampp\htdocs\clicksetbook
C:\xampp\mysql\bin\mysql.exe -u root -p u112535700_u112535700_ < migrations/rollback_reschedule_fields.sql
```

---

## Testing Queries

After migration, test with these queries:

### Check if fields were added
```sql
DESCRIBE appointments;
```

### Check indexes
```sql
SHOW INDEX FROM appointments WHERE Key_name LIKE '%reschedule%';
```

### Check foreign key
```sql
SELECT 
    CONSTRAINT_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'appointments' 
AND CONSTRAINT_NAME = 'fk_reschedule_approved_by';
```

### Check view
```sql
SELECT * FROM appointment_details LIMIT 1;
```

### Test reschedule workflow
```sql
-- Simulate user reschedule request
UPDATE appointments 
SET reschedule_request = 1,
    requested_date = '2025-12-05',
    requested_time = '14:00:00',
    reschedule_reason = 'Schedule conflict',
    reschedule_requested_at = NOW(),
    reschedule_status = 'pending'
WHERE id = 1;

-- Check result
SELECT 
    id, 
    appointment_date, 
    appointment_time, 
    requested_date, 
    requested_time, 
    reschedule_status,
    reschedule_request
FROM appointments 
WHERE id = 1;
```

---

## Impact Analysis

### Data Impact
- **Existing Data**: Not affected (all new columns are NULL by default)
- **New Appointments**: New columns will be NULL until reschedule is requested
- **Backward Compatibility**: ✅ Fully compatible with existing code

### Performance Impact
- **Indexes Added**: Minimal impact, improves query performance for reschedule filtering
- **View Updated**: No performance impact
- **Foreign Key**: Minimal constraint checking overhead

### Dependencies
- Requires `users` table (for foreign key constraint)
- Requires existing `reschedule_request` column (already exists)

---

## Next Steps (Implementation Phases)

✅ **Phase 1: Database** - COMPLETED (this migration)
- [x] Add reschedule tracking fields
- [x] Create indexes for performance
- [x] Update view
- [x] Add foreign key constraint

⏳ **Phase 2: Backend API** - NEXT
- [ ] Update `api/manage-booking.php` (user reschedule action)
- [ ] Update `api/admin/appointments.php` (approve/decline actions)
- [ ] Add notification triggers

⏳ **Phase 3: Frontend - User**
- [ ] Connect reschedule modal to API
- [ ] Show reschedule pending status
- [ ] Display requested date/time

⏳ **Phase 4: Frontend - Admin**
- [ ] Show reschedule requests in appointments list
- [ ] Add approve/decline buttons
- [ ] Create reschedule detail modal

⏳ **Phase 5: Notifications**
- [ ] Reschedule request notification (optional)
- [ ] Reschedule approved notification
- [ ] Reschedule declined notification

---

## Support

If you encounter any issues:
1. Check the error message in MySQL/phpMyAdmin
2. Verify database name is correct
3. Ensure you have proper permissions
4. Try the rollback script if needed
5. Check the testing queries above

## Author
Click Set Book Development Team
Date: November 28, 2025
