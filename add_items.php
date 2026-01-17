<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB Connection failed: " . $conn->connect_error]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST['name'] ?? '');
    $size     = trim($_POST['size'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $image_path = null;

    if (empty($name) || empty($size) || empty($material)) {
        echo json_encode(["success" => false, "message" => "All fields are required!"]);
        exit;
    }

    // Handle file upload
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['image']['name'];
        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $new_file_name = uniqid() . '.' . $file_ext;
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = $new_file_name;
            } else {
                echo json_encode(["success" => false, "message" => "Failed to upload image."]);
                exit;
            }
        } else {
            echo json_encode(["success" => false, "message" => "Invalid file type. Only JPG, JPEG, PNG, GIF allowed."]);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO items (name, size, material, image_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $size, $material, $image_path);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Item added successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

$conn->close();
?>
