<?php
// Add error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// DB connection
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock"; // <-- FIX 1: This was the wrong database name

$conn = null;
try {
    $conn = new mysqli($host, $user, $pass, $db);
} catch (Exception $e) {
    die(json_encode(['status' => 'error', 'message' => 'DB connection failed: ' . $e->getMessage()]));
}

// --- Fetch total items ---
$totalItemsResult = $conn->query("SELECT COUNT(*) as total FROM items");
$totalItems = $totalItemsResult->fetch_assoc()['total'] ?? 0;

// --- Fetch pending orders ---
// FIX 2: Your 'place_order.php' script inserts 'pending' (lowercase), not 'Pending' (uppercase).
$pendingOrdersResult = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status='pending'"); 
$pendingOrders = $pendingOrdersResult->fetch_assoc()['total'] ?? 0;

// --- Fetch low stock items ---
// FIX 3: Your 'items' table uses 'name' and 'stock', not 'item_name' or 'stock_quantity'
$lowStockResult = $conn->query("SELECT name, stock FROM items WHERE stock <= 10 LIMIT 5");
$lowStock = [];
while($row = $lowStockResult->fetch_assoc()){
    $lowStock[] = $row;
}

// --- Fetch recent orders ---
// FIX 4: Your 'orders' table uses 'order_number' and 'created_at'. 
// It does not have 'order_id' or 'total_amount'. I've added 'mobile' instead.
$recentOrdersResult = $conn->query("SELECT order_number, customer_name, mobile, created_at 
                                       FROM orders ORDER BY created_at DESC LIMIT 5");
$recentOrders = [];
while($row = $recentOrdersResult->fetch_assoc()){
    $recentOrders[] = $row;
}

$conn->close(); // Close the connection

echo json_encode([
    'status' => 'success',
    'totalItems' => $totalItems,
    'pendingOrders' => $pendingOrders,
    'lowStock' => $lowStock,
    'recentOrders' => $recentOrders
]);
?>