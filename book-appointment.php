<?php 
require_once 'includes/auth.php';
require_once 'config/emailjs.php';

requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();
$emailjs_config = getEmailJSConfig();

$type = $_GET['type'] ?? '';
$itemId = $_GET['id'] ?? 0;

if (!in_array($type, ['doctor', 'service'])) {
    header('Location: dashboard.php');
    exit();
}

// Fetch item details
if ($type == 'doctor') {
    $stmt = $pdo->prepare("SELECT COALESCE(CONCAT(u.first_name, ' ', u.last_name), CONCAT('Dr. ', d.specialty)) as name, 
                                  d.specialty, d.department 
                           FROM doctors d 
                           LEFT JOIN users u ON d.user_id = u.id 
                           WHERE d.id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemName = $item['name'] ?? 'Doctor';
    $itemSubtitle = $item['specialty'] ?? '';
} else {
    $stmt = $pdo->prepare("SELECT name, category FROM services WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemName = $item['name'] ?? '';
    $itemSubtitle = ucfirst($item['category'] ?? '');
}

if (!$item) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Appointment - Click Set Book</title>
<link rel="stylesheet" href="app-styles.css">
<link rel="stylesheet" href="book-appointment-styles.css">
<style>
    /* Override sidebar padding for this standalone page */
    body {
        padding-left: 0 !important;
    }
    
    /* Spinning animation for loading state */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Responsive button visibility */
    @media (max-width: 768px) {
        #bookBtnHeader {
            display: none !important;
        }
        #bookBtn {
            display: block !important;
        }
    }
</style>
</head>
<body>
<div class="container">

<!-- Header -->
<div style="padding: 24px 20px; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <button onclick="history.back()" style="background: white; border: 1px solid var(--border-color); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);" onmouseover="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.15)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.05)';">
            <svg style="width: 18px; height: 18px; color: var(--text-primary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </button>
        <h1 style="font-size: 20px; font-weight: 700; margin: 0; color: var(--text-primary); letter-spacing: -0.01em;">Book Appointment</h1>
    </div>
</div>

<!-- Content Wrapper -->
<div class="content-wrapper">

<!-- Item Info -->
<div style="padding: 20px; background: white; border-bottom: 1px solid var(--border-color); max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="width:60px;height:60px;background:var(--bg-secondary);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;">
                <?php echo $type=='doctor'?'👨‍⚕️':'🔬'; ?>
            </div>
            <div>
                <h3 style="font-size:16px;font-weight:700;margin:0 0 4px 0;"><?php echo htmlspecialchars($itemName);?></h3>
                <p style="font-size:14px;color:var(--text-light);margin:0;"><?php echo htmlspecialchars($itemSubtitle);?></p>
            </div>
        </div>
        <button id="bookBtnHeader" 
                disabled 
                style="padding: 12px 24px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border-radius: 12px; font-weight: 700; font-size: 15px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3); transition: all 0.2s ease; white-space: nowrap; display: flex; align-items: center; gap: 10px; min-width: 160px; justify-content: center;"
                onmouseover="if(!this.disabled) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(30, 58, 138, 0.4)'; }"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(30, 58, 138, 0.3)';">
            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <span>Book Now</span>
        </button>
    </div>
</div>

<!-- Purpose Section - Moved to top -->
<?php if($type=='service'): ?>
<div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-secondary); display: none;" id="purposeSection">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <svg style="width: 20px; height: 20px; color: var(--primary-color);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <h3 style="font-size:18px;font-weight:700;margin:0;color:var(--text-primary);">Appointment Purpose</h3>
        </div>
        <div style="position: relative;">
            <select id="servicePurpose" class="form-control" style="padding: 14px 40px 14px 16px; font-size: 15px; border: 2px solid var(--border-color); border-radius: 12px; background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%); cursor: pointer; appearance: none; width: 100%; transition: all 0.3s; font-weight: 500; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(-1px)';" onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(0)';">
                <option value="" style="color: #9ca3af; font-style: italic;">Select purpose...</option>
                <option value="Laboratory Test">🔬 Laboratory Test</option>
                <option value="Radiology/Imaging">📊 Radiology/Imaging</option>
                <option value="Physical Therapy">💪 Physical Therapy</option>
                <option value="Emergency Care">🚨 Emergency Care</option>
                <option value="Surgical Procedure">🏥 Surgical Procedure</option>
                <option value="Follow-up Care">📋 Follow-up Care</option>
                <option value="Other">📝 Other</option>
            </select>
            <svg style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-light); pointer-events: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </div>
