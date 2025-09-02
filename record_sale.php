<?php
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

$item_id   = $_POST['item_id']   ?? null;
$party_name = $_POST['party_name'] ?? null;
$quantity  = $_POST['quantity']  ?? null;
$sold_by   = $_POST['sold_by']   ?? null;
$timestamp = date("Y-m-d H:i:s");

if (empty($item_id) || empty($party_name) || empty($quantity) || empty($sold_by)) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

// Check user role
$stmt_user = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt_user->bind_param("i", $sold_by);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$stmt_user->close();

if (!$user_data || $user_data['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Permission denied. Only admins can record sales."]);
    exit;
}

// Insert into sales_log
$stmt = $conn->prepare("INSERT INTO sales_log (item_id, party_name, quantity, sold_by, timestamp) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isiss", $item_id, $party_name, $quantity, $sold_by, $timestamp);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Sale recorded successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
