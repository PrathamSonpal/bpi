<?php
// Database credentials
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101"; // YOUR DATABASE PASSWORD HERE
$dbname = "if0_39812412_bpi_stock";

// Attempt to connect to MySQL
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful!";
$conn->close();
?>