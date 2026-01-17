<?php
session_start();
header('Content-Type: application/json');

// 1. Authorization Check
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

// 2. Get ID and Quantity from POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$newQty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($id <= 0 || $newQty < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID or Quantity']);
    exit;
}

// 3. 🔒 CHECK LOG TYPE (Security Check)
$res = $conn->query("SELECT log_type FROM stock_log WHERE id = $id");
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

$logType = strtolower($res->fetch_assoc()['log_type']);

// Prevent editing of automated production entries
if (strpos($logType, 'casted') !== false || strpos($logType, 'raw') !== false) {
    echo json_encode([
        'success' => false,
        'message' => 'Casted/Raw stock entries cannot be edited'
    ]);
    exit;
}

/* ✅ SAFE TO UPDATE BELOW */

// 4. Perform the Update
$stmt = $conn->prepare("UPDATE stock_log SET quantity = ? WHERE id = ?");
$stmt->bind_param("ii", $newQty, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes made']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>