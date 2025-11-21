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
<link rel="stylesheet" href="styles.css">
<style>
body {
    padding-bottom: 160px;
}
/* --- Time Slots --- */
body { margin: 0; padding: 0; }
.container { padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
.content-wrapper { flex: 1; padding-bottom: 100px; overflow-y: auto; }
.page-header { padding: 20px; background: white; border-bottom: 1px solid var(--border-color); }

/* --- Flex layout --- */
.flex-container {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.flex-item {
    flex: 1;
    min-width: 250px;
}

/* --- Time Slots --- */
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 16px;
}
.time-slot {
    padding: 14px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
    font-size: 14px;
    font-weight: 600;
}
.time-slot:hover:not(.disabled) { border-color: var(--primary-color); background: var(--bg-secondary); }
.time-slot.disabled { opacity: 0.4; cursor: not-allowed; background: var(--bg-tertiary); }
.time-slot.selected { background: var(--primary-color); color: white; border-color: var(--primary-color); }

/* --- Calendar --- */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-top: 16px;
}
.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
    font-size: 12px;
    font-weight: 600;
}
.calendar-day:hover:not(.disabled) { border-color: var(--primary-color); background: var(--bg-secondary); }
.calendar-day.disabled { opacity: 0.4; cursor: not-allowed; background: var(--bg-tertiary); }
.calendar-day.selected { background: var(--primary-color); color: white; border-color: var(--primary-color); }
.calendar-day .day-name { font-size: 9px; color: var(--text-light); font-weight: 700; }
.calendar-day .day-number { font-size: 16px; font-weight: 700; margin-top: 4px; }

/* --- Book Button Fixed --- */
#bookButtonContainer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to top, white, white 80%, rgba(255,255,255,0));
    box-shadow: 0 -2px 15px rgba(0,0,0,0.08);
    z-index: 1000;
    display: flex;
    gap: 12px;
}
.btn-book-main { flex: 1; }

/* --- Two Column Layout --- */
.booking-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    padding: 20px;
}

@media(max-width: 768px) {
    .booking-layout { grid-template-columns: 1fr; }
    .time-slots-grid { grid-template-columns: repeat(3, 1fr); }
}

@media(min-width: 1024px) {
    .time-slots-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<div class="container">

<!-- Header -->
<div class="page-header">
    <div style="display: flex; align-items: center; gap:16px;">
        <button onclick="history.back()" style="background: var(--bg-tertiary); border:none; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">
            <svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </button>
        <h1 style="font-size:20px;font-weight:700;margin:0;">Book Appointment</h1>
    </div>
</div>

<!-- Content Wrapper -->
<div class="content-wrapper">

<!-- Item Info -->
<div style="padding: 20px; background: white; border-bottom: 1px solid var(--border-color);">
    <div style="display:flex;gap:16px;align-items:center;">
        <div style="width:60px;height:60px;background:var(--bg-secondary);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;">
            <?php echo $type=='doctor'?'👨‍⚕️':'🔬'; ?>
        </div>
        <div>
            <h3 style="font-size:16px;font-weight:700;margin:0 0 4px 0;"><?php echo htmlspecialchars($itemName);?></h3>
            <p style="font-size:14px;color:var(--text-light);margin:0;"><?php echo htmlspecialchars($itemSubtitle);?></p>
        </div>
    </div>
</div>

<!-- Calendar + Time Slots (Two Column) -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
    
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

<!-- Purpose -->
<?php if($type=='service'): ?>
<div style="padding: 20px; border-top: 1px solid var(--border-color);" id="purposeSection" style="display:none;">
    <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;color:var(--text-primary);">Appointment Purpose</h3>
    <select id="servicePurpose" class="form-control" style="padding: 12px; font-size: 14px; border: 1px solid var(--border-color); border-radius: 8px; background: white;">
        <option value="">Select purpose...</option>
        <option value="Laboratory Test">Laboratory Test</option>
        <option value="Radiology/Imaging">Radiology/Imaging</option>
        <option value="Physical Therapy">Physical Therapy</option>
        <option value="Emergency Care">Emergency Care</option>
        <option value="Surgical Procedure">Surgical Procedure</option>
        <option value="Follow-up Care">Follow-up Care</option>
        <option value="Other">Other</option>
    </select>
</div>
<?php else: ?>
<div style="padding: 20px; border-top: 1px solid var(--border-color);" id="doctorPurposeSection" style="display:none;">
    <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;color:var(--text-primary);">Appointment Purpose</h3>
    <select id="doctorPurpose" class="form-control" style="padding: 12px; font-size: 14px; border: 1px solid var(--border-color); border-radius: 8px; background: white;">
        <option value="">Select reason...</option>
        <option value="General Consultation">General Consultation</option>
        <option value="Follow-up Visit">Follow-up Visit</option>
        <option value="New Symptoms">New Symptoms</option>
        <option value="Chronic Disease Management">Chronic Disease Management</option>
        <option value="Prescription Refill">Prescription Refill</option>
        <option value="Medical Certificate">Medical Certificate</option>
        <option value="Second Opinion">Second Opinion</option>
        <option value="Pre-operative Consultation">Pre-operative Consultation</option>
        <option value="Post-operative Follow-up">Post-operative Follow-up</option>
        <option value="Other">Other</option>
    </select>
</div>
<?php endif; ?>

</div>

<!-- Book Button Fixed at Bottom -->
<div style="position: fixed; bottom: 80px; left: 0; right: 0; padding: 12px 16px; background: transparent; box-shadow: none; z-index: 100;">
    <div style="max-width: min(600px, 90vw); margin: 0 auto;">
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

                if(slot.available){
                    el.addEventListener('click',()=>selectTime(slot.time,el));
                } else {
                    el.classList.add('disabled');
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
    btn.disabled=!selectedDate||!selectedTime; 
}

document.getElementById('bookBtn').addEventListener('click',async()=>{
    const btn=document.getElementById('bookBtn');
    btn.disabled=true; btn.textContent='Booking...';
    const year=selectedDate.getFullYear();
    const month=String(selectedDate.getMonth()+1).padStart(2,'0');
    const day=String(selectedDate.getDate()).padStart(2,'0');
    const bookingData={
        type:'<?php echo $type;?>',
        item_id:<?php echo $itemId;?>,
        date:`${year}-${month}-${day}`,
        time:selectedTime
    };
    <?php if($type=='service'):?> bookingData.purpose=document.getElementById('servicePurpose').value;
    <?php else: ?> bookingData.purpose=document.getElementById('doctorPurpose').value; <?php endif; ?>
    try{
        const resp=await fetch('api/book-appointment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(bookingData)});
        const data=await resp.json();
        if(data.success) { alert('Booking successful!'); location.reload(); }
        else { alert('Error: '+(data.message||'Unknown')); btn.disabled=false; btn.textContent='Book Appointment'; }
    }catch(e){ alert('Error booking.'); btn.disabled=false; btn.textContent='Book Appointment'; console.error(e);}
});

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
</script>
</body>
</html>
