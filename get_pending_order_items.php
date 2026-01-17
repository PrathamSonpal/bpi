<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
session_start(); // Keep session start if needed for auth later

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$response = ["success" => false, "items" => [], "error" => null];

// Optional: Add authentication check here if needed later
// if (!isset($_SESSION['loggedIn']) || !in_array($_SESSION['role'], ['casting', 'admin'])) { ... }

// Get the requested status (default to PENDING_CASTING if not provided)
$status = $_GET['status'] ?? 'PENDING_CASTING';

// Validate status
$allowed_statuses = ['PENDING_STOCK', 'PENDING_CASTING', 'PENDING_TURNING', 'PENDING_BUFFING', 'PENDING_PACKING', 'COMPLETED', 'CANCELLED'];
if (!in_array($status, $allowed_statuses)) {
     $response['error'] = "Invalid status requested";
     echo json_encode($response);
     exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");

    // Prepare SQL to get order items with the specified status
    $stmt = $conn->prepare("
        SELECT oi.id, oi.order_number, oi.quantity_ordered, i.name as item_name
        FROM order_items oi
        JOIN items i ON oi.item_id = i.id
        WHERE oi.status = ?
        ORDER BY oi.order_number ASC, i.name ASC
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->close();
    } else {
        throw new Exception("Query failed: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();

    $response["success"] = true;
    $response["items"] = $items;

} catch (Exception $e) {
    $response["error"] = "Database Error: " . $e->getMessage();
    error_log("Error in get_pending_order_items.php: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { $conn->close(); }
}

echo json_encode($response);
exit();
?>