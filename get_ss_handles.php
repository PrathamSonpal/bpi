<?php
// get_ss_handles.php
// Fetches ONLY Stainless Steel handles for the Cut/Bend page.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedIn'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode([]); // Send empty array on error
    exit;
}
$conn->set_charset("utf8mb4");

$handles = [];
try {
    // This query selects only Stainless Steel items
    $sql = "SELECT id, name FROM items 
            WHERE material = 'Stainless Steel (SS)' 
            ORDER BY name ASC";
            
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $handles[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error in get_ss_handles.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($handles);
?>