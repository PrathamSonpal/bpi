<?php
session_start();
if (!isset($_SESSION['loggedIn'])) exit();

$conn = new mysqli("sql100.infinityfree.com","if0_39812412","Bpiapp0101","if0_39812412_bpi_stock");

$id = (int)$_POST['id'];
$buffed_pcs = (int)$_POST['buffed_pcs'];
$buffing_date = $_POST['buffing_date'];
$description = $_POST['description'];

if ($id <= 0 || $buffed_pcs <= 0) {
    header("Location: buff_page.php?error=Invalid update");
    exit();
}

/* (Recommended) Re-validate balance here */

$stmt = $conn->prepare("
    UPDATE buffing_log
    SET buffed_pcs=?, buffing_date=?, description=?
    WHERE id=?
");
$stmt->bind_param("issi", $buffed_pcs, $buffing_date, $description, $id);
$stmt->execute();
$stmt->close();

header("Location: buff_page.php?success=Buffing entry updated");
