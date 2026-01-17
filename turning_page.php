<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'];
if ($role !== 'turning' && $role !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}

// --- DB connection ---
$DB_HOST = "sql100.infinityfree.com";
$DB_USER = "if0_39812412";
$DB_PASS = "Bpiapp0101";
$DB_NAME = "if0_39812412_bpi_stock";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die("DB connection failed");
$conn->set_charset("utf8mb4");

// --- Load pending orders ---
$pendingOrders = [];
$sql = "SELECT order_number, customer_name FROM orders WHERE status = 'pending' ORDER BY id DESC";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) $pendingOrders[] = $r;
$res->close();

// --- 1. LOAD OVERALL BALANCES (For Modal & Dropdown) ---
$balances = [];
$sql = "
SELECT 
  i.id AS handle_id,
  i.name AS handle_name,
  i.size,
  i.material,

  CASE 
    WHEN i.material = 'Malleable'
      THEN (IFNULL(r.total_raw, 0) - IFNULL(rt.total_transferred, 0))
    ELSE IFNULL(b.total_bent, 0)
  END AS opening_stock,

  IFNULL(t.total_turned, 0) AS total_turned,

  (
    CASE 
      WHEN i.material = 'Malleable'
        THEN (IFNULL(r.total_raw, 0) - IFNULL(rt.total_transferred, 0))
      ELSE IFNULL(b.total_bent, 0)
    END
    - IFNULL(t.total_turned, 0)
  ) AS balance

FROM items i
LEFT JOIN (SELECT handle_id, SUM(bent_pcs) AS total_bent FROM bending_log GROUP BY handle_id) b ON b.handle_id = i.id
LEFT JOIN (SELECT handle_id, SUM(ready_pcs) AS total_turned FROM turning_log GROUP BY handle_id) t ON t.handle_id = i.id
LEFT JOIN (SELECT handle_id, SUM(total_pcs) AS total_raw FROM raw_casting_log GROUP BY handle_id) r ON r.handle_id = i.id
LEFT JOIN (SELECT handle_id, SUM(quantity) AS total_transferred FROM raw_transfers GROUP BY handle_id) rt ON rt.handle_id = i.id

WHERE i.material IN ('Malleable','Stainless Steel (SS)')
HAVING balance > 0
ORDER BY i.name ASC
";

$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) $balances[] = $r;
$res->close();

