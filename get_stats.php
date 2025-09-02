<?php
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["error" => "DB Connection failed"]);
    exit;
}

$stats = [];

// Count total items
$result = $conn->query("SELECT COUNT(*) AS cnt FROM items");
$stats['total_items'] = $result->fetch_assoc()['cnt'] ?? 0;

// Count total sales
$result = $conn->query("SELECT COUNT(*) AS cnt FROM sales_log");
$stats['total_sales'] = $result->fetch_assoc()['cnt'] ?? 0;

// Count unique parties
$result = $conn->query("SELECT COUNT(DISTINCT party_name) AS cnt FROM sales_log");
$stats['total_parties'] = $result->fetch_assoc()['cnt'] ?? 0;

echo json_encode($stats);
$conn->close();
?>
