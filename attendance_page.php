<?php
session_start();
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') { header("Location: login.html"); exit(); }
$date = $_GET['date'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | BPI </title>
    <link rel="icon" href="Bhavesh Plastic Industries.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <link rel="stylesheet" href="style_attendance.css">
</head>
<body>

    <header class="main-header">
        <div class="brand-area">
            <img src="Bhavesh Plastic Industries.png" alt="Logo">
            <div class="brand-text"><h2>Bhavesh Plastic Industries</h2><p>Attendance System</p></div>
        </div>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <div class="controls-bar">
        <div class="date-group">
            <button class="date-btn" onclick="changeDate(-1)"><ion-icon name="chevron-back"></ion-icon></button>
            <input type="date" id="attDate" class="date-input" value="<?php echo $date; ?>" onchange="loadData()">
            <button class="date-btn" onclick="changeDate(1)"><ion-icon name="chevron-forward"></ion-icon></button>
        </div>
        
        <button class="btn-action" style="background:white; color:#333; border:1px solid #ddd;" onclick="openLunchModal()">
            <ion-icon name="time" style="vertical-align:middle; font-size:1.2rem; color:var(--primary);"></ion-icon> 
            <span id="lunchSummary" style="margin-left:5px; font-size:0.9rem;">Settings</span>
        </button>

        <button id="btnHoliday" class="btn-holiday" onclick="toggleHoliday()">
            <ion-icon name="calendar-number"></ion-icon> 
            <span id="holidayText">Set Holiday</span>
        </button>

        <div class="search-box">
            <ion-icon name="search" class="search-icon"></ion-icon>
            <input type="text" id="searchBox" placeholder="Search..." onkeyup="filterWorkers()">
        </div>
        <button class="btn-action" onclick="markAllPresent()">
            <ion-icon name="checkmark-done-circle" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;"></ion-icon> Mark All
        </button>
    </div>
    
    <div id="holidayMsg" class="holiday-banner">
        <ion-icon name="warning" style="font-size:1.5rem; vertical-align:middle;"></ion-icon>
        This date is marked as a Holiday. Attendance is disabled.
    </div>

    <div id="workerContainer" class="content-area">
        <div style="text-align:center; padding:60px;">Loading...</div>
    </div>

    <div class="modal-overlay" id="deductModal">
        <div class="modal-box">
            <h3 style="color:var(--primary); margin-bottom:5px;">Adjust Shift</h3>
            <p id="modalEmpName" style="color:#666; margin-bottom:15px;">Worker</p>
            <div class="deduct-grid" id="deductGrid"></div>
            <button onclick="closeModal('deductModal')" style="background:none; border:none; color:#ef4444; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="otModal">
        <div class="modal-box">
            <h3 id="otTitle" style="color:#6366f1;">Add Time</h3>
            <p id="otEmpName" style="color:#666; margin-bottom:15px;">Worker</p>
            <div class="deduct-grid" id="otGrid"></div>
            <button onclick="closeModal('otModal')" style="background:none; border:none; color:#666; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>
    
    <div class="modal-overlay" id="lunchModal">
        <div class="modal-box" style="max-width:350px;">
            <h3 style="color:var(--primary);">Lunch Settings</h3>
            <select id="lunchStartVal" style="width:100%; padding:10px; margin:10px 0;">
                <option value="12:00">12:00 PM</option><option value="12:30">12:30 PM</option>
                <option value="13:00">01:00 PM</option><option value="13:30">01:30 PM</option>
                <option value="14:00">02:00 PM</option>
            </select>
            <select id="lunchDurVal" style="width:100%; padding:10px; margin-bottom:20px;">
                <option value="30">30 Min</option><option value="45">45 Min</option><option value="60">1 Hr</option><option value="90">1.5 Hr</option>
            </select>
            <button class="btn-action" style="width:100%;" onclick="saveLunchSettings()">Update</button>
            <button onclick="closeModal('lunchModal')" style="background:none; border:none; color:#777; margin-top:10px; cursor:pointer;">Close</button>
        </div>
    </div>

<script>
let currentData = [], shiftMeta = {}; 
const activeDate = document.getElementById('attDate');

/* --- LOAD DATA (Updated) --- */
async function loadData() {
    window.history.replaceState(null, null, `?date=${activeDate.value}`);
    try {
        const fd = new FormData(); fd.append('action', 'fetch'); fd.append('date', activeDate.value);
        const res = await fetch('attendance_api.php', { method:'POST', body:fd });
        const json = await res.json();
        
        if(json.status === 'success') {
            currentData = json.data; 
            shiftMeta = json.meta;
            renderList(currentData);
            updateLunchUI(shiftMeta.l_start, shiftMeta.l_dur);
            
            // UPDATE HOLIDAY BUTTON UI
            updateHolidayUI(shiftMeta.is_holiday);
        }
    } catch(err) { console.error(err); }
}

async function toggleHoliday() {
    const isHoliday = document.getElementById('btnHoliday').classList.contains('active');
    
    let msg = "";
    if(isHoliday) {
        msg = "Remove Holiday status? Attendance will be enabled.";
    } else {
        msg = "WARNING: Marking this as a HOLIDAY will DELETE all attendance records for this date.\n\nAre you sure?";
    }
    
    if(confirm(msg)) {
        const fd = new FormData();
        fd.append('action', 'toggle_holiday');
        fd.append('date', activeDate.value);
        
        await fetch('attendance_api.php', { method:'POST', body:fd });
        loadData(); 
    }
}

function renderList(data) {
    const container = document.getElementById('workerContainer');
    const holidayMsg = document.getElementById('holidayMsg');
    const markBtn = document.getElementById('markAllBtn');
    
    container.innerHTML = '';

    // --- STRICT HOLIDAY LOGIC ---
    if(shiftMeta.is_holiday) {
        holidayMsg.style.display = 'block';     // Show Banner
        if(markBtn) markBtn.style.display = 'none'; // Hide Mark All
        
        // Disable the entire container so NOTHING is clickable
        container.style.pointerEvents = 'none'; 
        container.style.opacity = '0.5';
    } else {
        holidayMsg.style.display = 'none';
        if(markBtn) markBtn.style.display = 'inline-flex';
        
        // Re-enable clicks
        container.style.pointerEvents = 'auto';
        container.style.opacity = '1';
    }

    if(!data.length) { 
        container.innerHTML='<div style="padding:40px; text-align:center;">No workers.</div>'; 
        return; 
    }

    data.forEach(w => {
        const card = document.createElement('div');
        card.className = 'worker-card';
        
        // ... (Card HTML remains the same) ...
        card.innerHTML = `
            <div>
                <div class="worker-name">${w.name}</div>
                <div class="worker-id">ID: ${w.id}</div>
            </div>
            <div class="shifts-row">
                <div class="ot-btn ot-early ${w.early>0?'active':''}" onclick="openOT(${w.id}, '${w.name}', 'early', ${w.early})">
                    <div class="ot-val">+${w.early > 0 ? formatMins(w.early) : '0'}</div>
                    <div class="ot-lbl">Early</div>
                </div>

                ${renderPill(w.id, 'b1', 'Morning', shiftMeta.lbl_b1, w.b1_p, w.b1_d, shiftMeta.b1_max)}
                ${renderPill(w.id, 'b2', 'Shift 2', shiftMeta.lbl_b2, w.b2_p, w.b2_d, shiftMeta.b2_max)}
                ${renderPill(w.id, 'b3', 'Shift 3', shiftMeta.lbl_b3, w.b3_p, w.b3_d, shiftMeta.b3_max)}
                
                <div class="ot-btn ot-late ${w.late>0?'active':''}" onclick="openOT(${w.id}, '${w.name}', 'late', ${w.late})">
                    <div class="ot-val">+${w.late > 0 ? formatMins(w.late) : '0'}</div>
                    <div class="ot-lbl">Late</div>
                </div>
            </div>
            <div>
                <div class="total-hrs">${w.total}</div>
                <div class="total-lbl">Hours</div>
            </div>
        `;
        container.appendChild(card);
    });
}

/* --- UPDATE HOLIDAY BUTTON UI --- */
function updateHolidayUI(isHoliday) {
    const btn = document.getElementById('btnHoliday');
    const txt = document.getElementById('holidayText');
    
    if(isHoliday) {
        btn.classList.add('active');
        txt.innerText = "Holiday Active";
    } else {
        btn.classList.remove('active');
        txt.innerText = "Set Holiday";
    }
    // Re-render list to apply the lock/unlock classes
    // (We don't need to call renderList here because loadData calls both)
}
    
// --- OVERTIME MODAL LOGIC ---
let activeOT = {};
function openOT(id, name, type, current) {
    activeOT = {id, type};
    const title = type === 'early' ? "Early Arrival (Before 8am)" : "Late Departure (After 8pm)";
    document.getElementById('otTitle').innerText = title;
    document.getElementById('otEmpName').innerText = name + ` (Current: +${formatMins(current)})`;
    
    const grid = document.getElementById('otGrid');
    let html = `<div class="d-opt" onclick="applyOT(0)">Reset (0)</div>`;
    [15, 30, 45, 60, 90, 120, 180, 240].forEach(m => {
        html += `<div class="d-opt" onclick="applyOT(${m})">+${formatMins(m)}</div>`;
    });
    grid.innerHTML = html;
    document.getElementById('otModal').classList.add('active');
}

async function applyOT(m) {
    const fd = new FormData();
    fd.append('action', 'update_field'); fd.append('type', 'ot');
    fd.append('date', activeDate.value); fd.append('eid', activeOT.id);
    fd.append('ot_type', activeOT.type === 'early' ? 'early' : 'late');
    fd.append('mins', m);
    
    await fetch('attendance_api.php', {method:'POST', body:fd});
    closeModal('otModal'); loadData();
}

// --- STANDARD SHIFT LOGIC (Keep existing) ---
function renderPill(id, block, title, lbl, p, d, max) {
    let css = p ? (d>0 ? 'partial' : 'present') : 'absent';
    let val = p ? (d>0 ? `-${formatMins(d)}` : 'FULL') : 'ABSENT';
    return `
    <div class="shift-pill ${css}" onclick="toggleShift(${id},'${block}',${p})" title="${lbl}">
        <div class="pill-label">${title}</div>
        <div class="pill-value" style="font-size:0.95rem;">${val}</div>
        <div style="font-size:0.65rem; opacity:0.7;">${lbl}</div>
        <div class="edit-btn" onclick="event.stopPropagation(); openDeduct(${id},'${block}','${title}',${max})"><ion-icon name="options"></ion-icon></div>
    </div>`;
}
async function toggleShift(id, block, p) {
    const fd = new FormData(); fd.append('action', 'update_field'); fd.append('type', 'shift');
    fd.append('date', activeDate.value); fd.append('eid', id); fd.append('block', block);
    fd.append('state', p?0:1); fd.append('deduct', 0);
    await fetch('attendance_api.php', { method:'POST', body:fd }); loadData();
}
let activeEdit = {};
function openDeduct(id, block, title, max) {
    activeEdit = {id, block};
    document.getElementById('modalEmpName').innerText = `${title} (Max: ${formatMins(max)})`;
    const grid = document.getElementById('deductGrid');
    grid.innerHTML = `<div class="d-opt" onclick="applyDeduct(0)">None (Full)</div>`;
    for(let i=15; i<=max; i+=15) grid.innerHTML += `<div class="d-opt" onclick="applyDeduct(${i})">-${formatMins(i)}</div>`;
    document.getElementById('deductModal').classList.add('active');
}
async function applyDeduct(m) {
    const fd = new FormData(); fd.append('action', 'update_field'); fd.append('type', 'shift');
    fd.append('date', activeDate.value); fd.append('eid', activeEdit.id); fd.append('block', activeEdit.block);
    fd.append('state', 1); fd.append('deduct', m);
    await fetch('attendance_api.php', { method:'POST', body:fd }); closeModal('deductModal'); loadData();
}

// Helpers
function formatMins(m) { if(m<60) return m+'m'; return `${Math.floor(m/60)}h ${m%60>0 ? m%60+'m' : ''}`; }
function updateLunchUI(start, dur) {
    const [h, m] = start.split(':'); const date = new Date(); date.setHours(h); date.setMinutes(m);
    document.getElementById('lunchSummary').innerText = `${date.toLocaleTimeString([],{hour:'numeric',minute:'2-digit'})} (${dur}m)`;
    document.getElementById('lunchStartVal').value = start; document.getElementById('lunchDurVal').value = dur;
}
async function saveLunchSettings() {
    const fd = new FormData(); fd.append('action', 'update_settings'); 
    fd.append('start_time', document.getElementById('lunchStartVal').value); 
    fd.append('duration', document.getElementById('lunchDurVal').value);
    await fetch('attendance_api.php', {method:'POST', body:fd}); closeModal('lunchModal'); loadData();
}
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function openLunchModal() { document.getElementById('lunchModal').classList.add('active'); }
function filterWorkers() {
    const t = document.getElementById('searchBox').value.toLowerCase();
    renderList(currentData.filter(w => w.name.toLowerCase().includes(t)));
}
function markAllPresent() { if(confirm("Mark All Full?")) { const fd = new FormData(); fd.append('action','mark_all'); fd.append('date',activeDate.value); fetch('attendance_api.php',{method:'POST', body:fd}).then(loadData); } }
function changeDate(d) { const dt = new Date(activeDate.value); dt.setDate(dt.getDate()+d); activeDate.value = dt.toISOString().split('T')[0]; loadData(); }

document.addEventListener('DOMContentLoaded', loadData);
</script>
</body>
</html>