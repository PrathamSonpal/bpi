<?php
session_start();

/* ================= Security Checks ================= */
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employee_page.php?error=Invalid request");
    exit();
}

/* ================= DB Connection ================= */
$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);
if ($conn->connect_error) {
    die("Database connection failed");
}
$conn->set_charset("utf8mb4");

/* ================= Input Validation ================= */
$id        = intval($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$role      = trim($_POST['role'] ?? '');
$mobile    = trim($_POST['mobile'] ?? '');
$email     = trim($_POST['email'] ?? '');

if ($id <= 0 || $full_name === '' || $username === '' || $role === '') {
    header("Location: employee_page.php?error=Missing required fields");
    exit();
}

/* ================= Update User ================= */
$stmt = $conn->prepare("
    UPDATE users
    SET full_name = ?, username = ?, role = ?, mobile = ?, email = ?
    WHERE id = ?
");
$stmt->bind_param(
    "sssssi",
    $full_name,
    $username,
    $role,
    $mobile,
    $email,
    $id
);

if ($stmt->execute()) {
    header("Location: employee_page.php?success=Employee updated successfully");
} else {
    header("Location: employee_page.php?error=Failed to update employee");
}

$stmt->close();
$conn->close();
exit();
