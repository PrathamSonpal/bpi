<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DB connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'] ?? '';
    $size = $_POST['size'] ?? '';
    $material = $_POST['material'] ?? '';
    $user_id = $_POST['user_id'] ?? null;

    if ($name && $size && $material && $user_id) {
        // Server-side check for user permissions
        $stmt_user = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $user_data = $result_user->fetch_assoc();
        $stmt_user->close();

        if ($user_data && $user_data['role'] === 'admin') {
            $stmt = $conn->prepare("INSERT INTO items (name, size, material) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $size, $material);
            if ($stmt->execute()) {
                echo "Item added successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Permission denied.";
        }
    } else {
        echo "All fields required.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>
