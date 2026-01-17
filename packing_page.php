<?php
session_start();
// Role check: Allow only 'packing' or 'admin' roles
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'];
if ($role !== 'packing' && $role !== 'admin') {
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
        (IFNULL(b.total_buffed,0) - IFNULL(p.total_packed,0)) AS balance
    FROM items i
    LEFT JOIN (
        SELECT handle_id, SUM(buffed_pcs) AS total_buffed
        FROM buffing_log
        GROUP BY handle_id
    ) b ON b.handle_id = i.id
    LEFT JOIN (
        SELECT handle_id, SUM(packed_pcs) AS total_packed
        FROM packing_log
        GROUP BY handle_id
    ) p ON p.handle_id = i.id
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

// --- 3. Load Overall Packing Balance (For Modal Logic & Table) ---
$balances = [];
// UPDATED SQL: Added i.material for filtering
$sql_balance = "
    SELECT 
      i.id AS handle_id,
      i.name AS handle_name,
      i.material,
      IFNULL(buf.total_buffed, 0) AS opening_stock,
      IFNULL(pack.total_packed, 0) AS total_packed,
      (IFNULL(buf.total_buffed, 0) - IFNULL(pack.total_packed, 0)) AS balance
    FROM items i
    LEFT JOIN (
        SELECT handle_id, SUM(buffed_pcs) AS total_buffed
        FROM buffing_log
        GROUP BY handle_id
    ) buf ON buf.handle_id = i.id
    LEFT JOIN (
        SELECT handle_id, SUM(packed_pcs) AS total_packed
        FROM packing_log
        GROUP BY handle_id
    ) pack ON pack.handle_id = i.id
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

// --- 4. Load Recent Packing History ---
$history = [];
$sql_history = "
  SELECT p.id, p.handle_id, p.packed_pcs, p.packing_date, p.description, p.order_number, i.name AS handle_name
  FROM packing_log p
  LEFT JOIN items i ON p.handle_id = i.id
  ORDER BY p.id DESC
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
    <title>Packing Department | BPI</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    
    <link rel="stylesheet" href="style_packing.css?v=1.2">
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
<div class="dashboard">

  <header class="header-container">
    <div class="logo-section">
      <img src="Bhavesh Plastic Industries.png" alt="BPI Logo">
      <div class="header-title">
        <h2>Bhavesh Plastic Industries</h2>
        <p>Packing Department</p>
      </div>
    </div>
    <div class="hamburger" onclick="toggleMenu()">☰</div>
    <nav class="nav-bar" id="navMenu">
      <?php if ($role === 'admin'): ?>
        <a href="index.php">Dashboard</a>
      <?php endif; ?>
      <?php if ($role === 'packing'): ?>
            <a href="my_portal.php">My Portal</a>
      <?php endif; ?>
      <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
  </header>

  <main class="main-content">
    
    <?php if (isset($_GET['success'])): ?>
      <div class="form-msg" style="background:#d4edda;color:#155724;padding:1rem;border-radius:8px;margin-bottom:1rem;">
        <?php echo htmlspecialchars($_GET['success']); ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="form-msg" style="background:#f8d7da;color:#721c24;padding:1rem;border-radius:8px;margin-bottom:1rem;">
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
    <?php endif; ?>

    <div class="actions-row">
      <button id="openPackingModalBtn" class="btn-add">Add Ready Packing</button>
      <button id="showOverallBalanceBtn" class="btn-add btn-balance">Show Overall Balance</button>
    </div>

    <div id="overallBalanceDisplay" class="overall-balance-container" style="display:none;">
      <button id="overallBalanceCloseBtn" class="overall-balance-close-btn" onclick="toggleBalance(false)">&times;</button>
      <h3>Overall Handle Balance (Packing Stock)</h3>
      
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
                  <th>Opening Stock</th>
                  <th>Total Packed</th>
                  <th>Balance</th>
               </tr>
            </thead>
            <tbody id="balanceTableBody">
               <?php if (empty($balances)): ?>
                 <tr><td colspan="4">No handle data found</td></tr>
               <?php else: ?>
                 <?php foreach ($balances as $b): 
                    // Normalize material for filtering
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
                     <td><?php echo (int)$b['total_packed']; ?></td>
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

    <div id="packingModal" class="modal">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('packingModal')">&times;</button>
        <h3>Add Ready Packing</h3>
        <form action="save_packing_log.php" method="POST">
          <label>Handle</label>
          <select id="packingHandleSelect" name="handle_id" required>
            <option value="">Select a Handle</option>
            <?php foreach ($handles as $h): ?>
              <option value="<?php echo (int)$h['id']; ?>"><?php echo htmlspecialchars($h['full_name']); ?></option>
            <?php endforeach; ?>
          </select>
          <label>Packed Pieces</label>
          <input id="packingReadyPcs" name="packed_pcs" type="number" min="1" required>
          <small>Max available: <span id="modalStockBalance">...</span></small>
          <label>For Order (Optional)</label>
          <select name="order_number" id="packingOrderSelect">
            <option value="">-- General Stock --</option>
            <?php foreach ($pendingOrders as $o): ?>
              <option value="<?php echo htmlspecialchars($o['order_number']); ?>">
                Order #<?php echo htmlspecialchars($o['order_number']); ?> (<?php echo htmlspecialchars($o['customer_name'] ?? 'N/A'); ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <label>Date</label>
          <input id="packingDate" name="packing_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>
          <label>Description (Optional)</label>
          <textarea id="packingDescription" name="description" rows="3"></textarea>
          <div class="form-row">
            <button type="submit" class="btn-save">Save Entry</button>
            <button type="button" class="btn-cancel" onclick="closeModal('packingModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
      
    <div id="editPackingModal" class="modal">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('editPackingModal')">&times;</button>
        <h3>Edit Packing Entry</h3>
        <form action="update_packing.php" method="POST">
          <input type="hidden" name="id" id="edit_pack_id">
          <label>Packed Pieces</label>
          <input type="number" name="packed_pcs" id="edit_packed_pcs" min="1" required>
          <label>Date</label>
          <input type="date" name="packing_date" id="edit_packing_date" required>
          <label>Description</label>
          <textarea name="description" id="edit_packing_description"></textarea>
          <div class="form-row">
            <button type="submit" class="btn-save">Update</button>
            <button type="button" class="btn-cancel" onclick="closeModal('editPackingModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="section">
      <button class="section-header" id="toggleHistoryHeader" aria-expanded="false">
        <span>Packing Records History</span>
        <span class="arrow" aria-hidden="true">▾</span>
      </button>
      <div id="historyContent" class="section-content" hidden>
        <div class="table-wrapper">
          <table class="log-table" id="packingHistoryTable">
            <thead>
              <tr>
                <th>Handle Name</th>
                <th>Packed Pcs</th>
                <th>Date</th>
                <th>Order No.</th>
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
                      <td><?php echo (int)$r['packed_pcs']; ?></td>
                      <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($r['packing_date']))); ?></td>
                      <td><?php echo htmlspecialchars($r['order_number'] ?: '--'); ?></td>
                      <td><?php echo htmlspecialchars($r['description'] ?: ''); ?></td>
                      <td>
                        <button class="btn-icon btn-edit" title="Edit"
                            onclick='openEditPacking(<?php echo json_encode($r); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" title="Delete"
                            onclick="deletePacking(<?php echo (int)$r['id']; ?>)">
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
</div>
    
