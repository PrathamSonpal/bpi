<?php
// get_pending_orders.php
// Fetches ALL pending orders for the main dashboard.

header('Content-Type: application/json');
session_start();

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock"; // Your correct database

// This matches the JSON structure your index.php expects: {"orders": [...]}
$response = ["orders" => [], "error" => null];

// Optional: Auth Check
/*
if (!isset($_SESSION['loggedIn'])) {
    $response['error'] = "Unauthorized access."; 
    echo json_encode($response); 
    exit;
}
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable exceptions
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");

    // This is the status your 'place_order.php' script sets
    $status_to_find = 'pending'; 
    
    // Select all orders that are 'pending'
    $stmt = $conn->prepare("
        SELECT order_number, customer_name, created_at, cart
        FROM orders
        WHERE status = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->bind_param("s", $status_to_find);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Set the 'orders' key in the response
    $response["orders"] = $orders; 
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response["error"] = "Database Error: " . $e->getMessage();
    error_log("Error in get_pending_orders.php: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { $conn->close(); }
}

echo json_encode($response);
exit();
?>