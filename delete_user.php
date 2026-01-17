<?php
session_start();

/* ================= Security Checks ================= */
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: employee_page.php?error=Invalid user ID");
    exit();
}

/* Prevent self-deletion */
if ($id == $_SESSION['user_id']) {
    header("Location: employee_page.php?error=You cannot delete your own account");
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

/* ================= Delete User ================= */
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: employee_page.php?success=Employee deleted successfully");
} else {
    header("Location: employee_page.php?error=Failed to delete employee");
}

$stmt->close();
$conn->close();
exit();
