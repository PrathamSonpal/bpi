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
// Set timezone if desired
// $conn->query("SET time_zone = '+05:30'");

$sql = "SELECT b.id, i.name AS handle_name, b.buffed_pcs, b.buffing_date, b.description
        FROM buffing_log b
        JOIN items i ON b.handle_id = i.id
        ORDER BY b.buffing_date DESC, b.id DESC
        LIMIT 50";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
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