// --- 2. Load Recent History ---
$history = [];
$sql = "
  SELECT t.id, t.handle_id, t.ready_pcs, t.turning_date, t.description, t.order_number, i.name AS handle_name
  FROM turning_log t
  LEFT JOIN items i ON t.handle_id = i.id
  ORDER BY t.id DESC
  LIMIT 50
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) $history[] = $r;
$res->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Turning Department | BPI</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    <link rel="stylesheet" href="style_turning.css?v=1.7">
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
      <img src="Bhavesh Plastic Industries.png" alt="BPI Logo" class="logo-img">
      <div class="header-title">
        <h2>Bhavesh Plastic Industries</h2>
        <p>Turning Department</p>
      </div>
    </div>
    <div class="hamburger" onclick="toggleMenu()">☰</div>
    <nav class="nav-bar" id="navMenu">
      <?php if ($role === 'admin'): ?>
        <a href="index.php">Dashboard</a>
      <?php endif; ?>
      <?php if ($role === 'turning'): ?>
            <a href="my_portal.php">My Portal</a>
      <?php endif; ?>
      <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
  </header>

  <main class="main-content">
    <?php if (isset($_GET['success'])): ?>
      <div style="background:#c8f7c5;padding:10px;border-radius:8px;margin-bottom:12px;">✅ Turning entry saved successfully.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div style="background:#f7c5c5;padding:10px;border-radius:8px;margin-bottom:12px;color:#600"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="actions-row">
      <button id="openTurningModalBtn" class="btn-add">Add Ready Turning</button>
      <button id="showOverallBalanceBtn" class="btn-add btn-balance">Show Overall Balance</button>
    </div>

    <div id="overallBalanceDisplay" class="overall-balance-container" style="display: none;">
      <button id="overallBalanceCloseBtn" class="overall-balance-close-btn" onclick="toggleBalance(true)">&times;</button>
      <h3>Overall Handle Balance (Turning Stock)</h3>
      
      <div class="filter-toolbar">
        <input type="text" id="searchInput" class="filter-input-search" placeholder="Search..." onkeyup="filterAndSortTable()">
        
        <select id="filterSelect" class="filter-select" onchange="filterAndSortTable()">
            <option value="all">All Sources</option>
            <option value="casting">Casting (Mall.)</option>
            <option value="bent">Bent Stock</option>
        </select>

        <select id="sortSelect" class="filter-select" onchange="filterAndSortTable()">
            <option value="name_asc">Name (A-Z)</option>
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
                  <th>Source</th>
                  <th>Opening Stock</th>
                  <th>Total Turned</th>
                  <th>Balance (Ready to Turn)</th>
               </tr>
            </thead>
            <tbody id="balanceTableBody">
               <?php if (count($balances) === 0): ?>
                 <tr><td colspan="5">No handle data found</td></tr>
               <?php else: ?>
                 <?php foreach ($balances as $b): 
                    $sourceType = ($b['material'] == 'Malleable') ? 'casting' : 'bent';
                 ?>
                   <tr data-handle-id="<?php echo (int)$b['handle_id']; ?>"
                       data-name="<?php echo strtolower(htmlspecialchars($b['handle_name'])); ?>"
                       data-balance="<?php echo (int)$b['balance']; ?>"
                       data-source="<?php echo $sourceType; ?>">
                     <td>
                        <?php echo htmlspecialchars($b['handle_name']); ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($b['material']); ?></small>
                     </td>
                     <td>
                        <?php if($b['material'] == 'Malleable'): ?>
                            <span style="color:#d63384; font-size:0.85em; font-weight:bold;">Casting</span>
                        <?php else: ?>
                            <span style="color:#007bff; font-size:0.85em; font-weight:bold;">Bent Stock</span>
                        <?php endif; ?>
                     </td>
                     <td><?php echo (int)$b['opening_stock']; ?></td>
                     <td><?php echo (int)$b['total_turned']; ?></td>
                     <td style="font-weight:bold; font-size:1.1em; color:<?php echo ($b['balance'] < 50) ? '#e74c3c' : '#28a745'; ?>;">
                        <?php echo (int)$b['balance']; ?>
                     </td>
                   </tr>
                 <?php endforeach; ?>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
    </div>

    <div id="turningModal" class="modal" style="display:none;">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('turningModal')">&times;</button>
        <h3>Add Ready Turning</h3>
        <form action="save_turning.php" method="POST">
          <label>Handle</label>
          <select id="turningHandleSelect" name="handle_id" required>
            <option value="">Select a Handle</option>
            <?php
            foreach ($balances as $b):
                if ($b['balance'] > 0): 
                    $fullName = $b['handle_name'];
                    if (!empty($b['size'])) $fullName .= " — " . $b['size'];
                    if (!empty($b['material'])) $fullName .= " — " . $b['material'];
                    $fullName .= " [Qty: " . $b['balance'] . "]";
            ?>
                <option value="<?php echo (int)$b['handle_id']; ?>">
                    <?php echo htmlspecialchars($fullName); ?>
                </option>
            <?php 
                endif;
            endforeach; 
            ?>
          </select>

          <label>Ready Pieces</label>
          <input id="turningReadyPcs" name="ready_pcs" type="number" min="1" required>
          <small>Max available: <span id="modalStockBalance" style="font-weight:bold;">...</span></small>

          <label>For Order (Optional)</label>
          <select name="order_number" id="turningOrderSelect">
            <option value="">-- General Stock --</option>
            <?php foreach ($pendingOrders as $o): ?>
              <option value="<?php echo htmlspecialchars($o['order_number']); ?>">
                Order No.<?php echo htmlspecialchars($o['order_number']); ?> (<?php echo htmlspecialchars($o['customer_name'] ?: 'N/A'); ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <label>Date</label>
          <input id="turningDate" name="turning_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>

          <label>Description (Optional)</label>
          <textarea id="turningDescription" name="description" rows="3"></textarea>

          <div class="form-row">
            <button type="submit" class="btn-save">Save Entry</button>
            <button type="button" class="btn-cancel" onclick="closeModal('turningModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
      
    <div id="editTurningModal" class="modal" style="display:none;">
      <div class="modal-panel">
        <button class="close-x" onclick="closeModal('editTurningModal')">&times;</button>
        <h3>Edit Turning Entry</h3>

        <form action="update_turning.php" method="POST">
          <input type="hidden" name="id" id="edit_id">

          <label>Ready Pieces</label>
          <input type="number" name="ready_pcs" id="edit_ready_pcs" min="1" required>

          <label>Date</label>
          <input type="date" name="turning_date" id="edit_turning_date" required>

          <label>Description</label>
          <textarea name="description" id="edit_description"></textarea>

          <div class="form-row">
            <button type="submit" class="btn-save">Update</button>
            <button type="button" class="btn-cancel"
                onclick="closeModal('editTurningModal')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="section">
      <button class="section-header" id="toggleHistoryHeader" aria-expanded="false">
        <span>Turning Record</span>
        <span class="arrow" aria-hidden="true">▾</span>
      </button>
      <div id="historyContent" class="section-content" hidden>
        <div class="table-wrapper">
          <table class="log-table" id="turningHistoryTable">
            <thead>
              <tr>
                <th>Handle Name</th>
                <th>Ready Pcs</th>
                <th>Date</th>
                <th>Order No.</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($history) === 0): ?>
                <tr><td colspan="6">No history found</td></tr>
              <?php else: ?>
                <?php foreach ($history as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['handle_name'] ?? 'N/A'); ?></td>
                    <td><?php echo (int)$r['ready_pcs']; ?></td>
                    <td><?php echo htmlspecialchars($r['turning_date']); ?></td>
                    <td><?php echo htmlspecialchars($r['order_number'] ?: '--'); ?></td>
                    <td><?php echo htmlspecialchars($r['description'] ?: ''); ?></td>
                    <td>
                        <button class="btn-icon btn-edit" title="Edit"
                            onclick='openEditTurning(<?php echo json_encode($r); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="btn-icon btn-delete" title="Delete"
                            onclick="deleteTurning(<?php echo (int)$r['id']; ?>)">
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

