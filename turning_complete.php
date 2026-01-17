<?php
// turning_complete.php
include '../db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turning_id = intval($_POST['turning_id']);
    $res = mysqli_query($conn, "SELECT * FROM turning_log WHERE id='$turning_id' LIMIT 1");
    $job = mysqli_fetch_assoc($res);
    if (!$job) die("Job not found");

    // mark turning completed
    mysqli_query($conn, "UPDATE turning_log SET status='completed' WHERE id='$turning_id'");

    // create buffing log
    mysqli_query($conn, "INSERT INTO buffing_log (order_id, order_item_id, item_id, quantity, status, created_at)
                         VALUES ('{$job['order_id']}', '{$job['order_item_id']}', '{$job['item_id']}', '{$job['quantity']}', 'pending', NOW())");

    // update order stage
    mysqli_query($conn, "UPDATE orders SET current_stage='buffing' WHERE id='{$job['order_id']}'");
    header("Location: turning_dashboard.php?msg=completed");
    exit;
}
?>
