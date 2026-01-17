<?php
session_start();

/* ---------- AUTH CHECK ---------- */
if (!isset($_SESSION['loggedIn']) || !in_array($_SESSION['role'], ['casting', 'admin'])) {
    header("Location: casting_page.php?error=Unauthorized access");
    exit;
}

/* ---------- DB CONNECTION ---------- */
$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);
if ($conn->connect_error) {
    header("Location: casting_page.php?error=Database connection failed");
    exit;
}
$conn->set_charset("utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ---------- GET POST DATA ---------- */
$handle_id          = $_POST['handle_id'] ?? null;
$total_weight_kg    = $_POST['total_weight_kg'] ?? null;
$weight_per_piece_g = $_POST['weight_per_piece_g'] ?? null;
$total_pcs          = $_POST['total_pcs'] ?? null;
$description        = $_POST['description'] ?? null;
$is_outsourced      = isset($_POST['is_outsourced']) ? 1 : 0;
$order_number       = isset($_POST['order_number']) && $_POST['order_number'] !== ''
                        ? trim($_POST['order_number'])
                        : null;

/* ---------- TYPE CASTING ---------- */
$handle_id_int       = (int)$handle_id;
$total_weight_kg     = (float)$total_weight_kg;
$weight_per_piece_g  = (float)$weight_per_piece_g;
$total_pcs_int       = (int)$total_pcs;
$description         = $description ? trim($description) : null;

/* ---------- VALIDATION ---------- */
if (
    !$handle_id_int ||
    $total_weight_kg <= 0 ||
    $weight_per_piece_g <= 0 ||
    $total_pcs_int <= 0
) {
    header("Location: casting_page.php?error=All required fields must be filled with valid values.");
    exit;
}

try {
    /* ---------- HANDLE VALIDATION ---------- */
    $stmt_check = $conn->prepare(
        "SELECT id FROM items WHERE id = ? AND material = 'Malleable'"
    );
    $stmt_check->bind_param("i", $handle_id_int);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows === 0) {
        throw new Exception("Invalid or non-malleable handle selected.");
    }
    $stmt_check->close();

    /* ---------- INSERT RAW CASTING ---------- */
    $stmt = $conn->prepare("
        INSERT INTO raw_casting_log 
            (handle_id, total_weight_kg, weight_per_piece_g, total_pcs, description, is_outsourced, order_number, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "iddisis",
        $handle_id_int,
        $total_weight_kg,
        $weight_per_piece_g,
        $total_pcs_int,
        $description,
        $is_outsourced,
        $order_number
    );
    $stmt->execute();
    $stmt->close();

    /* ---------- AUTO UPDATE ORDER STATUS ---------- */
    if ($order_number) {
        try {
            // Fetch order item
            $stmt2 = $conn->prepare("
                SELECT id, quantity_ordered, status
                FROM order_items
                WHERE order_number = ? AND item_id = ?
            ");
            $stmt2->bind_param("si", $order_number, $handle_id_int);
            $stmt2->execute();
            $order_item = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if ($order_item) {
                // Calculate processed qty
                $stmt3 = $conn->prepare("
                    SELECT COALESCE(SUM(total_pcs), 0) AS processed_qty
                    FROM raw_casting_log
                    WHERE order_number = ? AND handle_id = ?
                ");
                $stmt3->bind_param("si", $order_number, $handle_id_int);
                $stmt3->execute();
                $processed_qty = (float)$stmt3->get_result()->fetch_assoc()['processed_qty'];
                $stmt3->close();

                // Update status
                if (
                    $processed_qty >= (float)$order_item['quantity_ordered'] &&
                    $order_item['status'] !== 'PENDING_TURNING'
                ) {
                    $stmt4 = $conn->prepare("
                        UPDATE order_items
                        SET status = 'PENDING_TURNING'
                        WHERE id = ?
                    ");
                    $stmt4->bind_param("i", $order_item['id']);
                    $stmt4->execute();
                    $stmt4->close();
                }
            }
        } catch (Exception $ex) {
            error_log("Order auto-status update failed: " . $ex->getMessage());
        }
    }

    $conn->close();
    header("Location: casting_page.php?success=Raw casting entry saved successfully.");
    exit;

} catch (Exception $e) {
    error_log("Save Raw Casting Error: " . $e->getMessage());
    header("Location: casting_page.php?error=" . urlencode($e->getMessage()));
    exit;
}
