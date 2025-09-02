<?php
// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["error" => "DB Connection failed: " . $conn->connect_error]);
    exit;
}

// Corrected the column name to 'timestamp'
$result = $conn->query("SELECT item_id, quantity, timestamp FROM stock_log ORDER BY timestamp DESC");
$log = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $log[] = $row;
    }
    echo json_encode($log);
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
}

$conn->close();
?>
