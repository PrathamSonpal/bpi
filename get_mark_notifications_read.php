<?php
// Marks specified notifications as read for the current user's department
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Adjust if needed
session_start();

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$response = ["success" => false, "error" => null];

// --- Authentication & Role Check ---
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || !isset($_SESSION['role'])) {
    $response['error'] = "User not logged in or role not set.";
    echo json_encode($response);
    exit;
}
$role = $_SESSION['role'];
// Map role to department
$department = '';
switch (strtolower($role)) {
    case 'casting': $department = 'CASTING'; break;
    case 'turning': $department = 'TURNING'; break;
    case 'buff':    $department = 'BUFFING'; break;
    case 'packing': $department = 'PACKING'; break;
    case 'admin':   $department = 'ADMIN';   break;
    default:
        $response['error'] = "User role not recognized.";
        echo json_encode($response);
        exit;
}

// Get notification IDs from JSON body sent by JavaScript
$input = json_decode(file_get_contents('php://input'), true);
$notification_ids = $input['ids'] ?? null;

// Validate input
if (!is_array($notification_ids) || empty($notification_ids)) {
    $response['error'] = "No notification IDs provided.";
    echo json_encode($response);
    exit;
}

// Sanitize IDs
$sanitized_ids = array_filter(array_map('intval', $notification_ids), fn($id) => $id > 0);

if (empty($sanitized_ids)) {
    $response['error'] = "Invalid notification IDs provided.";
    echo json_encode($response);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);
    // $conn->query("SET time_zone = '+05:30'"); // Optional

    // Create placeholders for IN clause (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    // Create type string (e.g., "siii" if department check and 3 IDs)
    $types = 's' . str_repeat('i', count($sanitized_ids));
    $params = $sanitized_ids;
    array_unshift($params, $department); // Add department to the beginning of params array

    // Prepare SQL - IMPORTANT: Only mark read for the user's own department!
    $sql = "UPDATE notifications SET is_read = 1 WHERE department = ? AND id IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare statement failed: " . $conn->error);

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params); // Use splat operator (...)

    if ($stmt->execute()) {
        $response["success"] = true;
        // $response['updated_count'] = $stmt->affected_rows; // Optional
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