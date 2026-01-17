<?php
session_start();
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

/* ---------- DB CONNECTION ---------- */
$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

/* ---------- INPUTS ---------- */
$date = $_POST['date'];
$presence = $_POST['p'] ?? []; // Array of checked boxes
$deductions = $_POST['d'] ?? []; // Array of deduction minutes

/* ---------- FETCH LUNCH SETTINGS (To calc B3 duration) ---------- */
$set = $conn->query("SELECT lunch_minutes FROM attendance_settings WHERE id=1")->fetch_assoc();
$lunchMin = (int)($set['lunch_minutes'] ?? 60);

// Calculate Shift Durations in Minutes
$b1_max = 240; // Morning: Fixed 4 hours
$b2_max = 180; // Post-Lunch: Fixed 3 hours

// Calculate B3 (Evening) Max Duration dynamically
// Logic: 12:00 + Lunch + 3 hours (B2) = B3 Start. B3 End is always 20:00.
$lunchStartTimestamp = strtotime("$date 12:00:00");
$lunchEndTimestamp = $lunchStartTimestamp + ($lunchMin * 60);
$b2EndTimestamp = $lunchEndTimestamp + (180 * 60); // B2 ends 3 hours after lunch
$shopCloseTimestamp = strtotime("$date 20:00:00");

// B3 minutes = Difference between 8 PM and B3 Start
$b3_max = ($shopCloseTimestamp - $b2EndTimestamp) / 60;

/* ---------- PROCESSING LOOP ---------- */
// Get all active employees to ensure we handle everyone
$empQ = $conn->query("SELECT id FROM users WHERE status='active'");

while ($e = $empQ->fetch_assoc()) {
    $eid = $e['id'];
    
    // 1. Get Presence (1 if checked, 0 if not)
    $p_b1 = isset($presence[$eid]['b1']) ? 1 : 0;
    $p_b2 = isset($presence[$eid]['b2']) ? 1 : 0;
    $p_b3 = isset($presence[$eid]['b3']) ? 1 : 0;

    // 2. Get Deductions (ensure 0 if not present)
    $d_b1 = $p_b1 ? (int)($deductions[$eid]['b1'] ?? 0) : 0;
    $d_b2 = $p_b2 ? (int)($deductions[$eid]['b2'] ?? 0) : 0;
    $d_b3 = $p_b3 ? (int)($deductions[$eid]['b3'] ?? 0) : 0;

    // 3. Calculate Actual Minutes Worked
    $min_b1 = $p_b1 ? ($b1_max - $d_b1) : 0;
    $min_b2 = $p_b2 ? ($b2_max - $d_b2) : 0;
    $min_b3 = $p_b3 ? ($b3_max - $d_b3) : 0;

    $total_minutes = $min_b1 + $min_b2 + $min_b3;

    // 4. Update Database
    // Using INSERT ... ON DUPLICATE KEY UPDATE so it handles both new and existing records
    $stmt = $conn->prepare("
        INSERT INTO attendance 
        (employee_id, att_date, b1_present, b1_deduct, b2_present, b2_deduct, b3_present, b3_deduct, total_minutes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        b1_present=VALUES(b1_present), b1_deduct=VALUES(b1_deduct),
        b2_present=VALUES(b2_present), b2_deduct=VALUES(b2_deduct),
        b3_present=VALUES(b3_present), b3_deduct=VALUES(b3_deduct),
        total_minutes=VALUES(total_minutes)
    ");

    $stmt->bind_param("isiiiiiid", 
        $eid, $date, 
        $p_b1, $d_b1, 
        $p_b2, $d_b2, 
        $p_b3, $d_b3, 
        $total_minutes
    );
    $stmt->execute();
}

/* ---------- REDIRECT ---------- */
header("Location: attendance_page.php?date=$date&success=1");
exit();
?>