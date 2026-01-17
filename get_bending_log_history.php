<?php
// get_bending_log_history.php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['loggedIn'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = null;
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
    exit();
}
// ------------------------------------

$history = [];
try {
    // --- THIS IS THE FIX ---
    // Changed 'b.timestamp' to 'b.created_at' to match your table
    $sql = "SELECT b.*, i.name as handle_name 
            FROM bending_log b
            LEFT JOIN items i ON b.handle_id = i.id
            ORDER BY b.bend_date DESC, b.created_at DESC
            LIMIT 20";
    // -----------------------
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Error in get_bending_log_history.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($history);
?>