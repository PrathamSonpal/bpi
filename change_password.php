<?php
header("Content-Type: application/json");

// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Get POST values
$username = $_POST['username'] ?? '';
$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($username) || empty($old_password) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found."]);
    exit;
}

$row = $result->fetch_assoc();
$storedPassword = $row['password'];

// Verify old password (plain text OR hashed)
if ($storedPassword === $old_password || password_verify($old_password, $storedPassword)) {
    // Update new password (hashed for security)
    $hashedNewPass = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $update->bind_param("ss", $hashedNewPass, $username);

    if ($update->execute()) {
        echo json_encode(["success" => true, "message" => "Password changed successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating password."]);
    }
    $update->close();
} else {
    echo json_encode(["success" => false, "message" => "Old password is incorrect."]);
}

$stmt->close();
$conn->close();
?>
