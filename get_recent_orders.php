<?php
header("Content-Type: application/json");

// --- DB Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock"; // ✅ ensure this matches your actual DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

// --- Get department filter from request ---
$department = isset($_GET['department']) ? strtolower($_GET['department']) : null;

// --- Build dynamic WHERE clause ---
$where = "";
if (in_array($department, ['casting', 'turning', 'buffing', 'packing'])) {
    $where = "WHERE oi.status LIKE '%" . strtoupper($department) . "%' 
              OR oi.status = 'pending'"; 
}

// --- Fetch recent/pending orders ---
$sql = "
    SELECT 
        o.order_number,
        o.customer_name,
        MAX(CASE WHEN oi.status LIKE '%CASTING%' THEN oi.status ELSE NULL END) AS casting_status,
        MAX(CASE WHEN oi.status LIKE '%TURNING%' THEN oi.status ELSE NULL END) AS turning_status,
        MAX(CASE WHEN oi.status LIKE '%BUFFING%' THEN oi.status ELSE NULL END) AS buffing_status,
        MAX(CASE WHEN oi.status LIKE '%PACKING%' THEN oi.status ELSE NULL END) AS packing_status,
        MAX(o.created_at) AS created_at
    FROM orders o
    LEFT JOIN order_items oi ON o.order_number = oi.order_number
    $where
    GROUP BY o.order_number
    ORDER BY created_at DESC
    LIMIT 10
";

$result = $conn->query($sql);
$orders = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            "order_number" => $row["order_number"],
            "customer_name" => $row["customer_name"],
            "casting_status" => normalizeStatus($row["casting_status"]),
            "turning_status" => normalizeStatus($row["turning_status"]),
            "buffing_status" => normalizeStatus($row["buffing_status"]),
            "packing_status" => normalizeStatus($row["packing_status"]),
            "created_at" => $row["created_at"]
        ];
    }
}

echo json_encode($orders);

// --- helper function ---
function normalizeStatus($status) {
    if (!$status) return "pending";
    $status = strtoupper($status);
    if (strpos($status, "COMPLETE") !== false) return "completed";
    if (strpos($status, "PENDING") !== false) return "pending";
    return strtolower($status);
}

$conn->close();
?>
