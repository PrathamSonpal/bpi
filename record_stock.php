<?php
// record_stock.php

// Disable display_errors so text warnings don't break the JSON response
ini_set('display_errors', 0);
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

// --- FIX 1: Look for 'user_id' (sent by JS) instead of 'added_by' ---
$item_id   = $_POST['item_id'] ?? null;
$quantity  = $_POST['quantity'] ?? null;
$added_by  = $_POST['user_id'] ?? null; // Changed from $_POST['added_by']
$timestamp = date("Y-m-d H:i:s");

if (empty($item_id) || empty($quantity) || empty($added_by)) {
    echo json_encode(["success" => false, "message" => "Missing required fields (Item, Qty, or User ID)."]);
    exit;
}

// --- FIX 2: Correct bind_param types ---
// item_id (int), quantity (int), added_by (int), timestamp (string) -> "iiis"
$stmt = $conn->prepare("INSERT INTO stock_log (item_id, quantity, added_by, timestamp) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $item_id, $quantity, $added_by, $timestamp);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Stock recorded successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>