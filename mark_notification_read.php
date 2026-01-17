<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
session_start();

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$response = ["success" => false, "error" => null];

// Get notification IDs from JSON body
$input = json_decode(file_get_contents('php://input'), true);
$notification_ids = $input['ids'] ?? null;

// Validate input
if (!is_array($notification_ids) || empty($notification_ids)) {
    $response['error'] = "No notification IDs provided.";
    echo json_encode($response);
    exit;
}

// Sanitize IDs to ensure they are integers
$sanitized_ids = [];
foreach ($notification_ids as $id) {
    if (filter_var($id, FILTER_VALIDATE_INT)) {
        $sanitized_ids[] = (int)$id;
    }
}

if (empty($sanitized_ids)) {
    $response['error'] = "Invalid notification IDs provided.";
    echo json_encode($response);
    exit;
}

// Determine department from role (for security - optional but good)
$department = '';
$role = $_SESSION['role'] ?? null;
switch ($role) { /* ... Map roles to departments as in get_notifications.php ... */ }
// if (empty($department)) { /* ... Handle unauthorized role ... */ }


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");

    // Create placeholders for IN clause (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    // Create type string (e.g., "iii" if 3 IDs)
    $types = str_repeat('i', count($sanitized_ids));

    // Update notifications - Add department check if needed for security
    $sql = "UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders)"; // AND department = ? (add if needed)
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    // Bind parameters dynamically
    // If adding department check, add $department at the end and 's' to $types
    $stmt->bind_param($types, ...$sanitized_ids);

    if ($stmt->execute()) {
        $response["success"] = true;
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response["error"] = "Database Error: " . $e->getMessage();
    error_log("Error in mark_notifications_read.php: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { $conn->close(); }
}

echo json_encode($response);
exit();
?>