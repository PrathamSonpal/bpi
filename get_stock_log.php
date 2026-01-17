<?php
// Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS & JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB Connection failed: " . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

try {
    // --- UPDATED SQL QUERY ---
    $sql = "SELECT 
                s.id, s.item_id, s.quantity, s.timestamp, s.added_by,
                s.log_type, -- <-- Added the log_type column
                
                -- Use a CASE statement to handle the '0' (system) user
                CASE 
                    WHEN s.added_by = 0 THEN 'System/Guest Order'
                    ELSE u.full_name
                END AS added_by_name,
                
                i.name AS item_name, i.size, i.material
            FROM stock_log s
            LEFT JOIN users u ON s.added_by = u.id
            LEFT JOIN items i ON s.item_id = i.id
            ORDER BY s.timestamp DESC
            LIMIT 500";

    $result = $conn->query($sql);

    $stockLog = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stockLog[] = [
                "id" => $row['id'],
                "item_id" => $row['item_id'],
                "item_name" => $row['item_name'] ?? "Unknown Item",
                "size" => $row['size'] ?? "",
                "material" => $row['material'] ?? "",
                "log_type" => $row['log_type'] ?? "UNKNOWN", // <-- Added log_type
                "quantity" => (int)$row['quantity'],
                "timestamp" => $row['timestamp'],
                "added_by" => $row['added_by'],
                "added_by_name" => $row['added_by_name'] ?? "Unknown User"
            ];
        }
    }

    echo json_encode($stockLog);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>