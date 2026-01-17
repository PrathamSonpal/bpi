<?php
// save_bending.php
session_start();

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["success" => false, "message" => "An error occurred."];

// --- Check session ---
if (!isset($_SESSION['loggedIn'])) {
    $response['message'] = "Unauthorized.";
    http_response_code(401);
    echo json_encode($response);
    exit();
}

// --- Database connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    $response['message'] = "Database connection failed.";
    http_response_code(500);
    echo json_encode($response);
    exit();
}

// --- Collect POST data ---
$handle_id    = $_POST['handle_id'] ?? null;
$bent_pcs     = $_POST['bent_pcs'] ?? null;
$order_number = $_POST['order_number'] ?? null;
$bend_date    = $_POST['bend_date'] ?? null;
$description  = $_POST['description'] ?? null;

if (empty($handle_id) || empty($bent_pcs) || empty($bend_date)) {
    $response['message'] = "Handle, Bent Pieces, and Date are required.";
    echo json_encode($response);
    $conn->close();
    exit();
}

$order_number = !empty($order_number) ? $order_number : null;
$description  = !empty($description) ? $description : null;

// --- Validate stock availability before bending ---
try {
    $stmt_cut = $conn->prepare("SELECT IFNULL(SUM(cut_pcs),0) AS total_cut FROM cutting_log WHERE handle_id = ?");
    $stmt_cut->bind_param("i", $handle_id);
    $stmt_cut->execute();
    $total_cut = (int)$stmt_cut->get_result()->fetch_assoc()['total_cut'];
    $stmt_cut->close();

    $stmt_bend = $conn->prepare("SELECT IFNULL(SUM(bent_pcs),0) AS total_bent FROM bending_log WHERE handle_id = ?");
    $stmt_bend->bind_param("i", $handle_id);
    $stmt_bend->execute();
    $total_bent = (int)$stmt_bend->get_result()->fetch_assoc()['total_bent'];
    $stmt_bend->close();

    $available = $total_cut - $total_bent;
    if ($bent_pcs > $available) {
        $response['message'] = "Cannot bend {$bent_pcs} pcs. Only {$available} available from cutting.";
        echo json_encode($response);
        $conn->close();
        exit();
    }
} catch (Exception $e) {
    $response['message'] = "Stock validation failed: " . $e->getMessage();
    echo json_encode($response);
    $conn->close();
    exit();
}

// --- Save BENDING entry ---
try {
    $stmt = $conn->prepare("
        INSERT INTO bending_log (handle_id, bent_pcs, order_number, bend_date, description)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $handle_id, $bent_pcs, $order_number, $bend_date, $description);
    $stmt->execute();
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = "Database Error while inserting bending log: " . $e->getMessage();
    echo json_encode($response);
    $conn->close();
    exit();
}

// --- Create TURNING pending entry ---
try {
    $order_item_id = null;

    // Try to find related order item if exists
    if (!empty($order_number)) {
        $stmt_find = $conn->prepare("
            SELECT id FROM order_items 
            WHERE order_number = ? AND item_id = ?
            LIMIT 1
        ");
        $stmt_find->bind_param("si", $order_number, $handle_id);
        $stmt_find->execute();
        $res = $stmt_find->get_result();
        if ($row = $res->fetch_assoc()) {
            $order_item_id = $row['id'];
        }
        $stmt_find->close();
    }

    // Create turning entry (ready for turning)
    $stmt_turn = $conn->prepare("
        INSERT INTO turning_log (handle_id, ready_pcs, order_item_id, turning_date, description, order_number, created_at, status)
        VALUES (?, ?, ?, NOW(), ?, ?, NOW(), 'pending')
    ");
    $stmt_turn->bind_param("iiiss", $handle_id, $bent_pcs, $order_item_id, $description, $order_number);
    $stmt_turn->execute();
    $stmt_turn->close();

    // Update order stage if applicable
    if (!empty($order_number)) {
        $stmt_update = $conn->prepare("UPDATE orders SET current_stage = 'turning' WHERE order_number = ?");
        $stmt_update->bind_param("s", $order_number);
        $stmt_update->execute();
        $stmt_update->close();
    }

    $response['success'] = true;
    $response['message'] = "Bending saved and moved to turning.";

} catch (Exception $e) {
    $response['message'] = "Error while creating turning entry: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
