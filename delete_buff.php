<?php
session_start();
if (!isset($_SESSION['loggedIn'])) exit();

$conn = new mysqli("sql100.infinityfree.com","if0_39812412","Bpiapp0101","if0_39812412_bpi_stock");

$id = (int)$_GET['id'];
if ($id <= 0) {
    header("Location: buff_page.php?error=Invalid request");
    exit();
}

$stmt = $conn->prepare("DELETE FROM buffing_log WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: buff_page.php?success=Buffing entry deleted");
