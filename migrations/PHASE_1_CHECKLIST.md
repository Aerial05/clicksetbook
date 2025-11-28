# ✅ PHASE 1: DATABASE MODIFICATIONS - COMPLETION CHECKLIST

## Status: READY TO DEPLOY

---

## 📁 Files Created

### Migration Files
- ✅ `migrations/add_reschedule_fields.sql` - Main migration script
- ✅ `migrations/rollback_reschedule_fields.sql` - Rollback script
- ✅ `migrations/README_RESCHEDULE_MIGRATION.md` - Complete documentation
- ✅ `migrations/setup_migration.ps1` - PowerShell setup script
- ✅ `migrations/run_migration.bat` - Quick batch launcher

### Updated Files
- ✅ `next_gen_db.sql` - Updated with new fields for fresh installations

---

## 🗄️ Database Changes Summary

### New Fields Added (7 columns)
| Field | Type | Purpose |
|-------|------|---------|
| `requested_date` | DATE | Stores user's requested new date |
| `requested_time` | TIME | Stores user's requested new time |
| `reschedule_reason` | TEXT | User's reason for rescheduling |
| `reschedule_requested_at` | DATETIME | When request was made |
| `reschedule_approved_by` | INT(11) | Admin who processed request |
| `reschedule_response_at` | DATETIME | When admin responded |
| `reschedule_status` | ENUM | pending/approved/declined |

### Indexes Added (2)
- ✅ `idx_reschedule_request` - On (reschedule_request, reschedule_status)
- ✅ `idx_reschedule_dates` - On (requested_date, requested_time)

### Foreign Key Added (1)
- ✅ `fk_reschedule_approved_by` → users(id)

### View Updated (1)
- ✅ `appointment_details` - Includes new reschedule fields

---

## 🚀 How to Apply Migration

### Option 1: Using PowerShell Script (Recommended)
```powershell
cd C:\xampp\htdocs\clicksetbook
.\migrations\run_migration.bat
```
Then select option 1 to apply migration.

### Option 2: Using phpMyAdmin (Manual)
1. Open http://localhost/phpmyadmin
2. Select database: `u112535700_u112535700_`
3. Click "SQL" tab
4. Open `migrations/add_reschedule_fields.sql` in text editor
5. Copy all content
6. Paste into SQL box
7. Click "Go"
8. Look for success message

### Option 3: Using MySQL Command Line
```bash
cd C:\xampp\htdocs\clicksetbook
C:\xampp\mysql\bin\mysql.exe -u root -p u112535700_u112535700_ < migrations/add_reschedule_fields.sql
```

---

## ✅ Verification Steps

After running migration, verify:

### 1. Check Fields Were Added
```sql
DESCRIBE appointments;
```
Look for the 7 new reschedule fields.

### 2. Check Indexes
```sql
SHOW INDEX FROM appointments WHERE Key_name LIKE '%reschedule%';
```
Should show 2 new indexes.

### 3. Check Foreign Key
```sql
SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'appointments' 
AND CONSTRAINT_NAME = 'fk_reschedule_approved_by';
```

### 4. Test Data Insertion
```sql
UPDATE appointments 
SET reschedule_request = 1,
    requested_date = '2025-12-05',
    requested_time = '14:00:00',
    reschedule_reason = 'Test reschedule request',
    reschedule_requested_at = NOW(),
    reschedule_status = 'pending'
WHERE id = (SELECT MIN(id) FROM (SELECT id FROM appointments) AS tmp);
```

---

## 🔄 If Something Goes Wrong

### Rollback the Migration
```powershell
.\migrations\run_migration.bat
```
Then select option 2 to rollback.

Or manually in phpMyAdmin:
- Copy contents of `migrations/rollback_reschedule_fields.sql`
- Execute in SQL tab

---

## 📋 Next Phase Checklist

Once Phase 1 is complete, proceed to:

### Phase 2: Backend API Development
- [ ] Update `api/manage-booking.php` - Add reschedule action
- [ ] Update `api/admin/appointments.php` - Add approve/decline actions
- [ ] Add notification triggers

### Phase 3: Frontend - User Side
- [ ] Connect reschedule modal to API
- [ ] Show pending status indicator
- [ ] Display requested date/time

### Phase 4: Frontend - Admin Side
- [ ] Display reschedule requests
- [ ] Add approve/decline buttons
- [ ] Create reschedule detail modal

### Phase 5: Notifications
- [ ] Reschedule approved notification
- [ ] Reschedule declined notification

---

## 📊 Expected Workflow After Migration

### User Requests Reschedule
```
Appointment #19
Original: Nov 23, 2025 @ 2:00 PM
↓ User requests change via modal
Requested: Nov 30, 2025 @ 3:30 PM
Status: Reschedule Pending (reschedule_request = 1)
```

### Admin Approves
```
Admin clicks "Approve"
↓
appointment_date → Nov 30, 2025
appointment_time → 3:30 PM
reschedule_status → 'approved'
reschedule_request → 0
User gets notification: "Reschedule Approved"
```

### Admin Declines
```
Admin clicks "Decline"
↓
appointment_date → Nov 23, 2025 (unchanged)
appointment_time → 2:00 PM (unchanged)
reschedule_status → 'declined'
reschedule_request → 0
User gets notification: "Reschedule Declined"
```

---

## ✅ Phase 1 Complete When...

- [x] Migration files created
- [x] Documentation written
- [x] Setup scripts ready
- [ ] **Migration executed successfully** ← DO THIS NOW
- [ ] **Verification tests passed** ← THEN THIS
- [ ] **Ready for Phase 2** ← THEN PROCEED

---

## 🎯 Current Status

**PHASE 1: READY TO EXECUTE**

All preparation work is complete. You can now:
1. Run the migration script
2. Verify the database changes
3. Move to Phase 2 (Backend API)

**Estimated Time:** 5-10 minutes to apply and verify

---

Last Updated: November 28, 2025