</div>
<?php else: ?>
<div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-secondary); display: none;" id="doctorPurposeSection">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <svg style="width: 20px; height: 20px; color: var(--primary-color);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <h3 style="font-size:18px;font-weight:700;margin:0;color:var(--text-primary);">Appointment Purpose</h3>
        </div>
        <div style="position: relative;">
            <select id="doctorPurpose" class="form-control" style="padding: 14px 40px 14px 16px; font-size: 15px; border: 2px solid var(--border-color); border-radius: 12px; background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%); cursor: pointer; appearance: none; width: 100%; transition: all 0.3s; font-weight: 500; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(-1px)';" onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(0)';">
                <option value="" style="color: #9ca3af; font-style: italic;">Select reason...</option>
                <option value="General Consultation">👨‍⚕️ General Consultation</option>
                <option value="Follow-up Visit">📅 Follow-up Visit</option>
                <option value="New Symptoms">🩺 New Symptoms</option>
                <option value="Chronic Disease Management">💊 Chronic Disease Management</option>
                <option value="Prescription Refill">📋 Prescription Refill</option>
                <option value="Medical Certificate">📄 Medical Certificate</option>
                <option value="Second Opinion">🔍 Second Opinion</option>
                <option value="Pre-operative Consultation">🏥 Pre-operative Consultation</option>
                <option value="Post-operative Follow-up">✅ Post-operative Follow-up</option>
                <option value="Other">📝 Other</option>
            </select>
            <svg style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-light); pointer-events: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Calendar + Time Slots (Two Column) -->
<div class="calendar-time-wrapper">
    
    <!-- Time Slots (Left) - Always visible -->
    <div>
        <h3 style="font-size:18px;font-weight:700;margin-bottom:12px;color:var(--text-primary);">Available Time Slots</h3>
        <div id="timeSection" style="display:block;">
            <div class="time-slots-grid" id="timeSlotsGrid">
                <div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--text-light);">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px; opacity: 0.3;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <div style="font-size: 14px;">Select a date to view available time slots</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar (Right) -->
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="font-size:18px;font-weight:700;margin:0;color:var(--text-primary);">Select Date</h3>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <button onclick="previousMonth()" style="background:var(--bg-tertiary);border:none;width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <span id="currentMonth" style="font-weight:700;font-size:16px;"></span>
            <button onclick="nextMonth()" style="background:var(--bg-tertiary);border:none;width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        <div class="calendar-grid" id="calendarGrid"></div>
    </div>
</div>

<!-- Book Button -->
<div style="display: none; position: sticky; bottom: 0; background: linear-gradient(to top, white 90%, rgba(255,255,255,0)); padding: 20px 0 24px 0; margin-top: 20px; z-index: 100; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);">
    <div style="max-width: min(600px, 90vw); margin: 0 auto; padding: 0 20px;">
        <button id="bookBtn" 
                class="btn btn-primary btn-block btn-lg btn-book-main" 
                disabled 
                style="display: block; width: 100%; padding: 14px; background: var(--primary-color); color: white; text-align: center; border-radius: 12px; font-weight: 600; font-size: 15px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); transition: all 0.2s ease;"
                onmouseover="if(!this.disabled) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(79, 70, 229, 0.4)'; }"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(79, 70, 229, 0.3)';">
            Book Appointment
        </button>
    </div>
</div>

</div>

<script>
let currentDate = new Date();
let selectedDate = null;
let selectedTime = null;
const today = new Date();
today.setHours(0,0,0,0);
const maxBookingDate = new Date();
maxBookingDate.setMonth(maxBookingDate.getMonth() + 6);
maxBookingDate.setHours(23,59,59,999);

