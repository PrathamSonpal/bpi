<?php
// FILE: update_casting_log.php
session_start();
header("Content-Type: application/json");

// 1. Enable Error Reporting (Crucial for finding the bug)
ini_set('display_errors', 0); // Hide HTML errors from breaking JSON
error_reporting(E_ALL);

// 2. Check Permissions
if (!isset($_SESSION['loggedIn']) || ($_SESSION['role'] !== 'casting' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 3. Connect to Database
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit();
}

// 4. Get POST Data
$id = (int)($_POST['id'] ?? 0);
$metalin = (float)($_POST['metalin_weight'] ?? 0);
$pcs = (int)($_POST['total_pcs'] ?? 0);
$melo = $_POST['melo'] ?? '';
$desc = $_POST['description'] ?? '';
$date = $_POST['casting_date'] ?? '';
$order = $_POST['order_number'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

// --- SAFETY CHECK REMOVED ---
// We now allow updates regardless of negative metal balance.

// 5. UPDATE
$stmt = $conn->prepare("UPDATE casting_log SET metalin_weight=?, total_pcs=?, melo=?, description=?, casting_date=?, order_number=? WHERE id=?");
$stmt->bind_param("dissisi", $metalin, $pcs, $melo, $desc, $date, $order, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Updated Successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update Failed: ' . $stmt->error]);
}

$conn->close();
?>