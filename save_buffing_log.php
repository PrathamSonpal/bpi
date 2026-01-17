<?php
session_start();
// --- Auth Check ---
if (!isset($_SESSION['loggedIn'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'];
if ($role !== 'buff' && $role !== 'admin') {
    header("Location: buff_page.php?error=Unauthorized access");
    exit;
}

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header("Location: buff_page.php?error=Database connection failed");
    exit;
}
// $conn->query("SET time_zone = '+05:30'");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Get POST data ---
$handle_id    = $_POST['handle_id'] ?? null;
$buffed_pcs   = $_POST['buffed_pcs'] ?? null;
$buffing_date = $_POST['buffing_date'] ?? null;
$description  = $_POST['description'] ?? null;
$order_number = isset($_POST['order_number']) && $_POST['order_number'] !== '' ? trim($_POST['order_number']) : null;

// --- Validation ---
if (empty($handle_id) || empty($buffed_pcs) || empty($buffing_date)) {
    header("Location: buff_page.php?error=Handle, Pieces, and Date are required.");
    exit;
}
$buffed_pcs_int = (int)$buffed_pcs;
if ($buffed_pcs_int <= 0) {
     header("Location: buff_page.php?error=Pieces must be greater than 0.");
     exit;
}
$handle_id_int = (int)$handle_id;
$description = $description ? trim($description) : null;

// --- Begin Transaction & Stock Check ---
try {
    $conn->begin_transaction();

    // 1. Get Opening Stock (Total from Turning Log)
    $opening_stock = 0;
    $stmt_turned = $conn->prepare("SELECT SUM(ready_pcs) AS total_turned FROM turning_log WHERE handle_id = ?");
    if (!$stmt_turned) throw new Exception("DB Error (Turned Check)");
    $stmt_turned->bind_param("i", $handle_id_int);
    $stmt_turned->execute();
    $result_turned = $stmt_turned->get_result();
    if ($result_turned) {
        $opening_stock = (int)($result_turned->fetch_assoc()['total_turned'] ?? 0);
    }
    $stmt_turned->close();

    // 2. Get Total Buffed So Far
    $total_buffed = 0;
    $stmt_buffed = $conn->prepare("SELECT SUM(buffed_pcs) AS total_buffed FROM buffing_log WHERE handle_id = ?");
    if (!$stmt_buffed) throw new Exception("DB Error (Buffed Check)");
    $stmt_buffed->bind_param("i", $handle_id_int);
    $stmt_buffed->execute();
    $result_buffed = $stmt_buffed->get_result();
    if ($result_buffed) {
        $total_buffed = (int)($result_buffed->fetch_assoc()['total_buffed'] ?? 0);
    }
    $stmt_buffed->close();

    // 3. Calculate balance (Available to buff)
    $balance = $opening_stock - $total_buffed;

    // 4. Check if new entry exceeds balance
    if ($buffed_pcs_int > $balance) {
        throw new Exception("Error: Cannot add " . $buffed_pcs_int . " pieces. Only " . $balance . " turned pieces are in stock.");
    }
    // --- End Stock Check ---

    // 5. Insert the new record
    $stmt = $conn->prepare("
        INSERT INTO buffing_log (handle_id, buffed_pcs, buffing_date, description, order_number, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) throw new Exception("Prepare insert failed: " . $conn->error);

    // Bind parameters (i, i, s, s, s)
    $stmt->bind_param("issss", $handle_id_int, $buffed_pcs_int, $buffing_date, $description, $order_number);

    if ($stmt->execute()) {

        if ($order_number) {
            try {
                // 1️⃣ Get the corresponding order_item
                $stmt2 = $conn->prepare("SELECT id, quantity_ordered, status FROM order_items WHERE order_number=? AND item_id=?");
                $stmt2->bind_param("si", $order_number, $handle_id_int);
                $stmt2->execute();
                $item_data = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();

                if ($item_data) {
                    $ordered_qty = (int)$item_data['quantity_ordered'];

                    // 2️⃣ Sum all buffed quantities for this handle/order
                    $stmt3 = $conn->prepare("SELECT IFNULL(SUM(buffed_pcs),0) AS total_buffed FROM buffing_log WHERE order_number=? AND handle_id=?");
                    $stmt3->bind_param("si", $order_number, $handle_id_int);
                    $stmt3->execute();
                    $total_buffed = (int)$stmt3->get_result()->fetch_assoc()['total_buffed'];
                    $stmt3->close();

                    // 3️⃣ Update status automatically
                    if ($total_buffed >= $ordered_qty && $item_data['status'] !== 'COMPLETED') {
                        $stmt4 = $conn->prepare("UPDATE order_items SET status='PENDING_PACKING' WHERE id=?");
                        $stmt4->bind_param("i", $item_data['id']);
                        $stmt4->execute();
                        $stmt4->close();
                    }
                }
            } catch (Exception $ex) {
                error_log("Auto-update buffing status failed: " . $ex->getMessage());
            }
        }

        $conn->commit(); // Commit transaction
        header("Location: buff_page.php?success=Buffing entry saved successfully.");
    } else {
        throw new Exception("Execute insert failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    error_log("Save Buffing Error: " . $e->getMessage());
    header("Location: buff_page.php?error=" . urlencode($e->getMessage()));
}
exit();
?>