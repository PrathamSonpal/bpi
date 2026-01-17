<?php
session_start();
// Role check: Allow only 'buff' or 'admin' roles
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'];
if ($role !== 'buff' && $role !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// --- 1. Load Handles (For Modal Dropdown) ---
$handles = [];

$sql_handles = "
    SELECT 
        i.id,
        i.name,
        i.size,
        i.material,
        (IFNULL(t.total_turned,0) - IFNULL(b.total_buffed,0)) AS balance
    FROM items i
    LEFT JOIN (
        SELECT handle_id, SUM(ready_pcs) AS total_turned
        FROM turning_log
        GROUP BY handle_id
    ) t ON t.handle_id = i.id
    LEFT JOIN (
        SELECT handle_id, SUM(buffed_pcs) AS total_buffed
        FROM buffing_log
        GROUP BY handle_id
    ) b ON b.handle_id = i.id
    HAVING balance > 0
    ORDER BY i.name ASC
";

if ($res_handles = $conn->query($sql_handles)) {
    while ($r = $res_handles->fetch_assoc()) {
        $fullName = $r['name'];
        if (!empty($r['size'])) $fullName .= " — " . $r['size'];
        if (!empty($r['material'])) $fullName .= " — " . $r['material'];
        $fullName .= " [Qty: " . (int)$r['balance'] . "]";
        
        $handles[] = [
            'id' => $r['id'],
            'full_name' => $fullName
        ];
    }
    $res_handles->close();
}

// --- 2. Load Pending Orders ---
$pendingOrders = [];
$sql_orders = "SELECT order_number, customer_name FROM orders WHERE status = 'pending' ORDER BY id DESC";
if ($res_orders = $conn->query($sql_orders)) {
    while ($r = $res_orders->fetch_assoc()) $pendingOrders[] = $r;
    $res_orders->close();
}

// --- 3. Load Overall Buffing Balance (For Modal Logic & Table) ---
$balances = [];
// UPDATED SQL: Added i.material to select list for filtering
$sql_balance = "
    SELECT 
      i.id AS handle_id,
      i.name AS handle_name,
      i.material,
      IFNULL(tur.total_turned, 0) AS opening_stock,
      IFNULL(buf.total_buffed, 0) AS total_buffed,
      (IFNULL(tur.total_turned, 0) - IFNULL(buf.total_buffed, 0)) AS balance
    FROM items i
    LEFT JOIN (
        SELECT handle_id, SUM(ready_pcs) AS total_turned
        FROM turning_log
        GROUP BY handle_id
    ) tur ON tur.handle_id = i.id
    LEFT JOIN (
        SELECT handle_id, SUM(buffed_pcs) AS total_buffed
        FROM buffing_log
        GROUP BY handle_id
    ) buf ON buf.handle_id = i.id
    GROUP BY i.id
    HAVING balance > 0
    ORDER BY i.name ASC
";

if ($res_balance = $conn->query($sql_balance)) {
    while ($r = $res_balance->fetch_assoc()) {
        $balances[] = $r;
    }
    $res_balance->close();
}

// --- 4. Load Recent Buffing History ---
$history = [];
$sql_history = "
  SELECT b.id, b.handle_id, b.buffed_pcs, b.buffing_date, b.description, b.order_number, i.name AS handle_name
  FROM buffing_log b
  LEFT JOIN items i ON b.handle_id = i.id
  ORDER BY b.id DESC
  LIMIT 50
";
if ($res_history = $conn->query($sql_history)) {
    while ($r = $res_history->fetch_assoc()) $history[] = $r;
    $res_history->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Buffing Department | BPI</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    <link rel="stylesheet" href="style_buff.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .btn-icon { background: none; border: none; cursor: pointer; font-size: 1.1em; margin: 0 5px; padding: 5px; transition: transform 0.2s; }
        .btn-icon:hover { transform: scale(1.1); }
        .btn-edit { color: #007bff; }
        .btn-delete { color: #dc3545; }
        .log-table td { vertical-align: middle; }
    </style>
</head>
<body>

<div class="page-wrapper">

<div class="dashboard">

  <header class="header-container">
    <div class="logo-section">
      <img src="Bhavesh Plastic Industries.png" alt="BPI Logo" class="logo-img">
      <div class="header-title">
        <h2>Bhavesh Plastic Industries</h2>
        <p>Buffing Department</p>
      </div>
    </div>
    <div class="hamburger" onclick="toggleMenu()">☰</div>
    <nav class="nav-bar" id="navMenu">
      <?php if ($role === 'admin'): ?>
        <a href="index.php">Dashboard</a>
      <?php endif; ?>
      <?php if ($role === 'buff'): ?>
            <a href="my_portal.php">My Portal</a>
      <?php endif; ?>
      <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
  </header>

  <main class="main-content">
    
    <?php if (isset($_GET['success'])): ?>
      <div class="form-msg" style="display:block; background-color:#d4edda;color:#155724;border-color:#c3e6cb;padding:1rem;border-radius:8px;margin-bottom:1rem;">
        <?php echo htmlspecialchars($_GET['success']); ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="form-msg" style="display:block; background-color:#f8d7da;color:#721c24;border-color:#f5c6cb;padding:1rem;border-radius:8px;margin-bottom:1rem;">
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
    <?php endif; ?>

    <div class="actions-row">
      <button id="openBuffingModalBtn" class="btn-add">Add Ready Buffing</button>
      <button id="showOverallBalanceBtn" class="btn-add btn-balance">Show Overall Balance</button>
    </div>

    <div id="overallBalanceDisplay" class="overall-balance-container" style="display:none;">
      <button id="overallBalanceCloseBtn" class="overall-balance-close-btn" onclick="toggleBalance(false)">&times;</button>
      <h3>Overall Handle Balance (Buffing Stock)</h3>
      
      <div class="filter-toolbar">
        <input type="text" id="searchInput" class="filter-input-search" placeholder="Search..." onkeyup="filterAndSortTable()">
        
        <select id="filterSelect" class="filter-select" onchange="filterAndSortTable()">
            <option value="all">All Materials</option>
            <option value="malleable">Malleable</option>
            <option value="ss">Stainless Steel</option>
        </select>

        <select id="sortSelect" class="filter-select" onchange="filterAndSortTable()">
            <option value="name_asc">Name (A-Z)</option>
            <option value="name_desc">Name (Z-A)</option>
            <option value="bal_asc">Qty (Low-High)</option>
            <option value="bal_desc">Qty (High-Low)</option>
        </select>

        <div class="filter-qty-group">
            <select id="qtyOperator" class="filter-qty-operator" onchange="filterAndSortTable()">
                <option value="all">Any</option>
                <option value="lt">&lt; Less</option>
                <option value="gt">&gt; More</option>
            </select>
            <input type="number" id="qtyValue" class="filter-qty-input" placeholder="Qty" onkeyup="filterAndSortTable()">
        </div>
      </div>

      <div class="table-wrapper">
         <table id="overallBalanceTable" class="overall-balance-table">
            <thead>
               <tr>
                  <th>Handle Name</th>
                  <th>Opening Stock (Turned)</th>
                  <th>Total Buffed</th>
                  <th>Balance (Ready to Buff)</th>
               </tr>
            </thead>
            <tbody id="balanceTableBody">
               <?php if (empty($balances)): ?>
                 <tr><td colspan="4">No handle data found</td></tr>
               <?php else: ?>
                 <?php foreach ($balances as $b): 
                    // Normalize material for data attribute
                    $mat = strtolower($b['material'] ?? '');
                    if(strpos($mat, 'stainless') !== false) $mat = 'ss';
                 ?>
                   <tr data-handle-id="<?php echo (int)$b['handle_id']; ?>" 
                       data-balance="<?php echo (int)$b['balance']; ?>"
                       data-name="<?php echo strtolower(htmlspecialchars($b['handle_name'])); ?>"
                       data-material="<?php echo htmlspecialchars($mat); ?>">
                     <td>
                        <?php echo htmlspecialchars($b['handle_name']); ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($b['material']); ?></small>
                     </td>
                     <td><?php echo (int)$b['opening_stock']; ?></td>
                     <td><?php echo (int)$b['total_buffed']; ?></td>
                     <td style="font-weight:bold; color:<?php echo ($b['balance'] < 50) ? '#e74c3c' : '#28a745'; ?>;">
                        <?php echo (int)$b['balance']; ?>
                     </td>
                   </tr>
                 <?php endforeach; ?>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
    </div>

    <div id="buffingModal" class="modal" style="display:none;">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('buffingModal')">&times;</button>
        <h3>Add Ready Buffing</h3>

        <form action="save_buffing_log.php" method="POST">
          <label>Handle</label>
          <select id="buffingHandleSelect" name="handle_id" required>
            <option value="">Select a Handle</option>
            <?php foreach ($handles as $h): ?>
              <option value="<?php echo (int)$h['id']; ?>"><?php echo htmlspecialchars($h['full_name']); ?></option>
            <?php endforeach; ?>
          </select>

          <label>Buffed Pieces</label>
          <input id="buffingReadyPcs" name="buffed_pcs" type="number" min="1" required>
          <small>Max available (Turned): <span id="modalStockBalance">...</span></small>

          <label>For Order (Optional)</label>
          <select name="order_number" id="buffingOrderSelect">
            <option value="">-- General Stock --</option>
            <?php foreach ($pendingOrders as $o): ?>
              <option value="<?php echo htmlspecialchars($o['order_number']); ?>">
                Order #<?php echo htmlspecialchars($o['order_number']); ?> (<?php echo htmlspecialchars($o['customer_name'] ?? 'N/A'); ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <label>Date</label>
          <input id="buffingDate" name="buffing_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>

          <label>Description (Optional)</label>
          <textarea id="buffingDescription" name="description" rows="3"></textarea>

          <div class="form-row">
            <button type="submit" class="btn-save">Save Entry</button>
            <button type="button" class="btn-cancel" onclick="closeModal('buffingModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
      
    <div id="editBuffingModal" class="modal" style="display:none;">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('editBuffingModal')">&times;</button>
        <h3>Edit Buffing Entry</h3>

        <form action="update_buff.php" method="POST">
          <input type="hidden" name="id" id="edit_buff_id">

          <label>Buffed Pieces</label>
          <input type="number" name="buffed_pcs" id="edit_buffed_pcs" min="1" required>

          <label>Date</label>
          <input type="date" name="buffing_date" id="edit_buffing_date" required>

          <label>Description</label>
          <textarea name="description" id="edit_buffing_description"></textarea>

          <div class="form-row">
            <button type="submit" class="btn-save">Update</button>
            <button type="button" class="btn-cancel"
                onclick="closeModal('editBuffingModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="section">
      <button class="section-header" id="toggleHistoryHeader" aria-expanded="false">
        <span>Recent Buffing History</span>
        <span class="arrow" aria-hidden="true">▾</span>
      </button>
      <div id="historyContent" class="section-content" hidden>
        <div class="table-wrapper">
          <table class="log-table" id="buffingHistoryTable">
            <thead>
              <tr>
                <th>Handle Name</th>
                <th>Buffed Pcs</th>
                <th>Date</th>
                <th>Order #</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($history)): ?>
                <tr><td colspan="6">No history found</td></tr>
              <?php else: ?>
                <?php foreach ($history as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['handle_name'] ?? 'N/A'); ?></td>
                      <td><?php echo (int)$r['buffed_pcs']; ?></td>
                      <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($r['buffing_date']))); ?></td>
                      <td><?php echo htmlspecialchars($r['order_number'] ?: '--'); ?></td>
                      <td><?php echo htmlspecialchars($r['description'] ?: ''); ?></td>
                      <td>
                        <button class="btn-icon btn-edit" title="Edit"
                            onclick='openEditBuffing(<?php echo json_encode($r); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="btn-icon btn-delete" title="Delete"
                            onclick="deleteBuffing(<?php echo (int)$r['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>

</div></div><footer class="footer">
    <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
</footer>

<script>
const $ = s => document.querySelector(s);
const allBalances = {};

function toggleMenu() {
  const nav = $('#navMenu');
  if (nav) nav.classList.toggle('active');
}

function openModal(id) {
  $('#' + id).style.display = 'flex';
}
function closeModal(id) {
  $('#' + id).style.display = 'none';
}

function toggleBalance(forceShow) {
  const el = $('#overallBalanceDisplay');
  if (!el) return;
  if (forceShow === true) el.style.display = 'block';
  else if (forceShow === false) el.style.display = 'none';
  else el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

/* --- Filter & Sort Logic (New) --- */
function filterAndSortTable() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const sortVal = document.getElementById('sortSelect').value;
    const filterVal = document.getElementById('filterSelect').value; // Material
    
    // Qty Values
    const qtyOp = document.getElementById('qtyOperator').value; 
    const qtyVal = parseInt(document.getElementById('qtyValue').value);

    const tableBody = document.getElementById('balanceTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));

    // 1. Filter
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const material = row.getAttribute('data-material');
        const balance = parseInt(row.getAttribute('data-balance'));
        
        let matches = true;

        // Search
        if (!name.includes(searchVal)) matches = false;

        // Material Filter
        if (filterVal !== 'all' && !material.includes(filterVal)) matches = false;

        // Qty Filter
        if (qtyOp === 'lt' && !isNaN(qtyVal)) {
            if (balance >= qtyVal) matches = false; 
        } else if (qtyOp === 'gt' && !isNaN(qtyVal)) {
            if (balance <= qtyVal) matches = false;
        }

        row.style.display = matches ? '' : 'none';
    });

    // 2. Sort
    rows.sort((a, b) => {
        const nameA = a.getAttribute('data-name');
        const nameB = b.getAttribute('data-name');
        const balA = parseInt(a.getAttribute('data-balance'));
        const balB = parseInt(b.getAttribute('data-balance'));

        if (sortVal === 'name_asc') return nameA.localeCompare(nameB);
        if (sortVal === 'name_desc') return nameB.localeCompare(nameA);
        if (sortVal === 'bal_asc') return balA - balB;
        if (sortVal === 'bal_desc') return balB - balA;
        return 0;
    });

    // Re-append
    rows.forEach(row => tableBody.appendChild(row));
}

function bindToggle(headerId, contentId) {
  const header = $('#' + headerId);
  const content = $('#' + contentId);
  const arrow = header.querySelector('.arrow');
  header.addEventListener('click', () => {
    if (content.hidden) {
      content.hidden = false;
      content.style.maxHeight = content.scrollHeight + 'px';
      content.style.opacity = '1';
      arrow.style.transform = 'rotate(180deg)';
    } else {
      content.style.opacity = '0';
      content.style.maxHeight = '0px';
      arrow.style.transform = 'rotate(0deg)';
      content.hidden = true;
    }
  });
}

function storeBalances() {
  document.querySelectorAll('#overallBalanceTable tbody tr[data-handle-id]').forEach(row => {
    const handleId = row.dataset.handleId;
    const balance = parseInt(row.dataset.balance) || 0;
    allBalances[handleId] = balance;
  });
}
    
function openEditBuffing(row) {
    document.getElementById('edit_buff_id').value = row.id;
    document.getElementById('edit_buffed_pcs').value = row.buffed_pcs;
    document.getElementById('edit_buffing_date').value = row.buffing_date;
    document.getElementById('edit_buffing_description').value = row.description || '';
    openModal('editBuffingModal');
}

function deleteBuffing(id) {
    if (!confirm('Are you sure you want to delete this buffing entry?')) return;
    window.location.href = 'delete_buff.php?id=' + id;
}

$('#buffingHandleSelect').addEventListener('change', function () {
  const id = this.value;
  $('#modalStockBalance').textContent = allBalances[id] ?? '0';
});

document.addEventListener('DOMContentLoaded', () => {
  storeBalances();
  bindToggle('toggleHistoryHeader', 'historyContent');
  $('#openBuffingModalBtn').addEventListener('click', () => {
    $('#buffingHandleSelect').value = '';
    $('#buffingReadyPcs').value = '';
    $('#modalStockBalance').textContent = '...';
    openModal('buffingModal');
  });
  $('#showOverallBalanceBtn').addEventListener('click', () => toggleBalance(true));
  $('#overallBalanceCloseBtn').addEventListener('click', () => toggleBalance(false));
});
</script>

</body>
</html>