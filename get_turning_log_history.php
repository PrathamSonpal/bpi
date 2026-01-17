<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}
// $conn->query("SET time_zone = '+05:30'"); // Optional

// ✅ SQL Query UPDATED (added t.order_number)
$sql = "SELECT t.id, i.name AS handle_name, t.ready_pcs, t.turning_date, t.description, t.order_number
        FROM turning_log t
        JOIN items i ON t.handle_id = i.id
        ORDER BY t.turning_date DESC, t.id DESC
        LIMIT 50";
$result = $conn->query($sql);

if (!$result) {
    error_log("Query failed: (" . $conn->errno . ") " . $conn->error);
    echo json_encode(["error" => "Query failed fetching history."]);
    exit;
}

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode($data);
$conn->close();
?>