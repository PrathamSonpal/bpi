<?php
session_start();
if (!isset($_SESSION['loggedIn'])) exit();

$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);

$id = (int)$_POST['id'];
$packed_pcs = (int)$_POST['packed_pcs'];
$packing_date = $_POST['packing_date'];
$description = $_POST['description'];

if ($id <= 0 || $packed_pcs <= 0) {
    header("Location: packing_page.php?error=Invalid input");
    exit();
}

/* 🔒 OPTIONAL (Recommended):
   Validate buffed - packed balance here
*/

$stmt = $conn->prepare("
    UPDATE packing_log
    SET packed_pcs=?, packing_date=?, description=?
    WHERE id=?
");
$stmt->bind_param("issi", $packed_pcs, $packing_date, $description, $id);
$stmt->execute();
$stmt->close();

header("Location: packing_page.php?success=Packing entry updated");
