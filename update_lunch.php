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

if ($conn->connect_error) die("DB Error");

/* ---------- UPDATE SETTINGS ---------- */
if (isset($_POST['lunch_minutes'])) {
    $mins = (int)$_POST['lunch_minutes'];
    
    // Ensure the table exists or update the specific row ID 1
    // We use ON DUPLICATE KEY in case the row ID 1 doesn't exist yet
    $stmt = $conn->prepare("
        INSERT INTO attendance_settings (id, lunch_minutes) 
        VALUES (1, ?) 
        ON DUPLICATE KEY UPDATE lunch_minutes = VALUES(lunch_minutes)
    ");
    
    $stmt->bind_param("i", $mins);
    $stmt->execute();
}

/* ---------- REDIRECT ---------- */
// Go back to attendance page
// We don't send a date, so it defaults to today, or you can use HTTP_REFERER
$back = $_SERVER['HTTP_REFERER'] ?? 'attendance_page.php';
header("Location: $back");
exit();
?>