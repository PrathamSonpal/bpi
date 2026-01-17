<?php
// get_overall_bending_balance.php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["success" => false, "data" => [], "message" => ""];

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

try {
    $sql = "
        SELECT 
            i.id AS handle_id,
            i.name AS handle_name,
            COALESCE(cuts.total_cut, 0) AS total_cut,
            COALESCE(bends.total_bent, 0) AS total_bent,
            (COALESCE(cuts.total_cut, 0) - COALESCE(bends.total_bent, 0)) AS balance
        FROM 
            items i
        LEFT JOIN 
            (SELECT handle_id, SUM(cut_pcs) AS total_cut FROM cutting_log GROUP BY handle_id) AS cuts
            ON i.id = cuts.handle_id
        LEFT JOIN 
            (SELECT handle_id, SUM(bent_pcs) AS total_bent FROM bending_log GROUP BY handle_id) AS bends
            ON i.id = bends.handle_id
        WHERE
            COALESCE(cuts.total_cut, 0) > 0 OR COALESCE(bends.total_bent, 0) > 0
        ORDER BY 
            handle_name;
    ";

    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    $response['message'] = "Database Error: " . $e->getMessage();
    error_log("Error in get_overall_bending_balance.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>