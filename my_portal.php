<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['loggedIn'])) { header("Location: login.html"); exit(); }

// --- 1. DB CONNECTION ---
$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$myID = $_SESSION['user_id'];

// --- 2. FETCH USER INFO & WAGE ---
$uRes = $conn->query("SELECT full_name, role, hourly_wage FROM users WHERE id=$myID");

if ($uRes && $r = $uRes->fetch_assoc()) {
    $myName = $r['full_name'];
    $myRole = $r['role'];
    $myWage = isset($r['hourly_wage']) ? $r['hourly_wage'] : 0;
} else {
    $myName = "Worker"; $myRole = "worker"; $myWage = 0;
}

// --- 3. DEPT LINKS ---
$deptLink = ""; $deptLabel = "";
switch ($myRole) {
    case 'casting': $deptLink="casting_page.php"; $deptLabel="Casting Dept"; break;
    case 'turning': $deptLink="turning_page.php"; $deptLabel="Turning Dept"; break;
    case 'buff':    $deptLink="buff_page.php";    $deptLabel="Buffing Dept"; break;
    case 'packing': $deptLink="packing_page.php"; $deptLabel="Packing Dept"; break;
    case 'admin':   $deptLink="index.php";        $deptLabel="Dashboard";    break;
}

// --- 4. DATE HANDLING ---
$ym = $_GET['ym'] ?? date('Y-m');
$currDate = strtotime($ym . "-01");
$prevYM = date('Y-m', strtotime("-1 month", $currDate));
$nextYM = date('Y-m', strtotime("+1 month", $currDate));

// --- 5. FETCH ATTENDANCE STATS ---
$stats = $conn->query("SELECT SUM(total_minutes) as g_total, COUNT(id) as d_pres FROM attendance WHERE employee_id=$myID AND att_date LIKE '$ym%' AND total_minutes>0")->fetch_assoc();
$totalMins = $stats['g_total'] ?? 0;
$totalHrs  = round($totalMins / 60, 2);
$daysPres  = $stats['d_pres'] ?? 0;

// --- 6. CALCULATE SALARY ---
$grossPay = ($totalMins / 60) * $myWage;

// Fetch Withdrawals
$wdRes = $conn->query("SELECT SUM(amount) as total_wd FROM withdrawals WHERE user_id=$myID AND date LIKE '$ym%'");
$wdRow = $wdRes->fetch_assoc();
$totalWithdrawal = $wdRow['total_wd'] ?? 0;

// Net Pay
$netPay = $grossPay - $totalWithdrawal;

// --- 7. FETCH RECORDS ---
$masterList = [];
$chartLabels = [];
$chartData = [];

// Attendance
$qAtt = $conn->query("SELECT * FROM attendance WHERE employee_id=$myID AND att_date LIKE '$ym%' ORDER BY att_date ASC");
while($r = $qAtt->fetch_assoc()) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $r['att_date']);
    $dayNum = $dateObj->format('d');
    $hrs = round($r['total_minutes']/60, 1);

    $masterList[$r['att_date']] = ['type'=>'work', 'data'=>$r];
    $chartLabels[] = (int)$dayNum;
    $chartData[] = $hrs;
}

// Holidays
$qHol = $conn->query("SELECT h_date FROM holidays WHERE h_date LIKE '$ym%'");
while($h = $qHol->fetch_assoc()) {
    $masterList[$h['h_date']] = ['type'=>'holiday'];
}

// Sort Descending (Newest First)
krsort($masterList);