function toggleMenu() {
    const nav = document.getElementById('navMenu');
    nav.classList.toggle('active');
}

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function toggleBalance(forceShow) {
    const el = document.getElementById('overallBalanceDisplay');
    if (!el) return;
    if (forceShow === true) el.style.display = 'block';
    else if (forceShow === false) el.style.display = 'none';
    else el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

/* Updated Logic for Filtering and Sorting */
function filterAndSortTable() {
    // 1. Get Values
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const sortVal = document.getElementById('sortSelect').value;
    const filterVal = document.getElementById('filterSelect').value; // Source
    
    // Qty Values
    const qtyOp = document.getElementById('qtyOperator').value; // all, lt, gt
    const qtyVal = parseInt(document.getElementById('qtyValue').value);

    const tableBody = document.getElementById('balanceTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));

    // 2. Filter Rows
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const source = row.getAttribute('data-source');
        const balance = parseInt(row.getAttribute('data-balance'));
        
        let matches = true;

        // Search Name
        if (!name.includes(searchVal)) matches = false;

        // Source Filter
        if (filterVal !== 'all' && source !== filterVal) matches = false;

        // Qty Filter
        if (qtyOp === 'lt' && !isNaN(qtyVal)) {
            if (balance >= qtyVal) matches = false; 
        } else if (qtyOp === 'gt' && !isNaN(qtyVal)) {
            if (balance <= qtyVal) matches = false;
        }

        // Apply
        row.style.display = matches ? '' : 'none';
    });

    // 3. Sort Visible Rows
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

    // Re-append sorted rows
    rows.forEach(row => tableBody.appendChild(row));
}

function openEditTurning(row) {
    document.getElementById('edit_id').value = row.id;
    document.getElementById('edit_ready_pcs').value = row.ready_pcs;
    document.getElementById('edit_turning_date').value = row.turning_date;
    document.getElementById('edit_description').value = row.description || '';
    openModal('editTurningModal');
}

function deleteTurning(id) {
    if (!confirm('Are you sure you want to delete this turning entry?')) return;
    window.location.href = 'delete_turning.php?id=' + id;
}
    
function bindToggle(headerId, contentId) {
    const header = document.getElementById(headerId);
    const content = document.getElementById(contentId);
    if(!header || !content) return;
    const arrow = header.querySelector('.arrow');
    header.addEventListener('click', () => {
        if (content.hidden) {
            content.hidden = false;
            content.style.maxHeight = content.scrollHeight + 'px';
            content.style.opacity = '1';
            if(arrow) arrow.style.transform = 'rotate(180deg)';
        } else {
            content.style.opacity = '0';
            content.style.maxHeight = '0px';
            content.hidden = true;
            if(arrow) arrow.style.transform = 'rotate(0deg)';
        }
    });
}
bindToggle('toggleHistoryHeader', 'historyContent');

const openBtn = document.getElementById('openTurningModalBtn');
if(openBtn) {
    openBtn.addEventListener('click', () => {
        document.getElementById('modalStockBalance').textContent = '...';
        document.getElementById('turningReadyPcs').value = '';
        document.getElementById('turningDescription').value = '';
        openModal('turningModal');
    });
}

const showBalBtn = document.getElementById('showOverallBalanceBtn');
if(showBalBtn) showBalBtn.addEventListener('click', () => toggleBalance(true));

const closeBalBtn = document.getElementById('overallBalanceCloseBtn');
if(closeBalBtn) closeBalBtn.addEventListener('click', () => toggleBalance(false));

const handleSelect = document.getElementById('turningHandleSelect');
if(handleSelect) {
    handleSelect.addEventListener('change', function () {
        const handleId = this.value;
        const balanceSpan = document.getElementById('modalStockBalance');
        if (!handleId) {
            balanceSpan.textContent = '...';
            return;
        }
        // Look up balance from the hidden table logic
        const row = document.querySelector(`#overallBalanceTable tbody tr[data-handle-id="${handleId}"]`);
        if (row) {
            const balance = parseInt(row.getAttribute('data-balance')) || 0;
            balanceSpan.textContent = balance;
        } else {
            balanceSpan.textContent = '0';
        }
    });
}
</script>
</body>
</html>
<?php $conn->close(); ?>