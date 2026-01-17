<?php
session_start();
// --- Auth Check ---
if (!isset($_SESSION['loggedIn']) || !in_array($_SESSION['role'], ['casting', 'admin'])) {
    header("Location: casting_page.php?error=Unauthorized access");
    exit;
}

// --- Database Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header("Location: casting_page.php?error=Database connection failed");
    exit;
}
// $conn->query("SET time_zone = '+05:30'");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Get POST data ---
$casting_date   = $_POST['casting_date'] ?? '';
$metalin_weight = $_POST['metalin_weight'] ?? '';
$total_pcs      = $_POST['total_pcs'] ?? '';
$melo           = $_POST['melo'] ?? '';
$description    = $_POST['description'] ?? null;
$order_number   = isset($_POST['order_number']) && $_POST['order_number'] !== '' ? trim($_POST['order_number']) : null;

// --- Trim & Validate ---
$casting_date   = trim($casting_date);
$metalin_weight = trim($metalin_weight);
$total_pcs      = trim($total_pcs);
$melo           = trim($melo);
$description    = $description ? trim($description) : null;

if (empty($casting_date) || empty($metalin_weight) || empty($total_pcs) || empty($melo)) {
    header("Location: casting_page.php?error=Date, Metal In, Pcs, and Melo fields are required.");
    exit;
}
$total_pcs_int = (int)$total_pcs;
if ($total_pcs_int <= 0) {
     header("Location: casting_page.php?error=Total Pcs must be greater than 0.");
     exit;
}
$metalin_weight_float = (float)$metalin_weight;

// --- Insert into casting_log ---
try {
    // Removed 'total_weight', added 'order_number'
    $stmt = $conn->prepare("
        INSERT INTO casting_log (casting_date, metalin_weight, total_pcs, melo, description, order_number, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    // Bind types: s=string, d=double, i=integer, s=string, s=string, s=string
    $stmt->bind_param("sdssss", $casting_date, $metalin_weight_float, $total_pcs_int, $melo, $description, $order_number);

    if ($stmt->execute()) {
        // Success -> Redirect back
        header("Location: casting_page.php?success=Casting entry saved successfully.");
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Save Casting Error: " . $e->getMessage()); // Log error
    header("Location: casting_page.php?error=Database error occurred."); // Generic error for user
}
exit();
?>