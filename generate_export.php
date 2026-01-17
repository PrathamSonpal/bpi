<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// --- DB Connection ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);

$exports = $_POST['export'] ?? [];
if (empty($exports)) {
    die("<h3 style='font-family:sans-serif;color:#333;text-align:center;margin-top:50px;'>No data selected. <a href='export_excel.php'>Go Back</a></h3>");
}

$tempDir = "temp_exports/";
if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0777, true)) die("Cannot create temp_exports folder.");
}
if (!is_writable($tempDir)) die("Folder not writable.");

// Quick file test
file_put_contents($tempDir . "test.txt", "Test write OK " . date('H:i:s'));

$generatedFiles = [];

function exportQueryToExcel($conn, $query, $filename, $tempDir) {
    $res = $conn->query($query);
    if (!$res || $res->num_rows === 0) return null;
    $filePath = $tempDir . $filename . ".xls";
    $f = fopen($filePath, "w");

    $headers = array_keys($res->fetch_assoc());
    fputcsv($f, $headers, "\t");
    $res->data_seek(0);

    while ($row = $res->fetch_assoc()) {
        fputcsv($f, array_values($row), "\t");
    }
    fclose($f);
    return $filePath;
}

// === Export Logic ===
foreach ($exports as $type) {
    switch ($type) {
        case 'items':
            $q = "SELECT id, name, size, material FROM items ORDER BY id ASC";
            $generatedFiles[] = exportQueryToExcel($conn, $q, "Items_Report", $tempDir);
            break;
        case 'casting_history':
            $q = "SELECT * FROM casting_log ORDER BY timestamp DESC LIMIT 500";
            $generatedFiles[] = exportQueryToExcel($conn, $q, "Casting_History", $tempDir);
            break;
        // ... add your others here ...
    }
}

$generatedFiles = array_filter($generatedFiles);
if (empty($generatedFiles)) die("No data exported.");

// === Output ===
if (count($generatedFiles) === 1) {
    $file = $generatedFiles[0];
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . basename($file));
    readfile($file);
    unlink($file);
} else {
    if (!class_exists('ZipArchive')) die("ZIP extension missing on this server.");
    $zipFile = $tempDir . "BPI_Exports_" . date('Y-m-d_His') . ".zip";
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        foreach ($generatedFiles as $f) $zip->addFile($f, basename($f));
        $zip->close();
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=" . basename($zipFile));
        readfile($zipFile);
    } else {
        die("Failed to create zip file.");
    }
    foreach ($generatedFiles as $f) unlink($f);
    unlink($zipFile);
}
$conn->close();
exit;
?>
