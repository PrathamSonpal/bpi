<?php
// update_order_status.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$response = ["success" => false, "message" => "An unknown error occurred."];

$data = json_decode(file_get_contents('php://input'), true);
$order_number = $data['order_number'] ?? null;
$new_status_js = strtoupper(trim($data['new_status'] ?? ''));

if (!$order_number || !$new_status_js) {
    echo json_encode(["success" => false, "message" => "Missing order_number or new_status"]);
    exit;
}

$valid_statuses = ['DISPATCHED', 'DELIVERED', 'CANCELLED'];
if (!in_array($new_status_js, $valid_statuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status update."]);
    exit;
}

$status_for_orders_table = ucfirst(strtolower($new_status_js)); // e.g. "Dispatched"
$status_for_items_table = $new_status_js;
$logged_in_user_id = $_SESSION['user_id'] ?? 0;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    $conn->begin_transaction();

    // Prepare statements
    $stmt_update_order = $conn->prepare(
        "UPDATE orders SET status = ?, current_stage = ?, updated_at = NOW() WHERE order_number = ?"
    );
    $stmt_update_items = $conn->prepare(
        "UPDATE order_items SET status = ? WHERE order_number = ?"
    );

    if ($new_status_js === 'DISPATCHED') {
        // Fetch items for dispatch
        $stmt_get_items = $conn->prepare("
            SELECT oi.item_id, oi.quantity_ordered, i.name AS item_name
            FROM order_items oi
            JOIN items i ON oi.item_id = i.id
            WHERE oi.order_number = ?
        ");
        $stmt_get_items->bind_param("s", $order_number);
        $stmt_get_items->execute();
        $items = $stmt_get_items->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_get_items->close();

        if (!$items) throw new Exception("No items found for this order.");

        // Insert into sales log
        $stmt_log = $conn->prepare("
            INSERT INTO sales_log (item_id, item_name, quantity, order_number, sold_by, timestamp)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        foreach ($items as $item) {
            $stmt_log->bind_param("isisi", $item['item_id'], $item['item_name'], $item['quantity_ordered'], $order_number, $logged_in_user_id);
            $stmt_log->execute();
        }
        $stmt_log->close();

        $response['message'] = "Order dispatched and logged successfully.";

    } elseif ($new_status_js === 'DELIVERED') {
        $response['message'] = "Order marked as delivered.";

    } elseif ($new_status_js === 'CANCELLED') {
        $response['message'] = "Order cancelled successfully.";

        // Optional: rollback inventory if needed
        // (add code here if you want to restock)
    }

    // Update both tables
    $stmt_update_order->bind_param("sss", $status_for_orders_table, $status_for_orders_table, $order_number);
    $stmt_update_order->execute();

    $stmt_update_items->bind_param("ss", $status_for_items_table, $order_number);
    $stmt_update_items->execute();

    $stmt_update_order->close();
    $stmt_update_items->close();

    $conn->commit();
    $response['success'] = true;

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response['message'] = "Database error: " . $e->getMessage();
} finally {
    if (isset($conn)) $conn->close();
}

echo json_encode($response);
exit;
?>
