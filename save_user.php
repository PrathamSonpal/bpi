<?php
session_start();
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}

$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);
$conn->set_charset("utf8mb4");

// --- Collect Inputs ---
$full_name = trim($_POST['full_name']);
$mobile    = trim($_POST['mobile']);
$email     = trim($_POST['email']);
$role      = $_POST['role'];
$username  = trim($_POST['username']);
$password  = $_POST['password'];

if (!$full_name || !$username || !$password || !$role) {
    header("Location: employee_page.php?error=All required fields must be filled");
    exit();
}

// --- Check duplicate username ---
$chk = $conn->prepare("SELECT id FROM users WHERE username = ?");
$chk->bind_param("s", $username);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    header("Location: employee_page.php?error=Username already exists");
    exit();
}
$chk->close();

// --- Insert user ---
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (full_name, mobile, email, role, username, password, status)
    VALUES (?, ?, ?, ?, ?, ?, 'active')
");
$stmt->bind_param(
    "ssssss",
    $full_name,
    $mobile,
    $email,
    $role,
    $username,
    $hash
);
$stmt->execute();
$stmt->close();

header("Location: employee_page.php?success=Employee created successfully");
exit();
