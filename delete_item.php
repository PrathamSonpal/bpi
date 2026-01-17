<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// Only admin can delete
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

// --- DB connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Only POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(["success" => false, "message" => "Item ID is required"]);
        exit();
    }

    $id = intval($_POST['id']);

    // Check if item exists
    $stmt_check = $conn->prepare("SELECT image_path FROM items WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $item = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$item) {
        echo json_encode(["success" => false, "message" => "Item not found"]);
        exit();
    }

    // Delete item
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Also delete uploaded image if exists
        if (!empty($item['image_path']) && file_exists("uploads/" . $item['image_path'])) {
            @unlink("uploads/" . $item['image_path']);
        }
        echo json_encode(["success" => true, "message" => "Item deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting item: " . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}

$conn->close();
?>
