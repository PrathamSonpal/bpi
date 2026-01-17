<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Connection failed"]);
    exit;
}

$handle_id = intval($_GET['handle_id'] ?? 0);
if (!$handle_id) {
    echo json_encode(["success" => true, "opening_stock" => 0, "total_turned" => 0, "balance" => 0]);
    exit;
}

// Get raw stock (from raw casting)
$stmt1 = $conn->prepare("SELECT SUM(total_pcs) AS total_raw FROM raw_casting_log WHERE handle_id = ?");
$stmt1->bind_param("i", $handle_id);
$stmt1->execute();
$total_raw = (int)($stmt1->get_result()->fetch_assoc()['total_raw'] ?? 0);
$stmt1->close();

// Get already turned pcs
$stmt2 = $conn->prepare("SELECT SUM(ready_pcs) AS total_turned FROM turning_log WHERE handle_id = ?");
$stmt2->bind_param("i", $handle_id);
$stmt2->execute();
$total_turned = (int)($stmt2->get_result()->fetch_assoc()['total_turned'] ?? 0);
$stmt2->close();

$balance = $total_raw - $total_turned;

echo json_encode([
    "success" => true,
    "opening_stock" => $total_raw,
    "total_turned" => $total_turned,
    "balance" => $balance
]);
$conn->close();
?>
