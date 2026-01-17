<?php
session_start();

// --- Security Check ---
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// --- Validate Inputs ---
$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$size = trim($_POST['size'] ?? '');
$material = trim($_POST['material'] ?? '');

if (!$id || !$name || !$size || !$material) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

// --- Sanitize Values ---
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$size = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
$material = htmlspecialchars($material, ENT_QUOTES, 'UTF-8');

// --- Fetch existing image ---
$stmt = $conn->prepare("SELECT image_path FROM items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$oldImage = $existing['image_path'] ?? null;
$stmt->close();

$image_path = $oldImage;

// --- Handle New Image Upload (if provided) ---
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $targetDir = __DIR__ . "/uploads/";

    // Ensure directory exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Validate file type and size
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        echo json_encode(["success" => false, "message" => "Only JPG, PNG, and WEBP files are allowed."]);
        exit();
    }

    if ($_FILES['image']['size'] > $maxSize) {
        echo json_encode(["success" => false, "message" => "Image exceeds 2MB limit."]);
        exit();
    }

    // Safe file naming
    $originalName = basename($_FILES['image']['name']);
    $safeName = preg_replace("/[^A-Za-z0-9_.-]/", "_", $originalName);
    $imageName = uniqid("img_", true) . "_" . $safeName;
    $targetFile = $targetDir . $imageName;

    // Move file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        $image_path = $imageName;

        // Delete old image if exists
        $oldFilePath = __DIR__ . "/uploads/" . $oldImage;
        if (!empty($oldImage) && file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error uploading image."]);
        exit();
    }
}

// --- Update Database ---
try {
    if ($image_path) {
        $sql = "UPDATE items SET name = ?, size = ?, material = ?, image_path = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $size, $material, $image_path, $id);
    } else {
        $sql = "UPDATE items SET name = ?, size = ?, material = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $size, $material, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Item updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update item."]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