function renderCalendar(){
    const grid=document.getElementById('calendarGrid');
    const monthDisplay=document.getElementById('currentMonth');
    const year=currentDate.getFullYear();
    const month=currentDate.getMonth();
    monthDisplay.textContent=currentDate.toLocaleDateString('en-US',{month:'long',year:'numeric'});

    const firstDay=new Date(year,month,1).getDay();
    const daysInMonth=new Date(year,month+1,0).getDate();
    const todayCheck=new Date(); todayCheck.setHours(0,0,0,0);

    grid.innerHTML='';
    const dayNames=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    dayNames.forEach(name=>{
        const h=document.createElement('div');
        h.style.cssText='font-size:10px;font-weight:700;color:var(--text-light);text-align:center;padding:8px 0;';
        h.textContent=name; grid.appendChild(h);
    });
    for(let i=0;i<firstDay;i++) grid.appendChild(document.createElement('div'));
    for(let day=1;day<=daysInMonth;day++){
        const dayDate=new Date(year,month,day);
        dayDate.setHours(0,0,0,0);
        const el=document.createElement('div');
        el.className='calendar-day';

        if(dayDate < todayCheck || dayDate > maxBookingDate) {
            el.classList.add('disabled');
        } else {
            el.addEventListener('click',()=>selectDate(dayDate,el));
        }

        el.innerHTML=`<div class="day-name">${dayNames[dayDate.getDay()]}</div><div class="day-number">${day}</div>`;
        grid.appendChild(el);
    }
}

async function loadTimeSlots(){
    const grid=document.getElementById('timeSlotsGrid');
    grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-light);">Loading...</div>';
    try{
        const year=selectedDate.getFullYear();
        const month=String(selectedDate.getMonth()+1).padStart(2,'0');
        const day=String(selectedDate.getDate()).padStart(2,'0');
        const dateStr=`${year}-${month}-${day}`;
        const url = '<?php echo $type;?>'==='doctor'
            ? `api/get-home-data.php?endpoint=available-slots&doctor_id=<?php echo $itemId;?>&date=${dateStr}`
            : `api/get-home-data.php?endpoint=available-slots&service_id=<?php echo $itemId;?>&date=${dateStr}`;
        const resp=await fetch(url);
        const data=await resp.json();
        grid.innerHTML='';

        if(data.success && data.slots.length>0){
            const now = new Date();
            const isToday = selectedDate.toDateString() === now.toDateString();
            
            data.slots.forEach(slot=>{
                const el=document.createElement('div');
                el.className='time-slot';

                // Convert to 12-hour format
                let [h,m]=slot.time.split(':'); 
                let hour=parseInt(h,10);
                const ampm=hour>=12?'PM':'AM'; 
                hour=(hour%12)||12;
                const formattedTime = `${String(hour).padStart(2,'0')}:${m} ${ampm}`;

                el.textContent = formattedTime;

                // Check if slot is in the past (for today only)
                let isPastTime = false;
                if(isToday) {
                    const [slotHour, slotMinute] = slot.time.split(':').map(Number);
                    const slotDateTime = new Date(selectedDate);
                    slotDateTime.setHours(slotHour, slotMinute, 0, 0);
                    isPastTime = slotDateTime <= now;
                }

                // Disable if not available OR if it's in the past
                if(!slot.available || isPastTime){
                    el.classList.add('disabled');
                } else {
                    el.addEventListener('click',()=>selectTime(slot.time,el));
                }
                
                grid.appendChild(el);
            });
        } else {
            grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--text-light);"><svg style="width: 48px; height: 48px; margin: 0 auto 12px; opacity: 0.3;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><div style="font-size: 14px;">No available time slots for this date</div></div>';
        }
    }catch(e){ console.error(e); grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:20px;color:red;">Error loading slots</div>'; }
}

function selectDate(date,element){
    selectedDate=date; selectedTime=null;
    document.querySelectorAll('.calendar-day').forEach(e=>e.classList.remove('selected'));
    element.classList.add('selected');
    loadTimeSlots();
    updateBookButton();
}

