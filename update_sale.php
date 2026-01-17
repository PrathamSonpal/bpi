<?php
session_start();
header("Content-Type: application/json");

// 1. Check Authorization (Make sure your session has 'role' set)
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Admin access required']); 
    exit();
}

// 2. Database Connection
$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection failed']);
    exit();
}

// 3. Handle JSON input (Fixes the empty $_POST issue)
$input = json_decode(file_get_contents("php://input"), true);

// Fallback to $_POST if JSON is not used
$id = isset($input['id']) ? (int)$input['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$party = isset($input['party_name']) ? $input['party_name'] : (isset($_POST['party_name']) ? $_POST['party_name'] : '');
$qty = isset($input['quantity']) ? (int)$input['quantity'] : (isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0);

// 4. Validation
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}
if ($qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Quantity']);
    exit();
}

// 5. Update Database
$stmt = $conn->prepare("UPDATE sales_log SET party_name = ?, quantity = ? WHERE id = ?");
$stmt->bind_param("sii", $party, $qty, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Sale updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or ID not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>