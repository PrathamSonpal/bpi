<?php
session_start();

if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}

$role = $_SESSION['role'];
if ($role !== 'cut_bend' && $role !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Cutting & Bending Dept | BPI</title>
<link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
<link rel="stylesheet" href="style_cut_bend.css?v=1.1.5">
<style>
  /* Quick visual fixes */
  .section-content {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: all 0.4s ease;
  }
  .section-content.open {
    opacity: 1;
    max-height: 1000px;
  }
  .section-header {
    background: #eee;
    padding: 10px 14px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    cursor: pointer;
    border-radius: 6px;
    margin-top: 1rem;
  }
  .section-header .arrow {
    transition: transform 0.3s;
  }
  .section-header.open .arrow {
    transform: rotate(180deg);
  }
</style>
</head>
<body>
<div class="dashboard">

  <header class="header-container">
    <div class="logo-section">
      <img src="Bhavesh Plastic Industries.png" alt="BPI Logo" class="logo-img">
      <div class="header-title">
        <h2>Bhavesh Plastic Industries</h2>
        <p>Cutting & Bending Department</p>
      </div>
    </div>
    <div class="hamburger" onclick="toggleMenu()">☰</div>
    <nav class="nav-bar" id="navMenu">
      <?php if ($role === 'admin'): ?>
        <a href="index.php">Dashboard</a>
      <?php endif; ?>
      <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
  </header>

  <main class="main-content">

    <div class="actions-row">
      <button id="openCuttingModalBtn" class="btn-add">Add Cutting Entry</button>
      <button id="openBendingModalBtn" class="btn-add">Add Bending Entry</button>
      <button id="showOverallBalanceBtn" class="btn-add btn-balance">Show Overall Balance</button>
    </div>

    <div id="overallBalanceDisplay" class="overall-balance-container" style="display:none;">
      <button id="overallBalanceCloseBtn" class="overall-balance-close-btn" onclick="toggleBalance(false)">&times;</button>
      <h3>Overall Handle Balance (Cutting → Bending)</h3>
      <div class="search-bar-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by Handle Name...">
      </div>
      <div class="table-wrapper">
        <table id="overallBalanceTable" class="overall-balance-table">
          <thead>
            <tr>
              <th>Handle Name</th>
              <th>Total Cut</th>
              <th>Total Bent</th>
              <th>Balance (Ready for Bending)</th>
            </tr>
          </thead>
          <tbody><tr><td colspan="4">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- Cutting Modal -->
    <div id="cuttingModal" class="modal" style="display:none;">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('cuttingModal')">&times;</button>
        <h3>Add Cutting Entry</h3>
        <form id="cuttingForm">
          <label>Handle</label>
          <select name="handle_id" id="cutHandle" required>
            <option value="">Loading...</option>
          </select>

          <label>Cut Pieces</label>
          <input type="number" name="cut_pcs" id="cutPcs" min="1" required>

          <label>Order (Optional)</label>
          <select name="order_number" id="cutOrder">
            <option value="">-- General Stock --</option>
          </select>

          <label>Date</label>
          <input type="date" name="cut_date" id="cutDate" required>

          <label>Description (Optional)</label>
          <textarea name="description" id="cutDesc" rows="3"></textarea>

          <div class="form-row">
            <button type="submit" class="btn-save">Save Entry</button>
            <button type="button" class="btn-cancel" onclick="closeModal('cuttingModal')">Cancel</button>
          </div>
          <div class="form-msg" id="cutMsg"></div>
        </form>
      </div>
    </div>

    <!-- Bending Modal -->
    <div id="bendingModal" class="modal" style="display:none;">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('bendingModal')">&times;</button>
        <h3>Add Bending Entry</h3>
        <form id="bendingForm">
          <label>Handle</label>
          <select name="handle_id" id="bendHandle" required>
            <option value="">Loading...</option>
          </select>

          <label>Bent Pieces</label>
          <input type="number" name="bent_pcs" id="bendPcs" min="1" required>
          <small>Max available: <span id="bendBalance" class="balance-span">...</span></small>

          <label>Order (Optional)</label>
          <select name="order_number" id="bendOrder">
            <option value="">-- General Stock --</option>
          </select>

          <label>Date</label>
          <input type="date" name="bend_date" id="bendDate" required>

          <label>Description (Optional)</label>
          <textarea name="description" id="bendDesc" rows="3"></textarea>

          <div class="form-row">
            <button type="submit" class="btn-save">Save Entry</button>
            <button type="button" class="btn-cancel" onclick="closeModal('bendingModal')">Cancel</button>
          </div>
          <div class="form-msg" id="bendMsg"></div>
        </form>
      </div>
    </div>

    <!-- Cutting History -->
    <div class="section">
      <button class="section-header" id="toggleCuttingHeader">
        <span>Recent Cutting History</span>
        <span class="arrow">▾</span>
      </button>
      <div id="cuttingHistoryContent" class="section-content">
        <div class="table-wrapper">
          <table class="log-table" id="cuttingHistoryTable">
            <thead>
              <tr>
                <th>Handle Name</th>
                <th>Cut Pieces</th>
                <th>Date</th>
                <th>Order #</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody><tr><td colspan="5">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Bending History -->
    <div class="section">
      <button class="section-header" id="toggleBendingHeader">
        <span>Recent Bending History</span>
        <span class="arrow">▾</span>
      </button>
      <div id="bendingHistoryContent" class="section-content">
        <div class="table-wrapper">
          <table class="log-table" id="bendingHistoryTable">
            <thead>
              <tr>
                <th>Handle Name</th>
                <th>Bent Pieces</th>
                <th>Date</th>
                <th>Order #</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody><tr><td colspan="5">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

    <footer class="footer">
    	<p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
    </footer>
    
<script>
const $ = s => document.querySelector(s);
let balanceData = [];

function toggleMenu() { $('#navMenu').classList.toggle('active'); }
function openModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }
function toggleBalance(forceShow) {
  const el = $('#overallBalanceDisplay');
  if (forceShow === true) el.style.display = 'block';
  else if (forceShow === false) el.style.display = 'none';
  else el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

/* ===== Collapsible Smooth UI ===== */
function bindToggle(headerId, contentId) {
  const header = document.getElementById(headerId);
  const content = document.getElementById(contentId);
  const arrow = header.querySelector('.arrow');
  header.addEventListener('click', () => {
    header.classList.toggle('open');
    content.classList.toggle('open');
  });
}
bindToggle('toggleCuttingHeader','cuttingHistoryContent');
bindToggle('toggleBendingHeader','bendingHistoryContent');

/* ===== Load Dropdowns ===== */
async function fetchJSON(url){ const r=await fetch(url); return await r.json(); }

async function loadHandles(){
  const data = await fetchJSON('get_ss_handles.php').catch(()=>[]);
  const handleSelects = ['cutHandle','bendHandle'];
  handleSelects.forEach(id => {
    const el = $('#'+id);
    el.innerHTML = '<option value="">Select Handle</option>';
    data.forEach(h => {
      const full = `${h.name}${h.size ? ' — '+h.size:''}${h.material?' — '+h.material:''}`;
      el.innerHTML += `<option value="${h.id}">${full}</option>`;
    });
  });
}
async function loadOrders(){
  const data = await fetchJSON('get_pending_orders.php').catch(()=>({orders:[]}));
  const orderSelects=['cutOrder','bendOrder'];
  orderSelects.forEach(id=>{
    const el=$('#'+id);
    el.innerHTML='<option value="">-- General Stock --</option>';
    (data.orders||[]).forEach(o=>{
      el.innerHTML+=`<option value="${o.order_number}">${o.order_number} (${o.customer_name})</option>`;
    });
  });
}

/* ===== Histories & Balance ===== */
async function loadCuttingHistory(){
  const tb=$('#cuttingHistoryTable tbody');
  const data=await fetchJSON('get_cutting_log_history.php').catch(()=>[]);
  tb.innerHTML=data.length?data.map(r=>`
    <tr><td>${r.handle_name}</td><td>${r.cut_pcs}</td><td>${r.cut_date}</td><td>${r.order_number||'--'}</td><td>${r.description||''}</td></tr>`).join('')
    :'<tr><td colspan="5">No cutting history found</td></tr>';
}
async function loadBendingHistory(){
  const tb=$('#bendingHistoryTable tbody');
  const data=await fetchJSON('get_bending_log_history.php').catch(()=>[]);
  tb.innerHTML=data.length?data.map(r=>`
    <tr><td>${r.handle_name}</td><td>${r.bent_pcs}</td><td>${r.bend_date}</td><td>${r.order_number||'--'}</td><td>${r.description||''}</td></tr>`).join('')
    :'<tr><td colspan="5">No bending history found</td></tr>';
}
async function loadOverallBalance(){
  const tb=$('#overallBalanceTable tbody');
  tb.innerHTML='<tr><td colspan="4">Loading...</td></tr>';
  const data=await fetchJSON('get_overall_bending_balance.php').catch(()=>({success:false,data:[]}));
  if(data.success){
    balanceData=data.data;
    tb.innerHTML=data.data.map(r=>`
      <tr><td>${r.handle_name}</td><td>${r.total_cut}</td><td>${r.total_bent}</td><td>${r.balance}</td></tr>`).join('');
  }else tb.innerHTML='<tr><td colspan="4">No data found</td></tr>';
}

/* ===== Init ===== */
document.addEventListener('DOMContentLoaded',()=>{
  loadHandles(); loadOrders();
  loadCuttingHistory(); loadBendingHistory(); loadOverallBalance();
  $('#showOverallBalanceBtn').addEventListener('click',()=>toggleBalance(true));
  $('#overallBalanceCloseBtn').addEventListener('click',()=>toggleBalance(false));
});
</script>
</body>
</html>
