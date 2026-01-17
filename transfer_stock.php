<?php
// FILE: transfer_stock.php
// Moves stock from Raw Casting (Casting Page) -> Finished Stock (Admin Page)

session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['loggedIn'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit();
}

$handle_id = $_POST['handle_id'] ?? 0;
$qty = (int)($_POST['quantity'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$handle_id || $qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Item or Quantity']);
    exit();
}

// 1. CHECK RAW BALANCE (Prevent Loophole #1)
// Available = (Total Raw Made) - (Total Already Transferred)
$sql_check = "SELECT 
    (COALESCE((SELECT SUM(total_pcs) FROM raw_casting_log WHERE handle_id = $handle_id), 0) - 
     COALESCE((SELECT SUM(quantity) FROM raw_transfers WHERE handle_id = $handle_id), 0)) 
    as available_raw";

$result = $conn->query($sql_check);
$row = $result->fetch_assoc();
$current_raw_balance = (int)$row['available_raw'];

if ($qty > $current_raw_balance) {
    echo json_encode(['success' => false, 'message' => "Not enough raw stock! Available: $current_raw_balance"]);
    exit();
}

// 2. PERFORM TRANSFER
$conn->begin_transaction();

try {
    // A. Record the Transfer Out (Deduct from Casting Page view)
    $stmt1 = $conn->prepare("INSERT INTO raw_transfers (handle_id, quantity, user_id) VALUES (?, ?, ?)");
    $stmt1->bind_param("iii", $handle_id, $qty, $user_id);
    $stmt1->execute();

    // B. Record the Stock In (Add to Admin Page view with Label)
    // We assume 'added_by' is the user_id column in stock_log
    $log_type = "Raw Casted"; 
    $stmt2 = $conn->prepare("INSERT INTO stock_log (item_id, quantity, added_by, log_type) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("iiis", $handle_id, $qty, $user_id, $log_type);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Transfer Successful!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transfer Failed: ' . $e->getMessage()]);
}

$conn->close();
?>