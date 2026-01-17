<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'];
if ($role !== 'casting' && $role !== 'admin') {
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

// --- 1. Load Malleable Handles ---
$handles = [];
$sql_handles = "SELECT id, name, size FROM items WHERE material = 'Malleable' ORDER BY name ASC";
if ($res_handles = $conn->query($sql_handles)) {
    while ($r = $res_handles->fetch_assoc()) $handles[] = $r;
    $res_handles->close();
}

// --- 2. Load Pending Orders ---
$pendingOrders = [];
$sql_orders = "SELECT order_number, customer_name FROM orders WHERE status = 'pending' ORDER BY id DESC";
if ($res_orders = $conn->query($sql_orders)) {
    while ($r = $res_orders->fetch_assoc()) $pendingOrders[] = $r;
    $res_orders->close();
}

// --- 3. Load Metal Balance ---
$balance_metalin = 0;
$balance_raw = 0;
$sql_bal_metalin = "SELECT SUM(metalin_weight) AS total_metalin FROM casting_log";
if ($res_bal_m = $conn->query($sql_bal_metalin)) {
    $balance_metalin = (float)($res_bal_m->fetch_assoc()['total_metalin'] ?? 0);
    $res_bal_m->close();
}
$sql_bal_raw = "SELECT SUM(total_weight_kg) AS total_raw FROM raw_casting_log";
if ($res_bal_r = $conn->query($sql_bal_raw)) {
    $balance_raw = (float)($res_bal_r->fetch_assoc()['total_raw'] ?? 0);
    $res_bal_r->close();
}
$balance_final = $balance_metalin - $balance_raw;


// --- 4. Load Casting Records History (Metal Input) ---
$casting_history = [];
$sql_cast_history = "SELECT id, casting_date, metalin_weight, total_pcs, melo, description, order_number 
                      FROM casting_log 
                      ORDER BY casting_date DESC, id DESC LIMIT 50";
if ($res_cast_hist = $conn->query($sql_cast_history)) {
    while ($r = $res_cast_hist->fetch_assoc()) $casting_history[] = $r;
    $res_cast_hist->close();
}

// --- 5. Load Raw Casting History (Output) ---
$raw_casting_history = [];
$sql_raw_history = "SELECT r.id, r.handle_id, i.name AS handle_name, r.total_weight_kg, r.weight_per_piece_g, 
                           r.total_pcs, r.created_at, r.order_number, r.description, r.is_outsourced
                    FROM raw_casting_log r
                    JOIN items i ON r.handle_id = i.id
                    ORDER BY r.created_at DESC LIMIT 50";
if ($res_raw_hist = $conn->query($sql_raw_history)) {
    while ($r = $res_raw_hist->fetch_assoc()) $raw_casting_history[] = $r;
    $res_raw_hist->close();
}

/* ----- 6. RAW CASTING STOCK (Detailed Breakdown) ----- */
$rawHandleQuery = "
SELECT 
    i.id AS handle_id,
    i.name AS handle_name,
    i.size AS handle_size,
    
    COALESCE((SELECT SUM(total_pcs) FROM raw_casting_log WHERE handle_id = i.id), 0) AS total_produced,
    COALESCE((SELECT SUM(quantity) FROM raw_transfers WHERE handle_id = i.id), 0) AS total_transferred,
    
    (
        COALESCE((SELECT SUM(total_pcs) FROM raw_casting_log WHERE handle_id = i.id), 0) - 
        COALESCE((SELECT SUM(quantity) FROM raw_transfers WHERE handle_id = i.id), 0)
    ) AS total_available_raw

FROM items i
WHERE i.material = 'Malleable'
HAVING total_available_raw > 0
ORDER BY i.name ASC;
";
$rawHandleResult = $conn->query($rawHandleQuery);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Casting Department | BPI</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    <link rel="stylesheet" href="style_casting.css?v=<?php echo filemtime('style_casting.css'); ?>"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Small inline overrides if needed, otherwise using style_casting.css */
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
            <p>Casting Department</p>
          </div>
        </div>
        <div class="hamburger" onclick="toggleMenu()">☰</div>
        <nav class="nav-bar" id="navMenu">
          <?php if ($role === 'admin'): ?>
            <a href="index.php">Dashboard</a>
          <?php endif; ?>
          <?php if ($role === 'casting'): ?>
            <a href="my_portal.php">My Portal</a>
          <?php endif; ?>
          <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </header>

    <main class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="form-msg success" style="display:block; background-color: #d4edda; color: #155724; border-color: #c3e6cb; padding:15px; border-radius:8px; margin-bottom:15px;"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="form-msg error" style="display:block; background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding:15px; border-radius:8px; margin-bottom:15px;"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="actions-row">
            <button id="openFormBtn" class="btn-add"><i class="fas fa-plus"></i> Add Metal</button>
            <button id="openRawBtn" class="btn-add"><i class="fas fa-cubes"></i> Add Casting</button>
            <button id="showBalanceBtn" class="btn-add btn-balance"><i class="fas fa-scale-balanced"></i> Show Metal Balance</button>
        </div>
        
        <div id="balanceDisplay" class="balance-container" style="display: none;">
            <button id="balanceCloseBtn" class="balance-close-btn" onclick="toggleBalance(false)">&times;</button>
            <h3>Metal Balance</h3>
            <div class="balance-row">
              <span>Total Metal-In</span>
              <span id="balanceMetalin"><?php echo number_format($balance_metalin, 2); ?> kg</span>
            </div>
            <div class="balance-row">
              <span>Total Metal Used (Raw)</span>
              <span id="balanceRaw"><?php echo number_format($balance_raw, 2); ?> kg</span>
            </div>
            <div class="balance-row total">
              <span>BALANCE IN STOCK</span>
              <span id="balanceFinal"><?php echo number_format($balance_final, 2); ?> kg</span>
            </div>
        </div>

        <div id="castingModal" class="modal" style="display:none;">
          <div class="modal-panel">
            <button class="close-x" onclick="closeModal('castingModal')">&times;</button>
            <h3>New Casting Entry</h3>
            <form id="castingForm" action="save_casting.php" method="POST">
              <label>Metal-In (Weight in kg)</label>
              <input name="metalin_weight" type="number" step="0.01" required>
              <label>Total Pcs</label>
              <input name="total_pcs" type="number" required>
              <label>Melo (in kg)</label>
              <input name="melo" type="text" required>

              <label>For Order (Optional)</label>
              <select name="order_number" id="castingOrderSelect">
                  <option value="">-- General Stock --</option>
                  <?php foreach ($pendingOrders as $order): ?>
                      <option value="<?php echo htmlspecialchars($order['order_number']); ?>">
                          Order #<?php echo htmlspecialchars($order['order_number']); ?>
                      </option>
                  <?php endforeach; ?>
              </select>

              <label>Description (Optional)</label>
              <textarea name="description" rows="3"></textarea>
              <label>Date</label>
              <input name="casting_date" type="date" required value="<?php echo date('Y-m-d'); ?>">
              <div class="form-row">
                <button type="submit" class="btn-save">Save Entry</button> <button type="button" class="btn-cancel" onclick="closeModal('castingModal')">Cancel</button>
              </div>
              </form>
          </div>
        </div>

        <div id="rawCastingModal" class="modal" style="display:none;">
           <div class="modal-panel">
            <button class="close-x" onclick="closeModal('rawCastingModal')">&times;</button>
            <h3>Add Raw Casting</h3>
            <form id="rawCastingForm" action="save_raw_casting.php" method="POST">
                <label>Handle (Malleable)</label>
                    <select id="rawHandle" name="handle_id" required>
                        <option value="">Select Handle</option>
                        <?php foreach ($handles as $handle): ?>
                            <option value="<?php echo (int)$handle['id']; ?>">
                                <?php echo htmlspecialchars($handle['name']) . ' (' . htmlspecialchars($handle['size']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <label>Total Weight (kg)</label>
                <input id="rawTotalWeight" name="total_weight_kg" type="number" step="0.01" required>
                <label>Weight per Piece (g)</label>
                <input id="rawWeightPerPiece" name="weight_per_piece_g" type="number" step="0.01" required>
                <label>Total Pieces</label>
                <input id="rawTotalPcs" name="total_pcs" type="number" readonly>

                <label>For Order (Optional)</label>
                <select name="order_number" id="rawOrderSelect">
                      <option value="">-- General Stock --</option>
                      <?php foreach ($pendingOrders as $order): ?>
                         <option value="<?php echo htmlspecialchars($order['order_number']); ?>">
                            Order #<?php echo htmlspecialchars($order['order_number']); ?>
                         </option>
                      <?php endforeach; ?>
                </select>

                <label>Description (Optional)</label>
                <textarea id="rawDescription" name="description" rows="3"></textarea>
                <div class="form-checkbox-row">
                  <input id="rawOutsourced" name="is_outsourced" type="checkbox" value="1">
                  <label for="rawOutsourced">Outsourced</label>
                </div>
                <div class="form-row">
                  <button type="submit" class="btn-save" id="saveRawBtn">Save Raw Casting</button> <button type="button" class="btn-cancel" onclick="closeModal('rawCastingModal')">Cancel</button>
                </div>
                </form>
          </div>
        </div>
        
        <div id="editCastingModal" class="modal" style="display:none;">
          <div class="modal-panel">
            <button class="close-x" onclick="closeModal('editCastingModal')">&times;</button>
            <h3>Edit Casting Entry</h3>
            <form id="editCastingForm">
              <input type="hidden" id="editCastingId" name="id">
              
              <label>Metal-In (Weight in kg)</label>
              <input id="editCastingMetalin" name="metalin_weight" type="number" step="0.01" required>
              
              <label>Total Pcs</label>
              <input id="editCastingPcs" name="total_pcs" type="number" required>
              
              <label>Melo (in kg)</label>
              <input id="editCastingMelo" name="melo" type="text">
              
              <label>For Order</label>
              <select id="editCastingOrder" name="order_number">
                  <option value="">-- General Stock --</option>
                  <?php foreach ($pendingOrders as $order): ?>
                      <option value="<?php echo htmlspecialchars($order['order_number']); ?>">
                          Order #<?php echo htmlspecialchars($order['order_number']); ?>
                      </option>
                  <?php endforeach; ?>
              </select>
              
              <label>Description</label>
              <textarea id="editCastingDesc" name="description" rows="3"></textarea>
              
              <label>Date</label>
              <input id="editCastingDate" name="casting_date" type="date" required>
              
              <div class="form-row">
                <button type="button" class="btn-save" onclick="submitEditCasting()">Update</button> 
                <button type="button" class="btn-cancel" onclick="closeModal('editCastingModal')">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div id="editRawModal" class="modal" style="display:none;">
           <div class="modal-panel">
            <button class="close-x" onclick="closeModal('editRawModal')">&times;</button>
            <h3>Edit Raw Casting</h3>
            <form id="editRawForm">
                <input type="hidden" id="editRawId" name="id">
                <input type="hidden" id="editRawHandleId" name="handle_id">
                
                <label>Handle (Read Only)</label>
                <input type="text" id="editRawHandleName" readonly style="background:#f0f0f0; color:#555;">
                
                <label>Total Weight (kg)</label>
                <input id="editRawTotalWeight" name="total_weight_kg" type="number" step="0.01" required>
                
                <label>Weight per Piece (g)</label>
                <input id="editRawWeightPerPiece" name="weight_per_piece_g" type="number" step="0.01" required>
                
                <label>Total Pieces (Auto Calc)</label>
                <input id="editRawTotalPcs" name="total_pcs" type="number" readonly style="background:#f0f0f0; font-weight:bold;">
                
                <label>Description</label>
                <textarea id="editRawDescription" name="description" rows="3"></textarea>
                
                <div class="form-checkbox-row">
                  <input id="editRawOutsourced" name="is_outsourced" type="checkbox" value="1"> 
                  <label for="editRawOutsourced">Outsourced</label>
                </div>
                
                <div class="form-row">
                  <button type="button" class="btn-save" onclick="submitEditRaw()">Update</button>
                  <button type="button" class="btn-cancel" onclick="closeModal('editRawModal')">Cancel</button>
                </div>
            </form>
          </div>
        </div>

        <div id="transferModal" class="modal" style="display:none;">
            <div class="modal-panel">
                <button class="close-x" onclick="closeModal('transferModal')">&times;</button>
                <h3>Transfer to Finished Stock</h3>
                <p>Moves items from Casting Dept -> Admin Stock List</p>
                <form id="transferForm">
                    <input type="hidden" id="transferHandleId" name="handle_id">
                    <label>Item Name</label>
                    <input type="text" id="transferHandleName" readonly style="background:#f0f0f0; border:1px solid #ccc; padding:8px; width:100%; margin-bottom:10px;">
                    <label>Available Raw Pcs</label>
                    <input type="number" id="transferAvailable" readonly style="background:#f0f0f0; border:1px solid #ccc; padding:8px; width:100%; margin-bottom:10px;">
                    <label>Quantity to Transfer</label>
                    <input type="number" id="transferQty" name="quantity" required min="1" style="padding:8px; width:100%;">
                    <div class="form-row" style="margin-top:20px;">
                        <button type="button" class="btn-save" onclick="submitTransfer()">Transfer</button>
                        <button type="button" class="btn-cancel" onclick="closeModal('transferModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="section">
          <button class="section-header" id="toggleCastingHeader">
            <span>Metal Records</span> <span class="arrow">▾</span>
          </button>
          <div id="castingContent" class="section-content" hidden>
             <div class="table-wrapper">
                 <table class="log-table">
                    <thead>
                        <tr>
                            <th>Metalin (kg)</th>
                            <th>Pcs</th>
                            <th>Melo</th>
                            <th>Date</th>
                            <th>Desc</th>
                            <th>Order #</th>
                            <th>Action</th> </tr>
                    </thead>
                    <tbody>
                        <?php foreach($casting_history as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['metalin_weight']); ?></td>
                            <td><?php echo htmlspecialchars($c['total_pcs']); ?></td>
                            <td><?php echo htmlspecialchars($c['melo']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['casting_date'])); ?></td>
                            <td><?php echo htmlspecialchars($c['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($c['order_number'] ?? '--'); ?></td>
                            <td>
                                <button class="btn-icon btn-edit" onclick="openEditCasting(
                                    <?php echo $c['id']; ?>, 
                                    <?php echo $c['metalin_weight']; ?>, 
                                    <?php echo $c['total_pcs']; ?>, 
                                    '<?php echo addslashes($c['melo']); ?>', 
                                    '<?php echo $c['casting_date']; ?>', 
                                    '<?php echo addslashes($c['description'] ?? ''); ?>',
                                    '<?php echo addslashes($c['order_number'] ?? ''); ?>'
                                )"><i class="fas fa-edit"></i></button>
                                
                                <button class="btn-icon btn-delete" onclick="deleteCasting(<?php echo $c['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                 </table>
             </div>
          </div>
        </div>
        
        <div class="section">
          <button class="section-header" id="toggleRawHeader">
            <span>Casting Records</span>
            <span class="arrow">▾</span>
          </button>
          <div id="rawContent" class="section-content" hidden>
            <div class="table-wrapper">
              <table class="log-table" id="rawTable">
                 <thead>
                  <tr>
                    <th>Handle</th>
                    <th>Weight (kg)</th>
                    <th>Wt/Pcs (g)</th>
                    <th>Pcs</th>
                    <th>Date</th>
                    <th>Order #</th>
                    <th>Desc</th>
                    <th>Outsourced</th>
                    <th>Action</th> </tr>
                </thead>
                <tbody>
                    <?php if (empty($raw_casting_history)): ?>
                        <tr><td colspan="9">No records found</td></tr>
                    <?php else: ?>
                        <?php foreach ($raw_casting_history as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['handle_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['total_weight_kg']); ?></td>
                            <td><?php echo htmlspecialchars($r['weight_per_piece_g']); ?></td>
                            <td><?php echo htmlspecialchars($r['total_pcs']); ?></td>
                            <td><?php echo date('d/m/y', strtotime($r['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($r['order_number'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                            <td><?php echo $r['is_outsourced'] == 1 ? 'Yes' : 'No'; ?></td>
                            <td>
                                <button class="btn-icon btn-edit" title="Edit" 
                                    onclick="openEditRaw(
                                        <?php echo $r['id']; ?>, 
                                        <?php echo $r['handle_id']; ?>, 
                                        '<?php echo addslashes($r['handle_name']); ?>', 
                                        <?php echo $r['total_weight_kg']; ?>, 
                                        <?php echo $r['weight_per_piece_g']; ?>, 
                                        '<?php echo addslashes($r['description'] ?? ''); ?>', 
                                        <?php echo $r['is_outsourced']; ?>
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-delete" title="Delete" 
                                    onclick="deleteRaw(<?php echo $r['id']; ?>)">
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
        
        <h3 class="section-title">Casting Stock</h3>

        <div class="filter-toolbar">
            <input type="text" id="stockSearch" class="filter-input-search" placeholder="Search handle name..." onkeyup="filterStock()">
            
            <span class="filter-label">Sort:</span>
            <select id="stockSort" class="filter-select" onchange="filterStock()">
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="bal_high">Balance (High to Low)</option>
                <option value="bal_low">Balance (Low to High)</option>
            </select>

            <span class="filter-label">Filter Qty:</span>
            <div class="filter-group">
                <select id="filterType" class="filter-select" onchange="filterStock()">
                    <option value="all">Show All</option>
                    <option value="lt">Less Than (<)</option>
                    <option value="gt">Greater Than (>)</option>
                </select>
                <input type="number" id="filterValue" class="filter-input-qty" placeholder="0" onkeyup="filterStock()">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="log-table" id="stockTable">
                <thead>
                    <tr>
                        <th>Handle</th>
                        <th>Total Made</th>
                        <th>Transferred</th>
                        <th>Available Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="stockTableBody">
                    <?php 
                    $rawHandleResult->data_seek(0);
                    while($row = $rawHandleResult->fetch_assoc()): 
                        // Styling Logic
                        $balance = (int)$row['total_available_raw'];
                        $lowStockClass = ($balance < 50) ? 'low-stock' : '';
                    ?>
                    <tr class="stock-row" 
                        data-name="<?php echo strtolower(htmlspecialchars($row['handle_name'])); ?>" 
                        data-balance="<?php echo $balance; ?>">
                        
                        <td>
                            <div class="handle-info">
                                <span class="handle-name"><?php echo htmlspecialchars($row['handle_name']); ?></span>
                                <span class="handle-size"><?php echo htmlspecialchars($row['handle_size']); ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-blue"><?php echo number_format($row['total_produced']); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-purple"><?php echo number_format($row['total_transferred']); ?></span>
                        </td>
                        <td class="balance-cell <?php echo $lowStockClass; ?>">
                            <?php echo number_format($balance); ?>
                        </td>
                        <td>
                             <button class="btn-transfer" 
                                onclick="openTransferModal(<?php echo $row['handle_id']; ?>, '<?php echo addslashes($row['handle_name']); ?>', <?php echo $balance; ?>)">
                                <span>Transfer</span> <i class="fas fa-arrow-right"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <p id="noStockMsg" style="text-align:center; display:none; padding:10px; color:#666;">No matching stock found.</p>
        </div>
        

    </main>
</div>
    
<footer class="footer">
    <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
</footer>

<script>
    /* ===== Helpers ===== */
    const $ = s => document.querySelector(s);
    
    /* ===== Menu & UI ===== */
    function toggleMenu() { $('#navMenu').classList.toggle('active'); }
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    function toggleBalance(show) {
        const display = $('#balanceDisplay');
        if (!display) return;
        display.style.display = (show === false) ? 'none' : 'block';
    }

    /* ===== Stock Search, Sort & Dynamic Filter Logic ===== */
    function filterStock() {
        const input = document.getElementById('stockSearch').value.toLowerCase();
        const sortVal = document.getElementById('stockSort').value;
        const filterType = document.getElementById('filterType').value; // 'all', 'lt', 'gt'
        const filterValue = parseInt(document.getElementById('filterValue').value);
        
        const tableBody = document.getElementById('stockTableBody');
        const rows = Array.from(tableBody.getElementsByClassName('stock-row'));
        let visibleCount = 0;

        // 1. Filter Logic
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const balance = parseInt(row.getAttribute('data-balance'));
            
            let matchesSearch = name.includes(input);
            let matchesFilter = true;

            // Numeric Filter Logic
            if (filterType === 'lt' && !isNaN(filterValue)) {
                if (balance >= filterValue) matchesFilter = false; 
            } else if (filterType === 'gt' && !isNaN(filterValue)) {
                if (balance <= filterValue) matchesFilter = false; 
            }

            if (matchesSearch && matchesFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle "No results" message
        const msg = document.getElementById('noStockMsg');
        if(msg) msg.style.display = (visibleCount === 0) ? 'block' : 'none';

        // 2. Sorting Logic
        rows.sort((a, b) => {
            const nameA = a.getAttribute('data-name');
            const nameB = b.getAttribute('data-name');
            const balA = parseInt(a.getAttribute('data-balance'));
            const balB = parseInt(b.getAttribute('data-balance'));

            switch(sortVal) {
                case 'name_asc': return nameA.localeCompare(nameB);
                case 'name_desc': return nameB.localeCompare(nameA);
                case 'bal_high': return balB - balA;
                case 'bal_low': return balA - balB;
                default: return 0;
            }
        });

        // Re-append rows in new order
        rows.forEach(row => tableBody.appendChild(row));
    }

    /* ===== Transfer Logic ===== */
    function openTransferModal(id, name, available) {
        document.getElementById('transferHandleId').value = id;
        document.getElementById('transferHandleName').value = name;
        document.getElementById('transferAvailable').value = available;
        document.getElementById('transferQty').value = '';
        document.getElementById('transferQty').max = available; 
        document.getElementById('transferModal').style.display = 'flex';
    }

    function submitTransfer() {
        const confirmMsg = 
            "⚠️ WARNING:\n\n" +
            "This action will permanently transfer CASTING stock to FINISHED stock.\n" +
            "Once transferred, this entry CANNOT be reversed or edited.\n\n" +
            "Do you want to continue?";

        if (!confirm(confirmMsg)) return; 

        const form = document.getElementById('transferForm');
        const formData = new FormData(form);

        const qty = parseInt(document.getElementById('transferQty').value);
        const max = parseInt(document.getElementById('transferAvailable').value);

        if (!qty || qty <= 0) {
            alert("Enter a valid quantity");
            return;
        }

        if (qty > max) {
            alert("Cannot transfer more than available raw stock!");
            return;
        }

        fetch('transfer_stock.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(() => alert("System Error: Check console"));
    }


    /* ===== Edit Casting Entry ===== */
    function openEditCasting(id, metal, pcs, melo, date, desc, order) {
        $('#editCastingId').value = id; 
        $('#editCastingMetalin').value = metal;
        $('#editCastingPcs').value = pcs; 
        $('#editCastingMelo').value = melo;
        $('#editCastingDate').value = date; 
        $('#editCastingDesc').value = desc;
        $('#editCastingOrder').value = order;
        openModal('editCastingModal');
    }

    function submitEditCasting() {
        const fd = new FormData($('#editCastingForm'));
        fetch('update_casting_log.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(res => { 
            alert(res.message); 
            if(res.success) location.reload(); 
        })
        .catch(e => alert("System Error: " + e));
    }

    function deleteCasting(id) {
        if(!confirm("Are you sure you want to delete this Metal Input record?")) return;
        
        const fd = new FormData(); 
        fd.append('id', id);
        
        fetch('delete_casting_log.php', { method:'POST', body:fd })
        .then(r=>r.json()).then(res => {
            if(res.success) location.reload(); 
            else alert(res.message);
        })
        .catch(e => alert("System Error: " + e));
    }
    
    /* ===== Edit Raw Entry ===== */
    function openEditRaw(id, handleId, name, weight, perPiece, desc, outsourced) {
        $('#editRawId').value = id;
        $('#editRawHandleId').value = handleId;
        $('#editRawHandleName').value = name;
        $('#editRawTotalWeight').value = weight;
        $('#editRawWeightPerPiece').value = perPiece;
        $('#editRawDescription').value = desc;
        $('#editRawOutsourced').checked = (outsourced == 1);
        
        calcEditPieces(); // Init calculation
        openModal('editRawModal');
    }
    
    // Auto calc for Edit Modal
    $('#editRawTotalWeight').addEventListener('input', calcEditPieces);
    $('#editRawWeightPerPiece').addEventListener('input', calcEditPieces);
    function calcEditPieces() {
        const kg = parseFloat($('#editRawTotalWeight').value) || 0;
        const g = parseFloat($('#editRawWeightPerPiece').value) || 0;
        const total = (g > 0) ? Math.floor((kg * 1000) / g) : 0;
        $('#editRawTotalPcs').value = total;
    }

    function submitEditRaw() {
        const form = document.getElementById('editRawForm');
        const formData = new FormData(form);
        fetch('update_raw_casting.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            alert(res.message);
            if(res.success) location.reload();
        })
        .catch(e => alert("System Error"));
    }

    /* ===== Delete Raw Logic ===== */
    function deleteRaw(id) {
        if(!confirm("Are you sure you want to delete this record? This cannot be undone.")) return;
        
        const fd = new FormData();
        fd.append('id', id);
        
        fetch('delete_raw_casting.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                location.reload();
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(e => alert("System Error"));
    }

    /* ===== Collapsible Sections ===== */
    function bindToggle(headerId, contentId) {
      const header = document.getElementById(headerId);
      const content = document.getElementById(contentId);
      if (!header || !content) return;
      const arrow = header.querySelector('.arrow');
      
      content.addEventListener('transitionend', () => {
        if (content.style.maxHeight === '0px') content.hidden = true;
      });
      header.addEventListener('click', () => {
        if (content.hidden) {
          content.hidden = false;
          content.style.maxHeight = content.scrollHeight + 'px';
          content.style.opacity = '1';
          if(arrow) arrow.style.transform = 'rotate(180deg)';
        } else {
          content.style.opacity = '0';
          content.style.maxHeight = '0px';
          if(arrow) arrow.style.transform = 'rotate(0deg)';
        }
      });
    }
    
    /* ===== Open Modal Buttons ===== */
    const openFormBtn = $('#openFormBtn');
    if (openFormBtn) {
        openFormBtn.addEventListener('click', () => {
            $('#castingForm').reset();
            $('input[name="casting_date"]').value = '<?php echo date('Y-m-d'); ?>';
            openModal('castingModal');
        });
    }
    const openRawBtn = $('#openRawBtn');
    if (openRawBtn) {
        openRawBtn.addEventListener('click', () => {
            $('#rawCastingForm').reset();
            openModal('rawCastingModal');
        });
    }

    /* ===== Auto Calculate Pieces (Add Modal) ===== */
    const rawTotalWeightInput = $('#rawTotalWeight');
    const rawWeightPerPieceInput = $('#rawWeightPerPiece');
    function calcRawPieces() {
      const kg = parseFloat(rawTotalWeightInput ? rawTotalWeightInput.value : 0);
      const g = parseFloat(rawWeightPerPieceInput ? rawWeightPerPieceInput.value : 0);
      const rawTotalPcsInput = $('#rawTotalPcs');
      if (rawTotalPcsInput) {
           rawTotalPcsInput.value = (!isNaN(kg) && !isNaN(g) && g > 0)
                ? Math.floor((kg * 1000) / g)
                : '';
      }
    }
    if (rawTotalWeightInput) rawTotalWeightInput.addEventListener('input', calcRawPieces);
    if (rawWeightPerPieceInput) rawWeightPerPieceInput.addEventListener('input', calcRawPieces);

    /* ===== Show Balance Function ===== */
    const showBalanceBtn = $('#showBalanceBtn');
    if (showBalanceBtn) showBalanceBtn.addEventListener('click', () => toggleBalance(true));
    
    const balanceCloseBtn = $('#balanceCloseBtn');
    if (balanceCloseBtn) balanceCloseBtn.addEventListener('click', () => toggleBalance(false));

    /* ===== Init ===== */
    document.addEventListener('DOMContentLoaded', () => {
        bindToggle('toggleCastingHeader', 'castingContent');
        bindToggle('toggleRawHeader', 'rawContent');
    });
</script>

</body>
</html>