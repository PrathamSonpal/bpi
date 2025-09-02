<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB Connection failed: " . $conn->connect_error]);
    exit;
}

$item_id = $_POST['item_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$added_by = $_POST['added_by'] ?? null;
$timestamp = date("Y-m-d H:i:s");

if (empty($item_id) || empty($quantity) || empty($added_by)) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO stock_log (item_id, quantity, added_by, timestamp) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $item_id, $quantity, $added_by, $timestamp);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Stock recorded successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
