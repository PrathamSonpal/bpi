<?php
// FILE: delete_casting_log.php
session_start();
header("Content-Type: application/json");

// Check permissions
if (!isset($_SESSION['loggedIn']) || ($_SESSION['role'] !== 'casting' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// DB Connection
$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit();
}

$id = (int)$_POST['id'];

// --- DELETION LOGIC (Removed Safety Check) ---
// We proceed directly to delete, ignoring if it causes negative balance.

$stmt = $conn->prepare("DELETE FROM casting_log WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete Failed: ' . $conn->error]);
}

$conn->close();
?>