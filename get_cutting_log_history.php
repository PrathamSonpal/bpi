<?php
// get_cutting_log_history.php
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
    // Changed 'c.timestamp' to 'c.created_at' to match your table
    $sql = "SELECT c.*, i.name as handle_name 
            FROM cutting_log c
            LEFT JOIN items i ON c.handle_id = i.id
            ORDER BY c.cut_date DESC, c.created_at DESC
            LIMIT 20";
    // -----------------------
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Error in get_cutting_log_history.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($history);
?>