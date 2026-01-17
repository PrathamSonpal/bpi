<?php
// FILE: get_sales_log.php

session_start();

// Only allow logged-in users
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([]);
    exit();
}

// Enable error reporting (disable in production if needed)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB Connection failed: " . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// ✅ Latest 500 Sales Logs
$sql = "SELECT 
            s.id,
            s.item_id,
            s.party_name,
            s.quantity,
            s.sale_type,
            s.timestamp,
            s.sold_by,

            u.full_name AS sold_by_username,
            i.name AS item_name,
            i.size AS item_size,
            i.material AS item_material
        FROM sales_log s
        LEFT JOIN users u ON s.sold_by = u.id
        LEFT JOIN items i ON s.item_id = i.id
        ORDER BY s.timestamp DESC
        LIMIT 500";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "SQL Query failed: " . $conn->error]);
    exit();
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
