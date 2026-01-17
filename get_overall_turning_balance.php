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

$balance_data = [];

// 1. Get all handle IDs and names
$handles = [];
$result_handles = $conn->query("SELECT id, name FROM items ORDER BY name ASC");
if ($result_handles && $result_handles->num_rows > 0) {
    while ($row = $result_handles->fetch_assoc()) {
        $handles[$row['id']] = $row['name'];
    }
}

// Prepare statements for efficiency
$stmt_raw = $conn->prepare("SELECT SUM(total_pcs) AS total_raw FROM raw_casting_log WHERE handle_id = ?");
$stmt_turned = $conn->prepare("SELECT SUM(ready_pcs) AS total_turned FROM turning_log WHERE handle_id = ?");

// 2. Loop through each handle and get balances
foreach ($handles as $handle_id => $handle_name) {
    // Get Opening Stock
    $opening_stock = 0;
    $stmt_raw->bind_param("i", $handle_id);
    $stmt_raw->execute();
    $result_raw = $stmt_raw->get_result();
    if ($result_raw) {
        $opening_stock = (int)($result_raw->fetch_assoc()['total_raw'] ?? 0);
    }

    // Get Total Turned
    $total_turned = 0;
    $stmt_turned->bind_param("i", $handle_id);
    $stmt_turned->execute();
    $result_turned = $stmt_turned->get_result();
    if ($result_turned) {
        $total_turned = (int)($result_turned->fetch_assoc()['total_turned'] ?? 0);
    }

    // Calculate balance
    $balance = $opening_stock - $total_turned;

    // Store data only if there's stock or turning activity
    if ($opening_stock > 0 || $total_turned > 0) {
        $balance_data[] = [
            "handle_id" => $handle_id,
            "handle_name" => $handle_name,
            "opening_stock" => $opening_stock,
            "total_turned" => $total_turned,
            "balance" => $balance
        ];
    }
}

$stmt_raw->close();
$stmt_turned->close();
$conn->close();

// Return the aggregated data
echo json_encode(["success" => true, "data" => $balance_data]);
?>