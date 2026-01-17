<?php
// Fetches casting history, including related order number if applicable
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Adjust if needed
session_start();

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$response = ["success" => false, "data" => [], "error" => null];

// Optional: Auth check
/*
if (!isset($_SESSION['loggedIn']) || !$_SESSION['role']) {
     $response['error'] = "Unauthorized"; echo json_encode($response); exit;
}
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    if ($conn->connect_error) throw new Exception("Connection Failed: " . $conn->connect_error);
    // $conn->query("SET time_zone = '+05:30'");

    // Fetch casting log, join order_items to get order_number
    // Removed the old overall order_number column from SELECT, use joined one
    $sql = "SELECT
                cl.id,
                cl.casting_date,
                cl.metalin_weight,
                cl.total_pcs,
                cl.melo,
                cl.description,
                cl.order_item_id,
                cl.created_at,
                oi.order_number -- Get order number from the linked order_item
            FROM casting_log cl
            LEFT JOIN order_items oi ON cl.order_item_id = oi.id -- Join based on order_item_id
            ORDER BY cl.created_at DESC
            LIMIT 50";

    $result = $conn->query($sql);
    if ($result === false) throw new Exception("Query Failed: " . $conn->error);

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    $response["success"] = true;
    $response["data"] = $data;

    $result->close();
    $conn->close();

} catch (Exception $e) {
    $response["error"] = "Database Error: " . $e->getMessage();
    error_log("Error in get_casting.php: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { $conn->close(); }
}

echo json_encode($response);
exit();
?>