function selectTime(time,element){
    selectedTime=time;
    document.querySelectorAll('.time-slot').forEach(e=>e.classList.remove('selected'));
    element.classList.add('selected');
    <?php if($type=='service'): ?>
    document.getElementById('purposeSection').style.display='block';
    <?php else: ?>
    document.getElementById('doctorPurposeSection').style.display='block';
    <?php endif; ?>
    updateBookButton();
}

function updateBookButton(){ 
    const btn = document.getElementById('bookBtn');
    const btnHeader = document.getElementById('bookBtnHeader');
    const isDisabled = !selectedDate || !selectedTime;
    
    btn.disabled = isDisabled;
    btnHeader.disabled = isDisabled;
    
    // Update button opacity for disabled state
    if(isDisabled) {
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
        btnHeader.style.opacity = '0.5';
        btnHeader.style.cursor = 'not-allowed';
    } else {
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btnHeader.style.opacity = '1';
        btnHeader.style.cursor = 'pointer';
    }
}

async function handleBooking() {
    const btn = document.getElementById('bookBtn');
    const btnHeader = document.getElementById('bookBtnHeader');
    
    btn.disabled = true;
    btnHeader.disabled = true;
    btn.textContent = 'Booking...';
    btnHeader.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg><span>Booking...</span>';
    
    const year = selectedDate.getFullYear();
    const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
    const day = String(selectedDate.getDate()).padStart(2, '0');
    const bookingData = {
        type: '<?php echo $type;?>',
        item_id: <?php echo $itemId;?>,
        date: `${year}-${month}-${day}`,
        time: selectedTime
    };
    
    <?php if($type=='service'):?> bookingData.purpose = document.getElementById('servicePurpose').value;
    <?php else: ?> bookingData.purpose = document.getElementById('doctorPurpose').value; <?php endif; ?>
    
    try {
        const resp = await fetch('api/book-appointment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(bookingData)
        });
        const data = await resp.json();
        
        if(data.success) { 
            showSuccessModal(); 
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
            btn.disabled = false;
            btnHeader.disabled = false;
            btn.textContent = 'Book Appointment';
            btnHeader.innerHTML = '<svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg><span>Book Now</span>';
        }
    } catch(e) { 
        alert('Error booking.'); 
        btn.disabled = false;
        btnHeader.disabled = false;
        btn.textContent = 'Book Appointment';
        btnHeader.innerHTML = '<svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg><span>Book Now</span>';
        console.error(e);
    }
}

document.getElementById('bookBtn').addEventListener('click', handleBooking);
document.getElementById('bookBtnHeader').addEventListener('click', handleBooking);

function previousMonth(){ 
    const checkDate = new Date(currentDate);
    checkDate.setMonth(checkDate.getMonth() - 1);
    if(checkDate >= today) {
        currentDate = checkDate;
        renderCalendar();
    }
}

function nextMonth(){ 
    const checkDate = new Date(currentDate);
    checkDate.setMonth(checkDate.getMonth() + 1);
    if(checkDate <= maxBookingDate) {
        currentDate = checkDate;
        renderCalendar();
    }
}

renderCalendar();

// Success Modal Functions
function showSuccessModal() {
    document.getElementById('successModal').style.display = 'flex';
}

function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    window.location.href = 'dashboard.php';
}
</script>

<!-- Success Modal -->
<div id="successModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 32px; max-width: 440px; width: 90%; margin: 20px; text-align: center;">
        <div style="width: 64px; height: 64px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <svg style="width: 32px; height: 32px; color: #16a34a;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: #16a34a;">Booking Successful!</h3>
        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.5;">Your appointment has been successfully booked.</p>
        
        <!-- Info Box -->
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: left;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <svg style="width: 20px; height: 20px; color: #f59e0b; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div style="font-size: 13px; color: #92400e; line-height: 1.6;">
                    <strong style="display: block; margin-bottom: 4px;">Please Note:</strong>
                    Please wait for our medical staff to confirm your appointment. You will receive a notification once confirmed. Thank you for your patience!
                </div>
            </div>
        </div>
        
        <button onclick="closeSuccessModal()" style="padding: 12px 24px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">
            Go to Home
        </button>
    </div>
</div>

</body>
</html>
