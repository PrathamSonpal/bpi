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

$handle_id = $_GET['handle_id'] ?? null;
if (!$handle_id) {
    echo json_encode(["success" => false, "error" => "Missing handle_id"]);
    exit;
}

// 1️⃣ Get total turned
$stmt_turn = $conn->prepare("SELECT SUM(ready_pcs) AS total_turned FROM turning_log WHERE handle_id = ?");
$stmt_turn->bind_param("i", $handle_id);
$stmt_turn->execute();
$res_turn = $stmt_turn->get_result();
$total_turned = (int)($res_turn->fetch_assoc()['total_turned'] ?? 0);
$stmt_turn->close();

// 2️⃣ Get total buffed so far
$stmt_buff = $conn->prepare("SELECT SUM(buffed_pcs) AS total_buffed FROM buffing_log WHERE handle_id = ?");
$stmt_buff->bind_param("i", $handle_id);
$stmt_buff->execute();
$res_buff = $stmt_buff->get_result();
$total_buffed = (int)($res_buff->fetch_assoc()['total_buffed'] ?? 0);
$stmt_buff->close();

// 3️⃣ Calculate balance
$balance = $total_turned - $total_buffed;

// 4️⃣ Fetch handle name for display
$stmt_name = $conn->prepare("SELECT name FROM items WHERE id = ?");
$stmt_name->bind_param("i", $handle_id);
$stmt_name->execute();
$res_name = $stmt_name->get_result();
$handle_name = $res_name->fetch_assoc()['name'] ?? "Unknown Handle";
$stmt_name->close();

echo json_encode([
    "success" => true,
    "handle_id" => $handle_id,
    "handle_name" => $handle_name,
    "balance" => $balance,
    "total_turned" => $total_turned,
    "total_buffed" => $total_buffed
]);

$conn->close();
?>
