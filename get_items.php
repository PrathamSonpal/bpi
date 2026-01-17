<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$dbname = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch items
$sql = "SELECT id, name, size, material, image_path FROM items ORDER BY name ASC";
$result = $conn->query($sql);

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Add a computed ready_stock if needed (e.g., 0 initially)
        $row['ready_stock'] = 0;
        $items[] = $row;
    }
    echo json_encode($items);
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
}

$conn->close();
?>