function getBadge($p, $d, $label) {
    if(!$p) return "<span class='badge a'>ABSENT</span>";
    if($d > 0) return "<span class='badge w'>-{$d}m</span>";
    return "<span class='badge p'>PRESENT</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portal | BPI Factory</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="style_attendance.css">
    
    <style>
        /* --- NEW LAYOUT CSS --- */
        .portal-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        /* Desktop: 2 Columns (Attendance 65%, Salary 35%) */
        @media (min-width: 992px) {
            .portal-grid {
                grid-template-columns: 2fr 1fr; /* Left gets more space */
                align-items: start;
            }
        }

        /* Salary Card Styling */
        .salary-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px; /* Stays visible when scrolling on desktop */
            border: 1px solid #e2e8f0;
        }
        
        .salary-header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .salary-header h3 { margin: 0; color: #1e293b; font-size: 1.1rem; }
        
        .salary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #64748b;
            font-size: 0.95rem;
        }
        .salary-row.total {
            border-top: 2px dashed #e2e8f0;
            padding-top: 15px;
            margin-top: 15px;
            color: #0f172a;
            font-weight: 700;
            font-size: 1.2rem;
            align-items: center;
        }
        .salary-row.highlight { color: #3b82f6; font-weight: 600; }
        .salary-row.deduct { color: #ef4444; }

        /* Existing Overrides */
        .stats-row { margin-bottom: 20px; }
        #chartContainer {
            background: white; padding: 20px; border-radius: 12px; 
            margin-top: 20px; /* Added spacing since it's at the bottom now */
            box-shadow: 0 2px 5px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
        }
    </style>
</head>
<body style="background:#f4f6f9;">

    <header class="main-header">
        <div class="brand-area">
            <img src="Bhavesh Plastic Industries.png" alt="Logo">
            <div class="brand-text">
                <h2>Bhavesh Plastic Industries</h2>
                <p>Worker Portal</p>
            </div>
        </div>
        <nav class="nav-links">
            <?php if($deptLink): ?>
                <a href="<?php echo $deptLink; ?>" class="dept-btn">
                    <ion-icon name="briefcase"></ion-icon> <?php echo $deptLabel; ?>
                </a>
            <?php endif; ?>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <div class="worker-container">
        
        <div style="margin-bottom: 10px;">
            <h2 style="color:var(--primary); margin:0;">Hello, <?php echo htmlspecialchars($myName); ?> 👋</h2>
            <p style="color:#64748b; margin:5px 0 0 0; font-size:0.9rem;">Here is your summary.</p>
        </div>

        <div class="portal-grid">

            <div class="col-attendance">
                
                <div class="month-card">
                    <a href="?ym=<?php echo $prevYM; ?>" class="mc-btn"><ion-icon name="chevron-back"></ion-icon></a>
                    <div class="mc-title">
                        <ion-icon name="calendar" style="vertical-align:middle; color:var(--primary); margin-right:5px;"></ion-icon> 
                        <?php echo date("F Y", $currDate); ?>
                    </div>
                    <a href="?ym=<?php echo $nextYM; ?>" class="mc-btn"><ion-icon name="chevron-forward"></ion-icon></a>
                </div>

                <div class="stats-row">
                    <div class="stat-panel green">
                        <div class="sp-num"><?php echo $totalHrs; ?></div>
                        <div class="sp-lbl">Total Hours</div>
                    </div>
                    <div class="stat-panel green">
                        <div class="sp-num"><?php echo $daysPres; ?></div>
                        <div class="sp-lbl">Days Present</div>
                    </div>
                </div>

                <?php if(empty($masterList)): ?>
                    <div style="text-align:center; padding:50px; color:#94a3b8; background:white; border-radius:12px;">
                        <ion-icon name="file-tray-outline" style="font-size:3rem; margin-bottom:10px;"></ion-icon>
                        <p>No records found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($masterList as $date => $item): 
                        $dt = strtotime($date);
                        if($item['type'] === 'holiday'): 
                    ?>
                        <div class="day-row holiday">
                            <div class="dr-date">
                                <div class="dr-d"><?php echo date("d", $dt); ?></div>
                                <div class="dr-th"><?php echo date("D", $dt); ?></div>
                            </div>
                            <div class="dr-info" style="align-items:flex-start; justify-content:center;">
                                <div class="holiday-badge"><ion-icon name="sparkles"></ion-icon> HOLIDAY</div>
                                <div style="font-size:0.85rem; color:#b45309; margin-top:5px; font-weight:500;">Factory Closed</div>
                            </div>
                            <div class="dr-total"><div class="dr-hrs" style="color:#b45309;">--</div></div>
                        </div>
                    <?php else: $r = $item['data']; ?>
                        <div class="day-row">
                            <div class="dr-date">
                                <div class="dr-d"><?php echo date("d", $dt); ?></div>
                                <div class="dr-th"><?php echo date("D", $dt); ?></div>
                            </div>
                            <div class="dr-info">
                                <div class="status-badges">
                                    <?php echo getBadge($r['b1_present'], $r['b1_deduct'], 'M'); ?>
                                    <?php echo getBadge($r['b2_present'], $r['b2_deduct'], 'A'); ?>
                                    <?php echo getBadge($r['b3_present'], $r['b3_deduct'], 'E'); ?>
                                </div>
                                <?php if(($r['early_mins']??0) > 0 || ($r['late_mins']??0) > 0): ?>
                                <div class="dr-extra">
                                    <?php if(($r['early_mins']??0) > 0): ?><span class="extra-pill early">Early +<?php echo $r['early_mins']; ?>m</span><?php endif; ?>
                                    <?php if(($r['late_mins']??0) > 0): ?><span class="extra-pill late">Late +<?php echo $r['late_mins']; ?>m</span><?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="dr-total">
                                <div class="dr-hrs"><?php echo round($r['total_minutes']/60, 1); ?></div>
                                <div class="dr-lbl">HRS</div>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                <?php endif; ?>

                <div id="chartContainer">
                    <h4 style="margin:0 0 15px 0; color:#475569; font-size:0.95rem; font-weight:600;">Work Hours Trend</h4>
                    <div style="position: relative; height:200px; width:100%">
                        <canvas id="workChart"></canvas>
                    </div>
                </div>

            </div>

            <div class="col-salary">
                <div class="salary-card">
                    <div class="salary-header">
                        <h3><ion-icon name="wallet-outline" style="vertical-align:bottom; color:#3b82f6;"></ion-icon> Salary Calculation</h3>
                    </div>

                    <div class="salary-row">
                        <span>Hourly Wage</span>
                        <span>₹<?php echo number_format($myWage, 2); ?>/hr</span>
                    </div>

                    <div class="salary-row highlight">
                        <span>Gross Earning</span>
                        <span>₹<?php echo number_format($grossPay, 2); ?></span>
                    </div>

                    <div class="salary-row deduct">
                        <span>Withdrawals</span>
                        <span>- ₹<?php echo number_format($totalWithdrawal, 2); ?></span>
                    </div>

                    <div class="salary-row total">
                        <span>Net Payable</span>
                        <span style="color:#16a34a;">₹<?php echo number_format($netPay); ?></span>
                    </div>
                    
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:15px; text-align:center;">
                        * Calculated based on total minutes worked.
                    </div>
                </div>
            </div>

        </div> </div>

    <footer class="main-footer">
        <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
    </footer>

    <script>
        const ctx = document.getElementById('workChart').getContext('2d');
        const labels = <?php echo json_encode($chartLabels); ?>;
        const dataPoints = <?php echo json_encode($chartData); ?>;

        if(labels.length > 0) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Hours Worked',
                        data: dataPoints,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, suggestedMax: 12 },
                        x: { grid: { display: false } }
                    }
                }
            });
        } else {
            document.getElementById('chartContainer').style.display = 'none';
        }
    </script>
</body>
</html>