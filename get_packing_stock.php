<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Connection failed"]);
    exit;
}
// $conn->query("SET time_zone = '+05:30'"); // Optional: Set timezone

$handle_id = $_GET['handle_id'] ?? 0;
if (empty($handle_id)) {
    echo json_encode(["opening_stock" => 0, "total_packed" => 0, "balance" => 0]);
    exit;
}

// 1. Get Opening Stock (Total Ready from Buffing Log)
$opening_stock = 0;
$stmt_buffed = $conn->prepare("SELECT SUM(buffed_pcs) AS total_buffed FROM buffing_log WHERE handle_id = ?");
$stmt_buffed->bind_param("i", $handle_id);
$stmt_buffed->execute();
$result_buffed = $stmt_buffed->get_result();
if ($result_buffed) {
    $opening_stock = (int)($result_buffed->fetch_assoc()['total_buffed'] ?? 0);
}
$stmt_buffed->close();

// 2. Get Total Packed (From this new table)
$total_packed = 0;
$stmt_packed = $conn->prepare("SELECT SUM(packed_pcs) AS total_packed FROM packing_log WHERE handle_id = ?");
$stmt_packed->bind_param("i", $handle_id);
$stmt_packed->execute();
$result_packed = $stmt_packed->get_result();
if ($result_packed) {
    $total_packed = (int)($result_packed->fetch_assoc()['total_packed'] ?? 0);
}
$stmt_packed->close();

// 3. Calculate balance (Available to pack)
$balance = $opening_stock - $total_packed;

// 4. Return the data
echo json_encode([
    "success" => true,
    "opening_stock" => $opening_stock,
    "total_packed" => $total_packed,
    "balance" => $balance
]);

$conn->close();
?>