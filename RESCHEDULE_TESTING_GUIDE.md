# Reschedule Feature - Testing Guide

## 🧪 Complete Testing Checklist

### **PHASE 7: TESTING & VERIFICATION**

---

## **Test 1: User Reschedule Request (User Side)**

### Test 1.1: Submit Valid Reschedule Request
**Steps:**
1. Login as a regular user
2. Go to "My Bookings" page
3. Find an upcoming appointment (status: pending or confirmed)
4. Click "Reschedule" button
5. Select a future date and time
6. Enter a reason (e.g., "Doctor's appointment conflict")
7. Click "Confirm Reschedule"

**Expected Results:**
✅ Success message appears
✅ Modal closes
✅ Page refreshes and shows:
   - Orange "Reschedule Pending" badge appears
   - Yellow gradient box showing:
     * "⏳ Waiting for Admin Approval"
     * Current Date vs Requested Date comparison
     * Reason displayed
   - Reschedule button becomes **disabled** and grayed out
   - Button text changes to "Reschedule Pending"

**Database Verification:**
```sql
SELECT 
    id, 
    appointment_date, 
    requested_date, 
    reschedule_request, 
    reschedule_status, 
    reschedule_reason 
FROM appointments 
WHERE id = [your_appointment_id];
```
Should show:
- `reschedule_request = 1`
- `reschedule_status = 'pending'`
- `requested_date` = your new date
- `reschedule_reason` = your reason

---

### Test 1.2: Duplicate Reschedule Request Prevention
**Steps:**
1. With an appointment that already has a pending reschedule
2. Try to click "Reschedule" again

**Expected Results:**
✅ Button is **disabled** (grayed out, cursor: not-allowed)
✅ Cannot open reschedule modal

---

### Test 1.3: Past Date Validation
**Steps:**
1. Open browser developer console
2. Try to submit a reschedule with a past date via API:
```javascript
fetch('/api/manage-booking.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        action: 'reschedule',
        id: [appointment_id],
        requested_date: '2025-11-01',
        requested_time: '10:00:00'
    })
})
```

**Expected Results:**
✅ Error message: "Invalid date/time or date is in the past"
✅ Database not updated

---

## **Test 2: Admin Dashboard Statistics**

### Test 2.1: Pending Reschedules Counter
**Steps:**
1. Login as admin
2. Go to Admin Dashboard (overview page)
3. Look for "Reschedule Requests" stat card

**Expected Results:**
✅ Card shows correct count of pending reschedules
✅ Orange gradient icon (🔄)
✅ If count > 0: **Pulsing orange dot** appears in top-right corner
✅ Card is **clickable**

**Database Verification:**
```sql
SELECT COUNT(*) as pending_reschedules
FROM appointments 
WHERE reschedule_request = 1 AND reschedule_status = 'pending';
```
Count should match the displayed number.

---

### Test 2.2: Click Reschedule Stat Card
**Steps:**
1. Click on the "Reschedule Requests" card

**Expected Results:**
✅ Navigation switches to "Appointment Management" section
✅ Reschedule filter automatically set to "Pending Reschedules"
✅ Status filter cleared to "All Statuses"
✅ Page auto-scrolls to first reschedule request
✅ First reschedule card gets orange shadow highlight for 2 seconds

---

## **Test 3: Admin Reschedule Filter**

### Test 3.1: Filter by Pending Reschedules
**Steps:**
1. In Admin Dashboard → Appointment Management
2. Set "Reschedule Status" dropdown to "Pending Reschedules"

**Expected Results:**
✅ Only shows appointments with `reschedule_request=1` AND `reschedule_status='pending'`
✅ Each has orange "Reschedule" badge
✅ Each has yellow info box with original vs requested dates
✅ Each has "Approve" and "Decline" buttons

---

### Test 3.2: Filter by Approved Reschedules
**Steps:**
1. Set "Reschedule Status" dropdown to "Approved Reschedules"

**Expected Results:**
✅ Shows only appointments with `reschedule_status='approved'`
✅ Green "Reschedule Approved" badge visible
✅ `appointment_date` should be updated to the requested date

---

### Test 3.3: Filter by Declined Reschedules
**Steps:**
1. Set "Reschedule Status" dropdown to "Declined Reschedules"

**Expected Results:**
✅ Shows only appointments with `reschedule_status='declined'`
✅ Red "Reschedule Declined" badge visible
✅ Original `appointment_date` remains unchanged

---

## **Test 4: Admin Approve Reschedule**

### Test 4.1: Approve Valid Request
**Steps:**
1. Find appointment with pending reschedule
2. Note the original date/time and requested date/time
3. Click "Approve" button
4. Confirm in the dialog

**Expected Results:**
✅ Confirmation dialog shows: "Approve reschedule from [OLD] to [NEW]?"
✅ Success toast: "Reschedule request approved successfully!"
✅ Appointment disappears from pending reschedules list
✅ Badge changes to green "Reschedule Approved"

