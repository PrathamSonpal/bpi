<?php
// --- DB connection (update with your InfinityFree credentials) ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412"; // Corrected username
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock"; // Corrected database name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// --- Only allow POST requests ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['id']) || empty($_POST['id']) || !isset($_POST['user_id']) || empty($_POST['user_id'])) {
        echo "Item ID and User ID are required.";
        exit;
    }

    $id = intval($_POST['id']);
    $user_id = intval($_POST['user_id']);

    // Server-side check for user permissions
    $stmt_user = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();

    if ($user_data && $user_data['role'] === 'admin') {
        // Prepare statement to avoid SQL injection
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Item deleted successfully.";
        } else {
            echo "Error deleting item: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "Permission denied.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>
