<?php
// get_bending_stock.php
$DB_HOST = "sql100.infinityfree.com";
$DB_USER = "if0_39812412";
$DB_PASS = "Bpiapp0101";
$DB_NAME = "if0_39812412_bpi_stock";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit();
}

$handle_id = (int)($_GET['handle_id'] ?? 0);
if (!$handle_id) {
    echo json_encode(["success" => false, "message" => "Handle ID missing"]);
    exit();
}

// Calculate available cut pieces not yet bent
$sql_cut = "SELECT IFNULL(SUM(cut_pcs),0) AS total_cut FROM cutting_log WHERE handle_id = ?";
$stmt = $conn->prepare($sql_cut);
$stmt->bind_param("i", $handle_id);
$stmt->execute();
$res = $stmt->get_result();
$total_cut = (int)($res->fetch_assoc()['total_cut'] ?? 0);
$stmt->close();

$sql_bent = "SELECT IFNULL(SUM(bent_pcs),0) AS total_bent FROM bending_log WHERE handle_id = ?";
$stmt = $conn->prepare($sql_bent);
$stmt->bind_param("i", $handle_id);
$stmt->execute();
$res = $stmt->get_result();
$total_bent = (int)($res->fetch_assoc()['total_bent'] ?? 0);
$stmt->close();

$balance = $total_cut - $total_bent;
echo json_encode(["success" => true, "balance" => $balance]);
?>
