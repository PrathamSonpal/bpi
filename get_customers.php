<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([]);
    exit();
}

$sql = "SELECT DISTINCT TRIM(COALESCE(customer_name, customer, name, '')) AS customer FROM orders WHERE COALESCE(customer_name, customer, name) IS NOT NULL AND TRIM(COALESCE(customer_name, customer, name, '')) <> '' ORDER BY customer ASC";
$res = $conn->query($sql);
$customers = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $customers[] = $r['customer'];
}
echo json_encode($customers);
$conn->close();
?>
