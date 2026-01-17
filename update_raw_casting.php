<?php
// FILE: update_raw_casting.php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['loggedIn']) || ($_SESSION['role'] !== 'casting' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit();
}

$id = (int)$_POST['id'];
$handle_id = (int)$_POST['handle_id'];
$total_weight = (float)$_POST['total_weight_kg'];
$weight_per_piece = (float)$_POST['weight_per_piece_g'];
$description = $_POST['description'];
$outsourced = isset($_POST['is_outsourced']) ? 1 : 0;

// Calculate new pieces
if ($weight_per_piece <= 0) {
    echo json_encode(['success' => false, 'message' => 'Weight per piece must be > 0']);
    exit();
}
$new_total_pcs = floor(($total_weight * 1000) / $weight_per_piece);

// --- SAFETY CHECK: Prevent Negative Stock ---
// We must ensure that (Total Made after edit) >= (Total Transferred)

// 1. Get Total Transferred for this handle
$sql_trans = "SELECT COALESCE(SUM(quantity), 0) as total_trans FROM raw_transfers WHERE handle_id = $handle_id";
$res_trans = $conn->query($sql_trans);
$total_transferred = (int)$res_trans->fetch_assoc()['total_trans'];

// 2. Get Total Made by ALL OTHER records (excluding the one we are editing)
$sql_other = "SELECT COALESCE(SUM(total_pcs), 0) as total_other FROM raw_casting_log WHERE handle_id = $handle_id AND id != $id";
$res_other = $conn->query($sql_other);
$total_other = (int)$res_other->fetch_assoc()['total_other'];

// 3. Check if New Total is enough
if (($total_other + $new_total_pcs) < $total_transferred) {
    $shortage = $total_transferred - ($total_other + $new_total_pcs);
    echo json_encode(['success' => false, 'message' => "Cannot reduce quantity! $shortage pieces have already been transferred to Stock."]);
    exit();
}

// --- UPDATE ---
$stmt = $conn->prepare("UPDATE raw_casting_log SET total_weight_kg=?, weight_per_piece_g=?, total_pcs=?, description=?, is_outsourced=? WHERE id=?");
$stmt->bind_param("ddisii", $total_weight, $weight_per_piece, $new_total_pcs, $description, $outsourced, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Record Updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update Failed: ' . $conn->error]);
}
$conn->close();
?>