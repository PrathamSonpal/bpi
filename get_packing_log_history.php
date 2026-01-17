<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

// Optional: set timezone if needed
// $conn->query("SET time_zone = '+05:30'");

$sql = "
    SELECT 
        p.id,
        i.name AS handle_name,
        p.packed_pcs,
        p.packing_date,
        p.description,
        p.order_number
    FROM packing_log p
    JOIN items i ON p.handle_id = i.id
    ORDER BY p.packing_date DESC, p.id DESC
    LIMIT 50
";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