**Database Verification:**
```sql
SELECT 
    appointment_date, 
    appointment_time,
    requested_date,
    requested_time,
    reschedule_request,
    reschedule_status,
    reschedule_approved_by,
    reschedule_response_at
FROM appointments 
WHERE id = [appointment_id];
```
Should show:
- `appointment_date` = previous `requested_date`
- `appointment_time` = previous `requested_time`
- `reschedule_request = 0`
- `reschedule_status = 'approved'`
- `reschedule_approved_by` = admin user ID
- `reschedule_response_at` = current timestamp

**Appointment History Check:**
```sql
SELECT * FROM appointment_history 
WHERE appointment_id = [appointment_id] 
ORDER BY created_at DESC LIMIT 1;
```
Should have entry with:
- `new_status = 'reschedule_approved'`
- `change_reason` contains old and new date/time

---

### Test 4.2: User Sees Approved Reschedule
**Steps:**
1. As the user, go to "My Bookings"
2. Find the appointment that was approved

**Expected Results:**
✅ Green "Reschedule Approved" badge appears
✅ Appointment date/time shows the NEW date (what was requested)
✅ No yellow pending box
✅ Reschedule button is **enabled** again (can request another reschedule)

---

## **Test 5: Admin Decline Reschedule**

### Test 5.1: Decline with Reason
**Steps:**
1. Find appointment with pending reschedule
2. Click "Decline" button
3. Enter reason: "Doctor not available on that date"
4. Click OK in prompt
5. Confirm in dialog

**Expected Results:**
✅ Prompt asks for decline reason
✅ Confirmation dialog: "Keep original date [DATE]?"
✅ Success toast: "Reschedule request declined successfully!"
✅ Appointment removed from pending reschedules filter

**Database Verification:**
```sql
SELECT 
    appointment_date,
    requested_date,
    reschedule_request,
    reschedule_status,
    reschedule_reason,
    reschedule_approved_by,
    reschedule_response_at
FROM appointments 
WHERE id = [appointment_id];
```
Should show:
- `appointment_date` = **original date** (unchanged)
- `requested_date = NULL` (cleared)
- `reschedule_request = 0`
- `reschedule_status = 'declined'`
- `reschedule_reason` contains: "[original reason] [Declined: Doctor not available on that date]"
- `reschedule_approved_by` = admin user ID
- `reschedule_response_at` = current timestamp

---

### Test 5.2: User Sees Declined Reschedule
**Steps:**
1. As the user, go to "My Bookings"
2. Find the declined appointment

**Expected Results:**
✅ Red "Reschedule Declined" badge appears
✅ Red gradient box with:
   - "❌ Reschedule Request Declined"
   - "Your appointment remains on: [ORIGINAL DATE]"
   - "Admin's Response: Doctor not available on that date"
✅ Reschedule button is **enabled** (user can try another date)

---

### Test 5.3: Submit New Reschedule After Decline
**Steps:**
1. With a declined reschedule appointment
2. Click "Reschedule" button (should be enabled)
3. Select a different date and submit

**Expected Results:**
✅ New reschedule request submits successfully
✅ Status changes back to "pending"
✅ Old decline information is overwritten
✅ Reschedule button becomes disabled again

---

## **Test 6: Edge Cases & Error Handling**

### Test 6.1: No Reason Provided (Decline)
**Steps:**
1. Click "Decline" on a reschedule request
2. Leave reason blank or cancel the prompt

**Expected Results:**
✅ If blank: Error toast "Please provide a reason for declining"
✅ If cancelled: No action taken, dialog closes

---

### Test 6.2: Concurrent Admin Actions
**Steps:**
1. Open same appointment in two different admin browsers
2. Approve in first browser
3. Try to approve/decline in second browser

**Expected Results:**
✅ Second browser shows error: "No pending reschedule request found"
✅ Database prevents duplicate processing

---

### Test 6.3: Deleted/Invalid Appointment
**Steps:**
1. Try to approve non-existent appointment ID via API

**Expected Results:**
✅ Error message: "Appointment not found"
✅ No database changes

---

### Test 6.4: Multiple Reschedules on Same Appointment
**Steps:**
1. Submit reschedule → Admin approves
2. Submit another reschedule → Admin declines
3. Submit third reschedule → Admin approves

**Expected Results:**
✅ Each action properly updates the database
✅ History tracks all changes
✅ Final appointment_date reflects the last approved change

---

## **Test 7: UI/UX Verification**

### Test 7.1: Visual Design
**Checklist:**
- ✅ Orange gradient backgrounds are consistent
- ✅ Badge colors match status (orange/green/red)
- ✅ Strikethrough on original date when showing comparison
- ✅ Icons display correctly (calendar, check, X)
- ✅ Text is readable with good contrast
- ✅ Responsive on mobile devices

---

