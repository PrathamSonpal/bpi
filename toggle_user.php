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

$id = (int)$_GET['id'];
$currentUserId = $_SESSION['user_id'] ?? 0;

// Prevent self-disable
if ($id === $currentUserId) {
    header("Location: employee_page.php?error=You cannot disable your own account");
    exit();
}

$conn->query("
    UPDATE users
    SET status = IF(status='active','inactive','active')
    WHERE id = $id
");

header("Location: employee_page.php?success=User status updated");
exit();
