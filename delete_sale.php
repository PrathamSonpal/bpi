<?php
// FILE: delete_sale.php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json");

// Check Auth
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit();
}

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Fail']); exit();
}

try {
    $raw_input = $_POST['id'] ?? 'MISSING';
    $id = (int)$raw_input;

    // --- DEBUGGING CHECK ---
    if ($id <= 0) {
        // This message will tell us EXACTLY what is wrong
        throw new Exception("Invalid ID. PHP received: " . var_export($raw_input, true));
    }

    // 1. Fetch Sale Details
    $stmt = $conn->prepare("SELECT item_id, quantity, sale_type FROM sales_log WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Sale record not found in DB (ID: $id)");
    }

    $sale = $result->fetch_assoc();
    $itemId = $sale['item_id'];
    $qty = $sale['quantity'];
    $type = $sale['sale_type']; 
    $stmt->close();

    // 2. Restore Stock
    $colToUpdate = ($type === 'raw') ? 'raw_stock' : 'stock'; 
    $updateStmt = $conn->prepare("UPDATE items SET $colToUpdate = $colToUpdate + ? WHERE id = ?");
    $updateStmt->bind_param("ii", $qty, $itemId);
    if (!$updateStmt->execute()) throw new Exception("Stock restore failed: " . $conn->error);
    $updateStmt->close();

    // 3. Delete Log
    $delStmt = $conn->prepare("DELETE FROM sales_log WHERE id = ?");
    $delStmt->bind_param("i", $id);
    if ($delStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Deleted & Stock Restored']);
    } else {
        throw new Exception("Delete failed");
    }
    $delStmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>