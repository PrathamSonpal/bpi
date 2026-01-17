<?php
session_start();
// --- Auth Check ---
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'];
if ($role !== 'packing' && $role !== 'admin') {
    header("Location: packing_page.php?error=Unauthorized access");
    exit;
}
$added_by = $_SESSION['user_id'] ?? 0; // User ID for logs

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header("Location: packing_page.php?error=Database connection failed");
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Get POST data ---
$handle_id    = $_POST['handle_id'] ?? null;
$packed_pcs   = $_POST['packed_pcs'] ?? null;
$packing_date = $_POST['packing_date'] ?? null;
$description  = $_POST['description'] ?? null;
$order_number = isset($_POST['order_number']) && $_POST['order_number'] !== '' ? trim($_POST['order_number']) : null;

// --- Validation ---
if (empty($handle_id) || empty($packed_pcs) || empty($packing_date)) {
    header("Location: packing_page.php?error=Handle, Pieces, and Date are required.");
    exit;
}
$packed_pcs_int = (int)$packed_pcs;
if ($packed_pcs_int <= 0) {
     header("Location: packing_page.php?error=Pieces must be greater than 0.");
     exit;
}
$handle_id_int = (int)$handle_id;
$description = $description ? trim($description) : null;

try {
    $conn->begin_transaction();

    // --- 1. Get Opening Stock (Total from Buffing Log) ---
    $opening_stock = 0;
    $stmt_buffed = $conn->prepare("SELECT SUM(buffed_pcs) AS total_buffed FROM buffing_log WHERE handle_id = ?");
    $stmt_buffed->bind_param("i", $handle_id_int);
    $stmt_buffed->execute();
    $res_buffed = $stmt_buffed->get_result();
    $opening_stock = (int)($res_buffed->fetch_assoc()['total_buffed'] ?? 0);
    $stmt_buffed->close();

    // --- 2. Get Total Packed So Far ---
    $total_packed = 0;
    $stmt_packed = $conn->prepare("SELECT SUM(packed_pcs) AS total_packed FROM packing_log WHERE handle_id = ?");
    $stmt_packed->bind_param("i", $handle_id_int);
    $stmt_packed->execute();
    $res_packed = $stmt_packed->get_result();
    $total_packed = (int)($res_packed->fetch_assoc()['total_packed'] ?? 0);
    $stmt_packed->close();

    // --- 3. Calculate balance (Available to pack) ---
    $balance = $opening_stock - $total_packed;
    if ($packed_pcs_int > $balance) {
        throw new Exception("Cannot pack $packed_pcs_int pcs. Only $balance buffed pieces available.");
    }

    // --- 4. Insert the new packing record ---
    $stmt = $conn->prepare("
        INSERT INTO packing_log (handle_id, packed_pcs, packing_date, description, order_number, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("issss", $handle_id_int, $packed_pcs_int, $packing_date, $description, $order_number);
    $stmt->execute();
    $stmt->close();

    // --- 5. Update stock in 'items' table ---
    $stmt_stock = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
    $stmt_stock->bind_param("ii", $packed_pcs_int, $handle_id_int);
    $stmt_stock->execute();
    $stmt_stock->close();

    // --- 6. Insert into stock_log ---
    $log_type = 'PACKED';
    $stmt_log = $conn->prepare("
        INSERT INTO stock_log (handle_id, log_type, item_id, quantity, added_by, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt_log->bind_param("isiii", $handle_id_int, $log_type, $handle_id_int, $packed_pcs_int, $added_by);
    $stmt_log->execute();
    $stmt_log->close();

    // --- 7. Auto-update order_items.status if all packed ---
    if ($order_number) {
        $stmt_item = $conn->prepare("SELECT id, quantity_ordered, status FROM order_items WHERE order_number=? AND item_id=?");
        $stmt_item->bind_param("si", $order_number, $handle_id_int);
        $stmt_item->execute();
        $item_data = $stmt_item->get_result()->fetch_assoc();
        $stmt_item->close();

        if ($item_data) {
            $ordered_qty = (int)$item_data['quantity_ordered'];
            $stmt_sum = $conn->prepare("SELECT IFNULL(SUM(packed_pcs),0) AS total_packed FROM packing_log WHERE order_number=? AND handle_id=?");
            $stmt_sum->bind_param("si", $order_number, $handle_id_int);
            $stmt_sum->execute();
            $total_packed_for_order = (int)$stmt_sum->get_result()->fetch_assoc()['total_packed'];
            $stmt_sum->close();

            if ($total_packed_for_order >= $ordered_qty && $item_data['status'] !== 'COMPLETED') {
                $stmt_update = $conn->prepare("UPDATE order_items SET status='COMPLETED' WHERE id=?");
                $stmt_update->bind_param("i", $item_data['id']);
                $stmt_update->execute();
                $stmt_update->close();
            }
        }
    }

    // --- 8. Optionally update orders.current_stage ---
    if ($order_number) {
        $stmt_stage = $conn->prepare("UPDATE orders SET current_stage = 'packed' WHERE order_number = ?");
        $stmt_stage->bind_param("s", $order_number);
        $stmt_stage->execute();
        $stmt_stage->close();
    }

    $conn->commit();
    header("Location: packing_page.php?success=Packing entry saved and order status updated.");

} catch (Exception $e) {
    $conn->rollback();
    error_log("Save Packing Error: " . $e->getMessage());
    header("Location: packing_page.php?error=" . urlencode($e->getMessage()));
}
exit();
?>
