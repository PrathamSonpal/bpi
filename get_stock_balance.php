<?php
header("Content-Type: application/json; charset=UTF-8");

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);

// This query calculates total stock added minus total stock sold for every item
$sql = "SELECT 
            i.id, 
            i.name, 
            i.size, 
            i.material,
            (SELECT IFNULL(SUM(quantity), 0) FROM stock_log WHERE item_id = i.id) AS total_added,
            (SELECT IFNULL(SUM(quantity), 0) FROM sales_log WHERE item_id = i.id) AS total_sold
        FROM items i";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $current_balance = (int)$row['total_added'] - (int)$row['total_sold'];
    $data[] = [
        "id" => $row['id'],
        "name" => $row['name'],
        "size" => $row['size'],
        "material" => $row['material'],
        "balance" => $current_balance
    ];
}

echo json_encode($data);
$conn->close();
?>