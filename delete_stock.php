<?php
session_start();
header('Content-Type: application/json');

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

$id = intval($_POST['id']);

// 🔒 CHECK LOG TYPE
$res = $conn->query("SELECT log_type FROM stock_log WHERE id = $id");
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

$logType = strtolower($res->fetch_assoc()['log_type']);

if (strpos($logType, 'casted') !== false || strpos($logType, 'raw') !== false) {
    echo json_encode([
        'success' => false,
        'message' => 'Casted stock entries cannot be deleted'
    ]);
    exit;
}

// ✅ SAFE DELETE
$conn->query("DELETE FROM stock_log WHERE id = $id");

echo json_encode(['success' => true, 'message' => 'Stock entry deleted']);