<footer class="footer">
    <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
</footer>

<script>
const $ = s => document.querySelector(s);
const allBalances = {};

// Load balances for modal dropdown logic
document.querySelectorAll('#overallBalanceTable tbody tr[data-handle-id]').forEach(row => {
  const id = row.dataset.handleId;
  const bal = parseInt(row.dataset.balance) || 0;
  allBalances[id] = bal;
});

function toggleMenu() {
  $('#navMenu').classList.toggle('active');
}
function openModal(id) { $('#' + id).style.display = 'flex'; }
function closeModal(id) { $('#' + id).style.display = 'none'; }
    
function toggleBalance(forceShow) {
  const el = $('#overallBalanceDisplay');
  if (!el) return;
  if (forceShow === true) el.style.display = 'block';
  else if (forceShow === false) el.style.display = 'none';
  else el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

/* --- Filter & Sort Logic --- */
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
  const header = document.getElementById(headerId);
  const content = document.getElementById(contentId);
  if (!header || !content) return;
  const arrow = header.querySelector('.arrow');
  header.addEventListener('click', () => {
    if (content.hidden) {
      content.hidden = false;
      content.style.maxHeight = content.scrollHeight + 'px';
      content.style.opacity = '1';
      if (arrow) arrow.style.transform = 'rotate(180deg)';
    } else {
      content.style.opacity = '0';
      content.style.maxHeight = '0px';
      content.hidden = true;
      if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
  });
}
    
function openEditPacking(row) {
    document.getElementById('edit_pack_id').value = row.id;
    document.getElementById('edit_packed_pcs').value = row.packed_pcs;
    document.getElementById('edit_packing_date').value = row.packing_date;
    document.getElementById('edit_packing_description').value = row.description || '';
    openModal('editPackingModal');
}

function deletePacking(id) {
    if (!confirm('Are you sure you want to delete this packing entry?')) return;
    window.location.href = 'delete_packing.php?id=' + id;
}

document.addEventListener('DOMContentLoaded', () => {
  bindToggle('toggleHistoryHeader', 'historyContent');
  
  $('#openPackingModalBtn').addEventListener('click', () => {
    $('#packingHandleSelect').value = '';
    $('#packingReadyPcs').value = '';
    $('#modalStockBalance').textContent = '...';
    openModal('packingModal');
  });
  
  $('#packingHandleSelect').addEventListener('change', function() {
    const id = this.value;
    $('#modalStockBalance').textContent = allBalances[id] ?? '0';
  });
  
  $('#showOverallBalanceBtn').addEventListener('click', () => toggleBalance(true));
  $('#overallBalanceCloseBtn').addEventListener('click', () => toggleBalance(false));
});
</script>
</body>
</html>