### Test 7.2: Button States
**Checklist:**
- ✅ Disabled buttons have opacity/cursor changes
- ✅ Hover effects work on enabled buttons
- ✅ Loading states during API calls (if implemented)
- ✅ No button double-click issues

---

## **Test 8: Performance & Security**

### Test 8.1: SQL Injection Prevention
**Steps:**
1. Try malicious input in reason field:
```
'; DROP TABLE appointments; --
```

**Expected Results:**
✅ Input is safely escaped/sanitized
✅ No SQL errors
✅ Database remains intact

---

### Test 8.2: Authorization Checks
**Steps:**
1. As User A, get appointment ID from User B
2. Try to reschedule User B's appointment via API

**Expected Results:**
✅ Error: "Booking not found" (fails ownership check)
✅ No unauthorized changes

---

### Test 8.3: Admin-Only Actions
**Steps:**
1. Logout from admin
2. Try to access approve/decline endpoints directly

**Expected Results:**
✅ Authentication error
✅ Redirect to login or access denied

---

## **Test 9: Database Integrity**

### Test 9.1: Check All Fields Populated
**Query:**
```sql
SELECT 
    id,
    appointment_date,
    appointment_time,
    reschedule_request,
    requested_date,
    requested_time,
    reschedule_reason,
    reschedule_status,
    reschedule_requested_at,
    reschedule_approved_by,
    reschedule_response_at
FROM appointments
WHERE reschedule_request = 1 OR reschedule_status IS NOT NULL;
```

**Verify:**
- ✅ No NULL values where they shouldn't be
- ✅ Timestamps are logical (requested_at before response_at)
- ✅ approved_by references valid admin user

---

### Test 9.2: Indexes Performance
**Query:**
```sql
EXPLAIN SELECT * FROM appointments 
WHERE reschedule_request = 1 AND reschedule_status = 'pending';
```

**Verify:**
- ✅ Uses `idx_reschedule_request` index
- ✅ Query executes quickly (< 100ms)

---

## **Test 10: Cross-Browser Compatibility**

Test on:
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari (if available)
- ✅ Mobile browsers (Chrome Mobile, Safari iOS)

**Check:**
- Date/time pickers work
- Modals display correctly
- Badges render properly
- Filters function correctly

---

## **📝 TESTING RESULTS TEMPLATE**

Use this to track your testing:

```
Date: ___________
Tester: ___________

[ ] Test 1.1: Submit Valid Reschedule - PASS/FAIL
[ ] Test 1.2: Duplicate Prevention - PASS/FAIL
[ ] Test 1.3: Past Date Validation - PASS/FAIL
[ ] Test 2.1: Counter Display - PASS/FAIL
[ ] Test 2.2: Stat Card Click - PASS/FAIL
[ ] Test 3.1: Filter Pending - PASS/FAIL
[ ] Test 3.2: Filter Approved - PASS/FAIL
[ ] Test 3.3: Filter Declined - PASS/FAIL
[ ] Test 4.1: Admin Approve - PASS/FAIL
[ ] Test 4.2: User Sees Approved - PASS/FAIL
[ ] Test 5.1: Admin Decline - PASS/FAIL
[ ] Test 5.2: User Sees Declined - PASS/FAIL
[ ] Test 5.3: New Reschedule After Decline - PASS/FAIL
[ ] Test 6.1: No Reason Error - PASS/FAIL
[ ] Test 6.2: Concurrent Actions - PASS/FAIL
[ ] Test 6.3: Invalid Appointment - PASS/FAIL
[ ] Test 6.4: Multiple Reschedules - PASS/FAIL
[ ] Test 7.1: Visual Design - PASS/FAIL
[ ] Test 7.2: Button States - PASS/FAIL
[ ] Test 8.1: SQL Injection - PASS/FAIL
[ ] Test 8.2: Authorization - PASS/FAIL
[ ] Test 8.3: Admin-Only - PASS/FAIL
[ ] Test 9.1: Database Fields - PASS/FAIL
[ ] Test 9.2: Index Performance - PASS/FAIL
[ ] Test 10: Cross-Browser - PASS/FAIL

Issues Found:
1. ___________________________________
2. ___________________________________
3. ___________________________________

Notes:
___________________________________
___________________________________
```

---

## **🚀 QUICK START TESTING**

**Minimal Critical Path:**
1. User submits reschedule request
2. Check My Bookings shows pending status
3. Admin sees in dashboard stats
4. Admin clicks stat card → filters correctly
5. Admin approves request
6. User sees approved status and updated date
7. Admin declines a different request
8. User sees declined status with reason

If all 8 steps work → **Core feature is functional** ✅

---

## **NEXT STEPS AFTER TESTING**

Once testing is complete:
- Fix any bugs found
- Document known issues
- Proceed to **Phase 6: Notifications** (if desired)
- OR mark feature as **COMPLETE** and deploy

---

*Happy Testing! 🧪*
