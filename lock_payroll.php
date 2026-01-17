<?php
session_start();
if ($_SESSION['role'] !== 'admin') exit();

$conn = new mysqli(
  "sql100.infinityfree.com",
  "if0_39812412",
  "Bpiapp0101",
  "if0_39812412_bpi_stock"
);

$month = (int)$_POST['month'];
$year  = (int)$_POST['year'];

$stmt = $conn->prepare("
  UPDATE payroll_monthly
  SET status='locked', locked_at=NOW()
  WHERE month=? AND year=? AND status='draft'
");
$stmt->bind_param("ii",$month,$year);
$stmt->execute();

echo json_encode(['success'=>true]);
