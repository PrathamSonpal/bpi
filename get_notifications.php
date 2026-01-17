<?php
// Fetches unread notifications for the logged-in user's department
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Adjust if needed
session_start();

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$response = ["success" => false, "notifications" => [], "error" => null];

// --- Authentication & Role Check ---
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || !isset($_SESSION['role'])) {
    $response['error'] = "User not logged in or role not set.";
    echo json_encode($response);
    exit;
}
$role = $_SESSION['role'];

// Map roles to department names used in the notifications table
$department = '';
switch (strtolower($role)) { // Use strtolower for case-insensitivity
    case 'casting': $department = 'CASTING'; break;
    case 'turning': $department = 'TURNING'; break;
    case 'buff':    $department = 'BUFFING'; break;
    case 'packing': $department = 'PACKING'; break;
    case 'admin':   $department = 'ADMIN';   break;
    default:
        $response['error'] = "User role ('" . htmlspecialchars($role) . "') not recognized.";
        echo json_encode($response);
        exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);
    // $conn->query("SET time_zone = '+05:30'"); // Optional

    // Fetch unread notifications for the department
    $stmt = $conn->prepare("
        SELECT id, message, related_order_number, related_order_item_id, created_at
        FROM notifications
        WHERE department = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 20
    ");
    if (!$stmt) throw new Exception("Prepare statement failed: " . $conn->error);

    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Format timestamp for display
            $row['created_at_formatted'] = date('d/m/y H:i', strtotime($row['created_at']));
            $notifications[] = $row;
        }
        $result->close();
    }
    $stmt->close();
    $conn->close();

    $response["success"] = true;
    $response["notifications"] = $notifications;

} catch (Exception $e) {
    $response["error"] = "Database Error: " . $e->getMessage();
    error_log("Error in get_notifications.php: " . $e->getMessage());
    if (isset($conn) && $conn->ping()) { $conn->close(); }
}

echo json_encode($response);
exit();
?>