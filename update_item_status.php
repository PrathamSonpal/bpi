<?php
header('Content-Type: application/json');

// --- DB Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(['status'=>'error','message'=>'DB Connection failed']));
}
$conn->set_charset("utf8mb4");

// --- Get POST parameters ---
$order_number = $_POST['order_number'] ?? null;
$item_id = $_POST['item_id'] ?? null;
$new_status = $_POST['new_status'] ?? null; // Example: 'PENDING_TURNING', 'PACKED'

if (!$order_number || !$item_id || !$new_status) {
    die(json_encode(['status'=>'error','message'=>'Missing parameters']));
}

// --- 1️⃣ Update item status ---
$stmt = $conn->prepare("UPDATE order_items SET status=? WHERE order_number=? AND item_id=?");
$stmt->bind_param("ssi", $new_status, $order_number, $item_id);
$stmt->execute();
$stmt->close();

// --- 2️⃣ Recalculate overall order status ---
$res = $conn->query("
    SELECT status, COUNT(*) AS cnt
    FROM order_items
    WHERE order_number='$order_number'
    GROUP BY status
");

$status_counts = [];
while ($row = $res->fetch_assoc()) {
    $status_counts[strtoupper(trim($row['status']))] = (int)$row['cnt'];
}
$total_items = array_sum($status_counts);

// Priority from least complete → most complete
$final_status = 'Pending';
if (isset($status_counts['PENDING_CASTING'])) {
    $final_status = 'Pending Casting';
} else if (isset($status_counts['PENDING_TURNING'])) {
    $final_status = 'In Turning';
} else if (isset($status_counts['PENDING_BUFFING'])) {
    $final_status = 'In Buffing';
} else if (isset($status_counts['PENDING_PACKING'])) {
    $final_status = 'Pending Packing';
} else if (($status_counts['PACKED'] ?? 0) == $total_items) {
    $final_status = 'Packed';
} else if (($status_counts['COMPLETED'] ?? 0) == $total_items) {
    $final_status = 'Completed';
} else if (isset($status_counts['CANCELLED'])) {
    $final_status = 'Cancelled';
} else {
    $final_status = 'Partial';
}

// Update orders table
$stmt2 = $conn->prepare("UPDATE orders SET status=? WHERE order_number=?");
$stmt2->bind_param("ss", $final_status, $order_number);
$stmt2->execute();
$stmt2->close();

$conn->close();

// ✅ Return success
echo json_encode([
    'status' => 'success',
    'item_status' => $new_status,
    'order_status' => $final_status
]);
?>
