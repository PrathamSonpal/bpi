<?php
// FILE: get_available_items.php

// 1. Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database Connection Failed: " . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// ---------------------------------------------------------
// QUERY: Split 'stock_log' into Finished vs Raw based on Label
// ---------------------------------------------------------
$sql = "SELECT 
            i.id, 
            i.name, 
            i.size, 
            i.material,

            -- FINISHED (PACKED)
            (
                COALESCE((
                    SELECT SUM(quantity) 
                    FROM stock_log 
                    WHERE item_id = i.id 
                    AND (
                        log_type IS NULL 
                        OR log_type = ''
                        OR (
                            LOWER(log_type) NOT LIKE '%raw%'
                            AND LOWER(log_type) NOT LIKE '%cast%'
                        )
                    )
                ), 0)
                -
                COALESCE((
                    SELECT SUM(quantity) 
                    FROM sales_log 
                    WHERE item_id = i.id 
                    AND sale_type = 'finished'
                ), 0)
            ) AS finished_stock,

            -- RAW (CASTED)
            (
                COALESCE((
                    SELECT SUM(quantity) 
                    FROM stock_log 
                    WHERE item_id = i.id 
                    AND (
                        LOWER(log_type) LIKE '%raw%'
                        OR LOWER(log_type) LIKE '%cast%'
                    )
                ), 0)
                -
                COALESCE((
                    SELECT SUM(quantity) 
                    FROM sales_log 
                    WHERE item_id = i.id 
                    AND sale_type = 'raw'
                ), 0)
            ) AS raw_stock

        FROM items i
        ORDER BY i.name ASC, i.size ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    die(json_encode(["error" => "SQL Query Failed: " . $conn->error]));
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'size' => $row['size'],
        'material' => $row['material'],
        'finished_stock' => (int)$row['finished_stock'], 
        'raw_stock' => (int)$row['raw_stock']
    ];
}

echo json_encode($data);
$conn->close();
?>