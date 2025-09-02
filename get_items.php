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

$result = $conn->query("SELECT id, name, size, material FROM items ORDER BY id DESC");

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
}

$conn->close();
?>
