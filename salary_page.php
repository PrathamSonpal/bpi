<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// --- SECURITY ---
if (empty($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// --- DB CONNECTION ---
$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// --- ACTIONS ---
if (isset($_POST['action'])) {
    $msg = "";
    
    // 1. Update Wage
    if ($_POST['action'] === 'update_wage') {
        $uid  = (int)$_POST['worker_id'];
        $wage = (float)$_POST['hourly_wage'];
        $stmt = $conn->prepare("UPDATE users SET hourly_wage=? WHERE id=?");
        $stmt->bind_param("di", $wage, $uid);
        $stmt->execute();
        $stmt->close();
        $msg = "Wage Updated";
    }
    // 2. Add Withdrawal
    elseif ($_POST['action'] === 'add_withdrawal') {
        $uid  = (int)$_POST['worker_id'];
        $amt  = (float)$_POST['amount'];
        $date = date('Y-m-d'); 
        $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount, date) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $uid, $amt, $date);
        $stmt->execute();
        $stmt->close();
        $msg = "Withdrawal Added";
    }
    // 3. Edit Withdrawal (NEW)
    elseif ($_POST['action'] === 'edit_withdrawal') {
        $wd_id = (int)$_POST['withdrawal_id'];
        $amt   = (float)$_POST['amount'];
        $date  = $_POST['date_str']; // Date from input
        
        $stmt = $conn->prepare("UPDATE withdrawals SET amount=?, date=? WHERE id=?");
        $stmt->bind_param("dsi", $amt, $date, $wd_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Transaction Updated";
    }
    // 4. Delete Withdrawal
    elseif ($_POST['action'] === 'delete_withdrawal') {
        $wd_id = (int)$_POST['withdrawal_id'];
        $stmt = $conn->prepare("DELETE FROM withdrawals WHERE id=?");
        $stmt->bind_param("i", $wd_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Transaction Deleted";
    }
    
    // Redirect
    header("Location: salary_page.php?month=".$_POST['month']."&year=".$_POST['year']."&success=".$msg);
    exit();
}

// --- FILTERS ---
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// --- DATA PROCESSING ---

// 1. Fetch Withdrawals
$withdrawals_map = [];
$wd_sql = "SELECT id, user_id, amount, date FROM withdrawals WHERE MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC";
$wd_stmt = $conn->prepare($wd_sql);
$wd_stmt->bind_param("ii", $month, $year);
$wd_stmt->execute();
$wd_res = $wd_stmt->get_result();
while ($row = $wd_res->fetch_assoc()) {
    $withdrawals_map[$row['user_id']][] = $row;
}
$wd_stmt->close();

// 2. Fetch Users & Attendance
$sql = "
    SELECT 
        u.id, 
        u.full_name, 
        u.hourly_wage, 
        COALESCE(SUM(a.total_minutes), 0) AS total_minutes
    FROM users u
    LEFT JOIN attendance a 
        ON u.id = a.employee_id 
        AND MONTH(a.att_date) = ? 
        AND YEAR(a.att_date) = ?
    WHERE u.role != 'admin'  
    GROUP BY u.id, u.full_name, u.hourly_wage
    ORDER BY u.full_name ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

// 3. Pre-Calculate Totals
$workers_data = [];
$grand_net = 0;
$grand_wd = 0;

while ($u = $result->fetch_assoc()) {
    $mins = $u['total_minutes'];
    $display_hours = round($mins / 60, 2);
    $hourly_wage = (float)$u['hourly_wage'];
    $gross_pay = ($mins / 60) * $hourly_wage;

    $u_withdrawals = isset($withdrawals_map[$u['id']]) ? $withdrawals_map[$u['id']] : [];
    $total_wd = 0;
    foreach ($u_withdrawals as $wd) $total_wd += $wd['amount'];

    $net_pay = $gross_pay - $total_wd;
    $grand_net += $net_pay;
    $grand_wd += $total_wd;

    $u['display_hours'] = $display_hours;
    $u['gross_pay'] = $gross_pay;
    $u['total_wd'] = $total_wd;
    $u['net_pay'] = $net_pay;
    $u['withdrawals_list'] = $u_withdrawals;
    
    $workers_data[] = $u;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Salary Management | BPI</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    
    <link rel="stylesheet" href="style_casting.css?v=<?php echo filemtime('style_casting.css'); ?>">
    <link rel="stylesheet" href="style_salary.css?v=<?php echo filemtime('style_salary.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
<div class="dashboard">

    <header class="header-container">
        <div class="logo-section">
          <img src="Bhavesh Plastic Industries.png" alt="BPI Logo" class="logo-img">
          <div class="header-title">
            <h2>Bhavesh Plastic Industries</h2>
            <p>Salary Management</p>
          </div>
        </div>
        <div class="hamburger" onclick="toggleMenu()">☰</div>
        <nav class="nav-bar" id="navMenu">
            <a href="index.php">Dashboard</a>
            <a href="attendance_page.php">Attendance</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </header>

    <main class="main-content">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="form-msg success" style="background:#d4edda; color:#155724; padding:12px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #c3e6cb; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <div class="filter-card">
            <form method="get" class="filter-group">
                <select name="month" class="filter-select" onchange="this.form.submit()">
                    <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="filter-select" onchange="this.form.submit()">
                    <?php for($y=date('Y')-1;$y<=date('Y');$y++): ?>
                        <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>

            <form method="post" action="generate_payroll.php" onsubmit="return confirm('Generate payroll PDF?');">
                <input type="hidden" name="month" value="<?= $month ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <button type="submit" class="btn-save" style="display:flex; align-items:center; gap:8px; padding:10px 20px;">
                    <i class="fas fa-file-pdf"></i> Generate Slip
                </button>
            </form>
        </div>
        
        <div class="salary-summary">
            <div class="summary-box box-danger">
                <div class="summary-content">
                    <h4>Total Withdrawals</h4>
                    <div class="val">₹<?= number_format($grand_wd, 2) ?></div>
                </div>
                <div class="summary-icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div class="summary-box box-success">
                <div class="summary-content">
                    <h4>Total Net Payable</h4>
                    <div class="val">₹<?= number_format($grand_net, 2) ?></div>
                </div>
                <div class="summary-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Hourly Rate</th>
                            <th>Total Time</th>
                            <th>Gross Pay</th>
                            <th>Withdrawals</th>
                            <th>Net Payable</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($workers_data)): ?>
                        <?php foreach ($workers_data as $u): 
                             $history_json = htmlspecialchars(json_encode($u['withdrawals_list']), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td><span class="user-name"><?= htmlspecialchars($u['full_name']) ?></span></td>
                            
                            <td>
                                <?php if ($u['hourly_wage'] > 0): ?>
                                    <div class="wage-badge">
                                        ₹<?= number_format($u['hourly_wage'], 2) ?>
                                        <button class="btn-icon btn-edit" title="Change Wage" onclick="openWageModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>', <?= $u['hourly_wage'] ?>)">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button class="btn-set-wage" onclick="openWageModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>', '')">
                                        <i class="fas fa-plus"></i> Set Wage
                                    </button>
                                <?php endif; ?>
                            </td>

                            <td><span style="font-weight:500; color:#555;"><?= $u['display_hours'] ?> hrs</span></td>
                            <td>₹<?= number_format($u['gross_pay'], 2) ?></td>
                            
                            <td>
                                <span class="<?= $u['total_wd'] > 0 ? 'text-danger' : 'text-muted' ?>" style="font-weight:600;">
                                    ₹<?= number_format($u['total_wd'], 2) ?>
                                </span>
                                <?php if($u['total_wd'] > 0): ?>
                                    <button class="btn-icon btn-view" title="View Details" onclick="openHistoryModal('<?= htmlspecialchars($u['full_name']) ?>', <?= $history_json ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="<?= $u['net_pay'] < 0 ? 'text-danger' : 'text-success' ?>" style="font-weight:700; font-size:1.05rem;">
                                    ₹<?= number_format($u['net_pay'], 2) ?>
                                </span>
                            </td>

                            <td>
                                <button class="btn-withdraw" onclick="openWithdrawModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')">
                                    <i class="fas fa-minus-circle"></i> Withdraw
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px; color:#adb5bd;">No users found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<footer class="footer">
    <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
</footer>

<div id="wageModal" class="modal">
    <div class="modal-panel">
        <button class="close-x" onclick="closeModal('wageModal')">&times;</button>
        <h3>Set / Update Wage</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_wage">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" id="wage_worker_id" name="worker_id">
            
            <label>Worker Name</label>
            <input type="text" id="wage_worker_name" readonly style="background:#f9f9f9; color:#666;">
            
            <label>Hourly Wage (₹)</label>
            <input type="number" step="0.01" id="wage_input" name="hourly_wage" required placeholder="Enter amount (e.g. 50)">
            
            <div class="form-row">
                <button type="submit" class="btn-save">Save Wage</button>
                <button type="button" class="btn-cancel" onclick="closeModal('wageModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="withdrawModal" class="modal">
    <div class="modal-panel">
        <button class="close-x" onclick="closeModal('withdrawModal')">&times;</button>
        <h3>Add Withdrawal</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_withdrawal">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" id="wd_worker_id" name="worker_id">
            
            <label>Worker Name</label>
            <input type="text" id="wd_worker_name" readonly style="background:#f9f9f9; color:#666;">
            
            <label>Amount (₹)</label>
            <input type="number" step="1" name="amount" required placeholder="Enter amount">
            
            <div class="form-row">
                <button type="submit" class="btn-save">Confirm</button>
                <button type="button" class="btn-cancel" onclick="closeModal('withdrawModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editWithdrawModal" class="modal">
    <div class="modal-panel">
        <button class="close-x" onclick="closeModal('editWithdrawModal')">&times;</button>
        <h3>Edit Withdrawal</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_withdrawal">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" id="edit_wd_id" name="withdrawal_id">
            
            <label>Amount (₹)</label>
            <input type="number" step="1" id="edit_wd_amount" name="amount" required>
            
            <label>Date</label>
            <input type="date" id="edit_wd_date" name="date_str" required>
            
            <div class="form-row">
                <button type="submit" class="btn-save">Update</button>
                <button type="button" class="btn-cancel" onclick="closeModal('editWithdrawModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="historyModal" class="modal">
    <div class="modal-panel">
        <button class="close-x" onclick="closeModal('historyModal')">&times;</button>
        <h3 id="hist_title">Withdrawal History</h3>
        
        <ul id="history_list" class="history-list">
            </ul>
        
        <div class="form-row" style="justify-content:flex-end;">
            <button type="button" class="btn-cancel" onclick="closeModal('historyModal')">Close</button>
        </div>
    </div>
</div>

<script>
    const $ = s => document.querySelector(s);

    function toggleMenu() { $('#navMenu').classList.toggle('active'); }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    window.onclick = function(e) {
        if(e.target.classList.contains('modal')) e.target.style.display = 'none';
    }

    // --- Modal Openers ---
    function openWageModal(id, name, wage) {
        $('#wageModal').style.display = 'flex';
        $('#wage_worker_id').value = id;
        $('#wage_worker_name').value = name;
        $('#wage_input').value = wage; 
    }

    function openWithdrawModal(id, name) {
        $('#withdrawModal').style.display = 'flex';
        $('#wd_worker_id').value = id;
        $('#wd_worker_name').value = name;
    }

    // NEW: Open Edit Modal
    function openEditWithdrawModal(id, amount, dateStr) {
        // Close history first
        closeModal('historyModal');
        
        // Open Edit
        $('#editWithdrawModal').style.display = 'flex';
        $('#edit_wd_id').value = id;
        $('#edit_wd_amount').value = amount;
        $('#edit_wd_date').value = dateStr;
    }

    function openHistoryModal(name, historyData) {
        $('#historyModal').style.display = 'flex';
        $('#hist_title').innerText = name + "'s Withdrawals";
        
        const list = $('#history_list');
        list.innerHTML = ''; 

        if (!historyData || historyData.length === 0) {
            list.innerHTML = '<li class="history-item" style="justify-content:center; color:#999;">No withdrawals this month.</li>';
            return;
        }

        historyData.forEach(item => {
            const dateObj = new Date(item.date);
            const dateStr = dateObj.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
            // Keep ISO date for editing
            const rawDate = item.date.substring(0, 10); 

            const li = document.createElement('li');
            li.className = 'history-item';
            
            li.innerHTML = `
                <div class="history-info">
                    <span class="text-danger" style="font-weight:bold;">₹${item.amount}</span>
                    <span class="history-date">${dateStr}</span>
                </div>
                
                <div style="display:flex; gap:10px; align-items:center;">
                    <button type="button" class="btn-icon btn-edit" title="Edit" 
                        onclick="openEditWithdrawModal(${item.id}, ${item.amount}, '${rawDate}')">
                        <i class="fas fa-pencil-alt"></i>
                    </button>

                    <form method="POST" onsubmit="return confirm('Delete this withdrawal?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete_withdrawal">
                        <input type="hidden" name="month" value="<?= $month ?>">
                        <input type="hidden" name="year" value="<?= $year ?>">
                        <input type="hidden" name="withdrawal_id" value="${item.id}">
                        <button type="submit" class="btn-icon btn-delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            `;
            list.appendChild(li);
        });
    }
</script>

</body>
</html>