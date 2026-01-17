<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error output in production
error_reporting(0);

/* ---------- DATABASE CONFIG ---------- */
$host   = "sql100.infinityfree.com";
$user   = "if0_39812412";
$pass   = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

/* ---------- DB CONNECTION ---------- */
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

/* ---------- ONLY POST ALLOWED ---------- */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request"
    ]);
    exit;
}

/* ---------- INPUT ---------- */
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        "success" => false,
        "message" => "Username and password required"
    ]);
    exit;
}

/* ---------- FETCH USER ---------- */
$stmt = $conn->prepare(
    "SELECT id, username, password, role, status
     FROM users
     WHERE BINARY username = ?
     LIMIT 1"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

/* ---------- USER NOT FOUND ---------- */
if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid username or password"
    ]);
    exit;
}

$userRow = $result->fetch_assoc();

/* ---------- STATUS CHECK (CRITICAL FIX) ---------- */
if ($userRow['status'] !== 'active') {
    echo json_encode([
        "success" => false,
        "message" => "Your account has been disabled. Please contact admin."
    ]);
    exit;
}

/* ---------- PASSWORD CHECK ---------- */
if (!password_verify($password, $userRow['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect password"
    ]);
    exit;
}

/* ---------- LOGIN SUCCESS ---------- */
$_SESSION['loggedIn'] = true;
$_SESSION['user_id']  = $userRow['id'];
$_SESSION['username'] = $userRow['username'];
$_SESSION['role']     = $userRow['role'];
$_SESSION['status']   = $userRow['status']; // used for session guard

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "role"    => $userRow['role']
]);

$conn->close();
exit;
