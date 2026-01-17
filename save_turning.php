<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}

/* ---------- DB CONNECTION ---------- */
$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);
if ($conn->connect_error) {
    header("Location: turning_page.php?error=" . urlencode("DB connection failed"));
    exit();
}
$conn->set_charset("utf8mb4");

/* ---------- INPUT ---------- */
$handle_id    = isset($_POST['handle_id']) ? (int)$_POST['handle_id'] : 0;
$ready_pcs   = isset($_POST['ready_pcs']) ? (int)$_POST['ready_pcs'] : 0;
$turning_date = $_POST['turning_date'] ?? '';
$description  = $_POST['description'] ?? null;
$order_number = !empty($_POST['order_number']) ? trim($_POST['order_number']) : null;

if (!$handle_id || $ready_pcs <= 0 || !$turning_date) {
    header("Location: turning_page.php?error=" . urlencode("Handle, Pieces and Date are required."));
    exit();
}

/* ---------- GET HANDLE MATERIAL ---------- */
$stmt = $conn->prepare("SELECT material FROM items WHERE id = ?");
$stmt->bind_param("i", $handle_id);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc()['material'] ?? null;
$stmt->close();

if (!$material) {
    header("Location: turning_page.php?error=" . urlencode("Invalid handle selected."));
    exit();
}

/* ---------- MATERIAL-AWARE STOCK CHECK ---------- */
try {

    if ($material === 'Malleable') {

        // CASTING → TURNING
        $sql = "
            SELECT 
            (IFNULL(rc.total_raw,0) 
             - IFNULL(rt.total_transferred,0) 
             - IFNULL(t.total_turned,0)) AS available
            FROM items i
            LEFT JOIN (SELECT handle_id, SUM(total_pcs) total_raw FROM raw_casting_log GROUP BY handle_id) rc
                ON rc.handle_id = i.id
            LEFT JOIN (SELECT handle_id, SUM(quantity) total_transferred FROM raw_transfers GROUP BY handle_id) rt
                ON rt.handle_id = i.id
            LEFT JOIN (SELECT handle_id, SUM(ready_pcs) total_turned FROM turning_log GROUP BY handle_id) t
                ON t.handle_id = i.id
            WHERE i.id = ?
        ";

    } else {
        // SS → BENDING → TURNING
        $sql = "
            SELECT 
            (IFNULL(b.total_bent,0) 
             - IFNULL(t.total_turned,0)) AS available
            FROM items i
            LEFT JOIN (SELECT handle_id, SUM(bent_pcs) total_bent FROM bending_log GROUP BY handle_id) b
                ON b.handle_id = i.id
            LEFT JOIN (SELECT handle_id, SUM(ready_pcs) total_turned FROM turning_log GROUP BY handle_id) t
                ON t.handle_id = i.id
            WHERE i.id = ?
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $handle_id);
    $stmt->execute();
    $available = (int)($stmt->get_result()->fetch_assoc()['available'] ?? 0);
    $stmt->close();

    if ($ready_pcs > $available) {
        header("Location: turning_page.php?error=" . urlencode(
            "Cannot add $ready_pcs pcs. Only $available available for $material."
        ));
        exit();
    }

} catch (Exception $e) {
    header("Location: turning_page.php?error=" . urlencode("Stock validation failed."));
    exit();
}

/* ---------- INSERT TURNING ENTRY ---------- */
$stmt = $conn->prepare("
    INSERT INTO turning_log 
    (handle_id, ready_pcs, turning_date, description, order_number, created_at, status)
    VALUES (?, ?, ?, ?, ?, NOW(), 'completed')
");
$stmt->bind_param("iisss", $handle_id, $ready_pcs, $turning_date, $description, $order_number);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header("Location: turning_page.php?error=" . urlencode("Error saving turning entry."));
    exit();
}

/* ---------- ORDER FLOW → BUFFING ---------- */
if ($order_number) {

    // Move order to buffing stage
    $u = $conn->prepare("UPDATE orders SET current_stage = 'buffing' WHERE order_number = ?");
    $u->bind_param("s", $order_number);
    $u->execute();
    $u->close();

    // Create pending buffing entry
    $b = $conn->prepare("
        INSERT INTO buffing_log 
        (handle_id, buffed_pcs, order_item_id, buffing_date, description, created_at, status, order_number)
        VALUES (?, 0, NULL, NOW(), 'Awaiting Buffing', NOW(), 'pending', ?)
    ");
    $b->bind_param("is", $handle_id, $order_number);
    $b->execute();
    $b->close();
}

/* ---------- SUCCESS ---------- */
header("Location: turning_page.php?success=1");
exit();

$conn->close();
?>
