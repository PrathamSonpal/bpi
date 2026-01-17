<?php
// generate_payroll.php

// 1. ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Security Check
if (empty($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['role'] !== 'admin') {
    die("Error: Access Denied. Please log in as admin.");
}

// 3. Database Connection
$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");
if ($conn->connect_error) die("DB Connection Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// 4. Get Post Data
$month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('m');
$year  = isset($_POST['year'])  ? (int)$_POST['year']  : (int)date('Y');
$monthName = date('F', mktime(0, 0, 0, $month, 1));

// 5. Fetch Withdrawals
$withdrawals_map = [];
$wd_sql = "SELECT user_id, amount FROM withdrawals WHERE MONTH(date) = ? AND YEAR(date) = ?";
$wd_stmt = $conn->prepare($wd_sql);
if (!$wd_stmt) die("SQL Error in Withdrawals: " . $conn->error);

$wd_stmt->bind_param("ii", $month, $year);
$wd_stmt->execute();
$wd_res = $wd_stmt->get_result();

while ($row = $wd_res->fetch_assoc()) {
    if(!isset($withdrawals_map[$row['user_id']])) $withdrawals_map[$row['user_id']] = 0;
    $withdrawals_map[$row['user_id']] += $row['amount'];
}

// 6. Fetch Workers
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
if (!$stmt) die("SQL Error in Users: " . $conn->error);

$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payroll - <?= $monthName ?> <?= $year ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* === RESET & FONTS === */
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    
    :root {
        --bg-color: #f3f4f6;
        --primary: #003366;
        --accent: #2563eb;
    }

    body { 
        background-color: var(--bg-color); 
        padding: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    /* === SCREEN ONLY: CONTROLS === */
    .controls {
        position: sticky; top: 20px; z-index: 100;
        background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
        padding: 12px 24px; border-radius: 50px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        display: flex; align-items: center; gap: 15px; border: 1px solid #ddd;
    }
    .btn {
        padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600;
        cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-primary { background: var(--accent); color: white; }
    .btn-secondary { background: #e2e8f0; color: #475569; }

    /* === THE SLIP === */
    .slip-container {
        background: white;
        width: 210mm; /* A4 Width */
        height: 135mm; /* Fits 2 per page (297mm / 2 = ~148mm minus margins) */
        padding: 30px 40px;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* Pushes footer to bottom */
        margin-bottom: 20px; /* Gap on screen */
    }

    /* Header */
    .company-header {
        display: flex; align-items: center;
        border-bottom: 2px solid var(--primary);
        padding-bottom: 15px; margin-bottom: 15px;
    }
    .logo { height: 50px; margin-right: 20px; }
    .company-info h1 { color: var(--primary); font-size: 22px; font-weight: 800; text-transform: uppercase; }
    .company-info p { color: #64748b; font-size: 13px; font-weight: 500; }

    /* Grid Layout */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 10px; }
    .box { border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; background: #f8fafc; }
    .box h3 { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 700; }
    
    .row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
    .row span { color: #64748b; }
    .row strong { color: #1e293b; font-weight: 600; }

    /* Total Row */
    .total-row {
        background: #eff6ff; padding: 12px 20px;
        border: 1px solid #bfdbfe; border-radius: 6px;
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 5px;
    }
    .total-row span:first-child { font-size: 13px; font-weight: 700; color: #1e40af; }
    .total-row span:last-child { font-size: 20px; font-weight: 800; color: #1e3a8a; }

    /* Footer & Signatures */
    .signatures { display: flex; justify-content: space-between; margin-top: 25px; }
    .sig-box {
        text-align: center; width: 35%; border-top: 1px solid #cbd5e1;
        padding-top: 5px; font-size: 12px; color: #94a3b8; text-transform: uppercase;
    }
    
    .dev-credit {
        text-align: center; font-size: 10px; color: #cbd5e1;
        margin-top: 10px; font-style: italic;
    }

    /* === PRINT CONFIGURATION (THE MAGIC) === */
    @media print {
        /* 1. Hide Browser Junk */
        @page { margin: 0; size: A4; }
        
        /* 2. Reset Body */
        body { 
            background: white; padding: 0; margin: 0; 
            display: block; 
        }
        
        /* 3. Hide Screen Elements */
        .controls { display: none !important; }

        /* 4. Slip Formatting for Print */
        .slip-container {
            width: 100%;
            height: 148mm; /* Exact half of A4 (297mm) approx */
            margin: 0; 
            box-shadow: none; 
            border: none;
            border-bottom: 1px dashed #ccc; /* Cut line */
            page-break-inside: avoid;
            padding: 15mm; /* Inner padding for the paper */
        }

        /* 5. Force Colors */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        /* 6. Page Break Helper */
        .page-break { page-break-after: always; clear: both; }
    }
</style>
</head>
<body>

<div class="controls">
    <div style="font-size:14px; color:#64748b; font-weight:600;">
        <?= $result->num_rows ?> Slips &bull; <?= $monthName ?> <?= $year ?>
    </div>
    <a href="salary_page.php" class="btn btn-secondary">Back</a>
    <button onclick="window.print()" class="btn btn-primary">Print All</button>
</div>

<?php 
$counter = 0; // Initialize counter for page breaks

if ($result->num_rows > 0) {
    while ($u = $result->fetch_assoc()) {
        $counter++; // Increment counter

        // Data Logic
        $mins = $u['total_minutes'];
        $hours = round($mins / 60, 2);
        $hourly_wage = (float)$u['hourly_wage'];
        $gross = ($mins / 60) * $hourly_wage;
        $total_wd = isset($withdrawals_map[$u['id']]) ? $withdrawals_map[$u['id']] : 0;
        $net = $gross - $total_wd;
?>

    <div class="slip-container">
        
        <div class="company-header">
            <img src="Bhavesh Plastic Industries.png" alt="Logo" class="logo" onerror="this.style.display='none'">
            <div class="company-info">
                <h1>Bhavesh Plastic Industries</h1>
                <p>Salary Slip &bull; <strong><?= $monthName ?> <?= $year ?></strong></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="box">
                <h3>Employee Details</h3>
                <div class="row"><span>Name</span> <strong><?= htmlspecialchars($u['full_name']) ?></strong></div>
                <div class="row"><span>ID</span> <strong>EMP-<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?></strong></div>
                <div class="row"><span>Rate</span> <strong>₹<?= number_format($hourly_wage, 2) ?>/hr</strong></div>
            </div>

            <div class="box">
                <h3>Earnings</h3>
                <div class="row"><span>Total Hours</span> <strong><?= $hours ?> hrs</strong></div>
                <div class="row"><span>Gross Pay</span> <strong>₹<?= number_format($gross, 2) ?></strong></div>
                <div class="row">
                    <span style="color:#ef4444;">Withdrawals</span> 
                    <strong style="color:#ef4444;">- ₹<?= number_format($total_wd, 2) ?></strong>
                </div>
            </div>
        </div>

        <div class="total-row">
            <span>NET PAYABLE</span>
            <span>₹<?= number_format($net, 2) ?></span>
        </div>

        <div class="signatures">
            <div class="sig-box">Employer</div>
            <div class="sig-box">Receiver</div>
        </div>

        <div class="dev-credit">Created & Developed by Pratham P Sonpal</div>

    </div>

<?php 
        // Logic: Insert Page Break after every 2nd slip
        if ($counter % 2 == 0) {
            echo '<div class="page-break"></div>';
        }
    } // End While
} else {
    echo '<div style="text-align:center; padding:50px; color:#666;">No records found.</div>';
}
?>

</body>
</html>