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
// Set timezone if desired
// $conn->query("SET time_zone = '+05:30'");

$balance_data = [];

// 1. Get all handle IDs and names
$handles = [];
$result_handles = $conn->query("SELECT id, name FROM items ORDER BY name ASC");
if ($result_handles && $result_handles->num_rows > 0) {
    while ($row = $result_handles->fetch_assoc()) {
        $handles[$row['id']] = $row['name'];
    }
}

// Prepare statements
$stmt_turned = $conn->prepare("SELECT SUM(ready_pcs) AS total_turned FROM turning_log WHERE handle_id = ?");
$stmt_buffed = $conn->prepare("SELECT SUM(buffed_pcs) AS total_buffed FROM buffing_log WHERE handle_id = ?");

// 2. Loop through handles
foreach ($handles as $handle_id => $handle_name) {
    // Get Opening Stock (from Turning)
    $opening_stock = 0;
    $stmt_turned->bind_param("i", $handle_id);
    $stmt_turned->execute();
    $result_turned = $stmt_turned->get_result();
    if ($result_turned) {
        $opening_stock = (int)($result_turned->fetch_assoc()['total_turned'] ?? 0);
    }

    // Get Total Buffed
    $total_buffed = 0;
    $stmt_buffed->bind_param("i", $handle_id);
    $stmt_buffed->execute();
    $result_buffed = $stmt_buffed->get_result();
    if ($result_buffed) {
        $total_buffed = (int)($result_buffed->fetch_assoc()['total_buffed'] ?? 0);
    }

    $balance = $opening_stock - $total_buffed;

    // Store data only if there's activity
    if ($opening_stock > 0 || $total_buffed > 0) {
        $balance_data[] = [
            "handle_id" => $handle_id,
            "handle_name" => $handle_name,
            "opening_stock" => $opening_stock,
            "total_buffed" => $total_buffed,
            "balance" => $balance
        ];
    }
}

$stmt_turned->close();
$stmt_buffed->close();
$conn->close();

echo json_encode(["success" => true, "data" => $balance_data]);
?>