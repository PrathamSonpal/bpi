<?php
session_start();
if (!isset($_SESSION['loggedIn'])) exit();

$conn = new mysqli("sql100.infinityfree.com","if0_39812412","Bpiapp0101","if0_39812412_bpi_stock");

$id = (int)$_POST['id'];
$ready_pcs = (int)$_POST['ready_pcs'];
$turning_date = $_POST['turning_date'];
$description = $_POST['description'];

if ($id <= 0 || $ready_pcs <= 0) {
    header("Location: turning_page.php?error=Invalid update");
    exit();
}

/*
 IMPORTANT:
 You may optionally re-run the SAME stock validation logic here
 (recommended for safety)
*/

$stmt = $conn->prepare("
    UPDATE turning_log
    SET ready_pcs=?, turning_date=?, description=?
    WHERE id=?
");
$stmt->bind_param("issi", $ready_pcs, $turning_date, $description, $id);
$stmt->execute();
$stmt->close();

header("Location: turning_page.php?success=1");
