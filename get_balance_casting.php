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

// 1. Get total metal-in from casting_log
$total_metalin = 0;
$result_metalin = $conn->query("SELECT SUM(metalin_weight) AS total_metalin FROM casting_log");
if ($result_metalin && $result_metalin->num_rows > 0) {
    $row = $result_metalin->fetch_assoc();
    // Use ?? 0 to handle case where table is empty and SUM returns NULL
    $total_metalin = (float)($row['total_metalin'] ?? 0);
}

// 2. Get total raw casting weight from raw_casting_log
$total_raw = 0;
$result_raw = $conn->query("SELECT SUM(total_weight_kg) AS total_raw FROM raw_casting_log");
if ($result_raw && $result_raw->num_rows > 0) {
    $row = $result_raw->fetch_assoc();
    $total_raw = (float)($row['total_raw'] ?? 0);
}

// 3. Calculate balance
$balance = $total_metalin - $total_raw;

// 4. Return the data
echo json_encode([
    "success" => true,
    "total_metalin" => $total_metalin,
    "total_raw_casting" => $total_raw,
    "balance_kg" => $balance
]);

$conn->close();
?>