<?php
// Database connection
$host = "sql100.infinityfree.com"; // CORRECTED HOSTNAME
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "DB Connection failed: " . $conn->connect_error]);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    echo json_encode([
        "success" => true,
        "username" => $user['username'],
        "user_id" => $user['id'],
        "role" => $user['role']
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Invalid credentials"]);
}

$stmt->close();
$conn->close();
?>
