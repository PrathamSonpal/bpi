<?php
// FILE: delete_raw_casting.php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['loggedIn']) || ($_SESSION['role'] !== 'casting' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");

$id = (int)$_POST['id'];

// Get handle_id before deleting
$res = $conn->query("SELECT handle_id FROM raw_casting_log WHERE id = $id");
if ($res->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit();
}
$handle_id = (int)$res->fetch_assoc()['handle_id'];

// --- SAFETY CHECK ---
// 1. Get Total Transferred
$res_trans = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total_trans FROM raw_transfers WHERE handle_id = $handle_id");
$total_transferred = (int)$res_trans->fetch_assoc()['total_trans'];

// 2. Get Total Made (Excluding this one)
$res_other = $conn->query("SELECT COALESCE(SUM(total_pcs), 0) as total_other FROM raw_casting_log WHERE handle_id = $handle_id AND id != $id");
$total_other = (int)$res_other->fetch_assoc()['total_other'];

if ($total_other < $total_transferred) {
    echo json_encode(['success' => false, 'message' => "Cannot delete! These items have already been transferred to Stock."]);
    exit();
}

// --- DELETE ---
if ($conn->query("DELETE FROM raw_casting_log WHERE id = $id")) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete Failed']);
}
$conn->close();
?>