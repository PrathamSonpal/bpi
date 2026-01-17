<?php
// Add debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock"; // <-- FIXED: Ensured no trailing space

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB connection failed: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// Helper: format date
function fmt_date($dt) {
    if (!$dt || $dt === '0000-00-00' || $dt === '0000-00-00 00:00:00') return "";
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y', $ts) : $dt;
}

// --- If a single order requested (for modal view) ---
$orderParam = $_GET['order_id'] ?? $_GET['order_number'] ?? $_GET['order'] ?? null;

if ($orderParam) {
    $order = null;

    // Fetch main order details (Only need to check by order_number)
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->bind_param("s", $orderParam);
    
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if ($order) {
        $order['date'] = fmt_date($order['created_at']);
        $order['mobile_number'] = $order['mobile'] ?? '';
        $order['shipping_address'] = $order['address'] ?? ''; // This is the full address

        // Fetch Individual Order Items
        $order_items = [];
        $stmt_items = $conn->prepare("
            SELECT oi.id, oi.quantity_ordered, oi.status, i.name AS item_name
            FROM order_items oi
            JOIN items i ON oi.item_id = i.id
            WHERE oi.order_number = ?
            ORDER BY i.name
        ");
        
        $stmt_items->bind_param("s", $order['order_number']);
        $stmt_items->execute();
        $res_items = $stmt_items->get_result();
        while ($row_item = $res_items->fetch_assoc()) {
            // Format status (e.g., PENDING_PACKING -> "Pending Packing")
            $row_item['status_pretty'] = ucwords(strtolower(str_replace('_', ' ', $row_item['status'])));
            $order_items[] = $row_item;
        }
        $stmt_items->close();

        $order['items'] = $order_items;
        // Clean up unnecessary data
        unset($order['cart'], $order['created_at'], $order['customer_id']);

        echo json_encode($order);
    } else {
        http_response_code(404);
        echo json_encode(['status'=>'error','message'=>'Order not found']);
    }
    $conn->close();
    exit();
}

// --- Otherwise: return all orders with proper statuses ---
try {
    $orders = [];

    // 1. Fetch all main orders
    $sql_orders = "
        SELECT id, order_number, customer_name, created_at, status AS original_status 
        FROM orders 
        ORDER BY id DESC
    ";
    $result_orders = $conn->query($sql_orders);
    if ($result_orders === false) throw new Exception("Error fetching orders: ".$conn->error);

    $orders_map = [];
    while ($row = $result_orders->fetch_assoc()) {
        $row['date'] = fmt_date($row['created_at']);
        unset($row['created_at']);
        $row['calculated_status'] = 'Unknown';
        $orders[$row['order_number']] = $row;
        $orders_map[$row['order_number']] = [];
    }
    $result_orders->close();

    // 2. Fetch all order_items to derive calculated statuses
    $sql_items = "SELECT order_number, status FROM order_items";
    $result_items = $conn->query($sql_items);
    if ($result_items === false) throw new Exception("Error fetching order items: ".$conn->error);

    while ($item = $result_items->fetch_assoc()) {
        if (isset($orders_map[$item['order_number']])) {
            $orders_map[$item['order_number']][] = $item['status'];
        }
    }
    $result_items->close();

    // 3. Calculate the overall status for each order
    foreach ($orders as $order_number => &$order) {
        $item_statuses = $orders_map[$order_number];

        if (empty($item_statuses)) {
            $order['calculated_status'] = ucwords(strtolower($order['original_status'] ?: 'Pending'));
        } else {
            $total_items = count($item_statuses);
            $status_counts = array_count_values($item_statuses);

            // Priority from least complete -> most complete
            // These match the statuses from your department pages
            if (isset($status_counts['PENDING_CASTING'])) {
                $order['calculated_status'] = 'Pending Casting';
            } else if (isset($status_counts['PENDING_TURNING'])) {
                $order['calculated_status'] = 'In Turning';
            } else if (isset($status_counts['PENDING_BUFFING'])) {
                $order['calculated_status'] = 'In Buffing';
            } else if (isset($status_counts['PENDING_PACKING'])) {
                $order['calculated_status'] = 'Pending Packing';
            } else if (($status_counts['COMPLETED'] ?? 0) == $total_items) {
                // This is the status that triggers the "Dispatch" button
                $order['calculated_status'] = 'Completed'; 
            } else if (isset($status_counts['CANCELLED'])) {
                $order['calculated_status'] = 'Cancelled';
            } else {
                // If it's a mix of statuses (e.g., some packed, some not)
                $order['calculated_status'] = 'In Production'; 
            }
        }

        // --- FINAL STATUS OVERRIDE ---
        // Check the main order status. If it's Dispatched or Delivered, that status wins.
        $dbStatus = strtoupper(trim($order['original_status'] ?? ''));
        
        // FIXED: 'COMPLETED' and 'PACKED' are item statuses, not final order statuses.
        $finalStatuses = ['DISPATCHED', 'DELIVERED', 'CANCELLED'];

        if(in_array($dbStatus, $finalStatuses)) {
            // Use the final status from the `orders` table
            $order['status'] = ucwords(strtolower($dbStatus));
        } else {
            // Use the status we just calculated from the items
            $order['status'] = $order['calculated_status'];
        }

        unset($order['original_status'], $order['calculated_status']);
    }


    $conn->close();

    // 4. Return as array (not map)
    echo json_encode(array_values($orders));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Server error: '.$e->getMessage()]);
    if ($conn) $conn->close();
    exit();
}
?>