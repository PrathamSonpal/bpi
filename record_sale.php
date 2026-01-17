<?php
// FILE: record_sale.php
// Updated: Now explicitly saves the Timestamp using NOW()

session_start();
header("Content-Type: application/json");

// Error Reporting (for debugging)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database Connection Failed']);
    exit();
}

// Get POST data
$item_id = $_POST['item_id'] ?? 0;
$qty = (int)($_POST['quantity'] ?? 0);
$sale_type = $_POST['sale_type'] ?? 'finished';
$party = $_POST['party_name'] ?? '';
$user_id = $_POST['user_id'] ?? 0;

if (!$item_id || !$qty || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// 1. CHECK STOCK BALANCE
// We calculate available stock based on the log history
$sql_check = "SELECT 
    (COALESCE((SELECT SUM(quantity) FROM stock_log WHERE item_id = ?), 0) - 
     COALESCE((SELECT SUM(quantity) FROM sales_log WHERE item_id = ?), 0)) 
    as current_stock";

$stmtCheck = $conn->prepare($sql_check);
$stmtCheck->bind_param("ii", $item_id, $item_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
$row = $resultCheck->fetch_assoc();
$current_stock = (int)$row['current_stock'];

if ($qty > $current_stock) {
    echo json_encode(['success' => false, 'message' => "Insufficient Stock! Available: $current_stock"]);
    exit();
}

// 2. INSERT SALE WITH TIMESTAMP
// Added 'timestamp' column and 'NOW()' function
$stmt = $conn->prepare("INSERT INTO sales_log (item_id, quantity, sold_by, party_name, sale_type, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iiiss", $item_id, $qty, $user_id, $party, $sale_type);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => "SQL Error: " . $stmt->error]);
}

$conn->close();
?>