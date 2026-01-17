<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // <-- Already present, but good to confirm

// Database connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";  // make sure DB name is correct

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Fetch handles with material='Malleable'
$sql = "SELECT id, name FROM items WHERE material='Malleable' ORDER BY name ASC";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit;
}

$handles = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $handles[] = $row;
    }
}

echo json_encode($handles);
$conn->close();
?>