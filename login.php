<?php
// ✅ Enable CORS for GitHub Pages requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// ✅ Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// ✅ Get input
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// ✅ Query for user
$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ✅ Verify password hash
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
