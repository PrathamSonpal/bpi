<?php
// casting_complete.php
include '../db_connect.php'; // adjust path
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $casting_id = intval($_POST['casting_id']);
    // optional fields the casting team might submit when completing
    $casting_date = date('Y-m-d H:i:s');
    $metalin_weight = isset($_POST['metalin_weight']) ? mysqli_real_escape_string($conn, $_POST['metalin_weight']) : NULL;
    $melo = isset($_POST['melo']) ? mysqli_real_escape_string($conn, $_POST['melo']) : NULL;
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';

    // 1) Get casting job details
    $res = mysqli_query($conn, "SELECT * FROM casting_log WHERE id='$casting_id' LIMIT 1");
    $job = mysqli_fetch_assoc($res);
    if (!$job) { die("Job not found"); }

    // 2) Mark casting completed
    $update = "UPDATE casting_log SET status='completed', casting_date='$casting_date', metalin_weight=" . ($metalin_weight !== NULL ? "'$metalin_weight'" : "NULL") .
              ", melo=" . ($melo !== NULL ? "'$melo'" : "NULL") . ", description='" . mysqli_real_escape_string($conn,$description) . "' WHERE id='$casting_id'";
    mysqli_query($conn, $update);

    // 3) Create turning job (assumes turning_log at least has order_id, order_item_id, quantity, status, created_at)
    $order_item_id = intval($job['order_item_id']);
    $order_number = intval($job['order_number']);

    // get item_id and quantity from order_items
    $oi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT item_id, quantity FROM order_items WHERE id='$order_item_id' LIMIT 1"));
    $item_id = $oi['item_id'];
    $qty = $oi['quantity'];

    mysqli_query($conn, "INSERT INTO turning_log (order_id, order_item_id, item_id, quantity, status, created_at)
                         VALUES ('$order_number', '$order_item_id', '$item_id', '$qty', 'pending', NOW())");

    // 4) Update orders.current_stage -> turning
    mysqli_query($conn, "UPDATE orders SET current_stage='turning' WHERE id='$order_number'");

    // redirect back to casting dashboard
    header("Location: casting_dashboard.php?msg=completed");
    exit;
}
?>
