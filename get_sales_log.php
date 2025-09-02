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

// Join sales_log with items table to get item name, size, and material
$sql = "
    SELECT s.item_id, s.party_name, s.quantity, s.sold_by, s.timestamp,
           i.name AS item_name, i.size, i.material
    FROM sales_log s
    LEFT JOIN items i ON s.item_id = i.id
    ORDER BY s.timestamp DESC
";

$result = $conn->query($sql);

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
