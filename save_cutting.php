<?php
// save_cutting.php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["success" => false, "message" => "An error occurred."];

if (!isset($_SESSION['loggedIn'])) {
    $response['message'] = "Unauthorized.";
    http_response_code(401);
    echo json_encode($response);
    exit();
}

// --- Database Connection (Built-in) ---
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
    $response['message'] = "Database connection failed.";
    http_response_code(500);
    echo json_encode($response);
    exit();
}
// ------------------------------------

// Get data from POST
$handle_id = $_POST['handle_id'] ?? null;
$cut_pcs = $_POST['cut_pcs'] ?? null;
$order_number = $_POST['order_number'] ?? null;
$cut_date = $_POST['cut_date'] ?? null;
$description = $_POST['description'] ?? null;
// $user_id is not saved, which is fine

if (empty($handle_id) || empty($cut_pcs) || empty($cut_date)) {
    $response['message'] = "Handle, Cut Pieces, and Date are required.";
    echo json_encode($response);
    $conn->close();
    exit();
}

// Use NULL if empty
$order_number = !empty($order_number) ? $order_number : null;
$description = !empty($description) ? $description : null;

try {
    
    // --- THIS IS THE FIX ---
    // Removed 'added_by_user_id' from the query (5 columns)
    $stmt = $conn->prepare("INSERT INTO cutting_log (handle_id, cut_pcs, order_number, cut_date, description) VALUES (?, ?, ?, ?, ?)");
    
    // bind_param now correctly has 5 variables ("iisss")
    $stmt->bind_param("iisss", $handle_id, $cut_pcs, $order_number, $cut_date, $description);
    // -----------------------
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Cutting entry saved.";
    } else {
        $response['message'] = "Failed to save entry.";
    }
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = "Database Error: " . $e->getMessage();
    error_log("Error in save_cutting.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>