<?php
// Fetches raw casting history, including order number
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

    // Fetch raw casting log, join items for name, include order_number
    $sql = "SELECT
                r.id,
                i.name AS handle_name,
                r.total_weight_kg,
                r.weight_per_piece_g,
                r.total_pcs,
                r.description,
                r.is_outsourced,
                r.order_number, -- Include order number
                r.created_at
            FROM raw_casting_log r
            JOIN items i ON r.handle_id = i.id
            ORDER BY r.created_at DESC
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
    error_log("Error in get_raw_casting.php: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { $conn->close(); }
}

echo json_encode($response);
exit();
?>