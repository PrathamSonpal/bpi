<?php
// packing_complete.php
include '../db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packing_id = intval($_POST['packing_id']);
    $res = mysqli_query($conn, "SELECT * FROM packing_log WHERE id='$packing_id' LIMIT 1");
    $job = mysqli_fetch_assoc($res);
    if (!$job) die("Job not found");

    // mark packing completed
    mysqli_query($conn, "UPDATE packing_log SET status='completed' WHERE id='$packing_id'");

    // add to stock_log (increase available_qty)
    // assumes stock_log has columns: item_id, available_qty
    $item_id = intval($job['item_id']);
    $qty = intval($job['quantity']);

    $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM stock_log WHERE item_id='$item_id' LIMIT 1"));
    if ($s) {
        mysqli_query($conn, "UPDATE stock_log SET available_qty = available_qty + $qty WHERE item_id='$item_id'");
    } else {
        mysqli_query($conn, "INSERT INTO stock_log (item_id, available_qty, created_at) VALUES ('$item_id', '$qty', NOW())");
    }

    // mark order completed or ready_in_stock
    mysqli_query($conn, "UPDATE orders SET current_stage='ready_in_stock' WHERE id='{$job['order_id']}'");

    header("Location: packing_dashboard.php?msg=completed");
    exit;
}
?>
