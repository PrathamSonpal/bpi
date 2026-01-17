<?php
// Add debugging to see errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive'); 

// --- DB Credentials (same as your other files) ---
$host = "sql100.infinityfree.com";
$user = "if0_39812412";
$pass = "Bpiapp0101";
$db   = "if0_39812412_bpi_stock";

// --- Function to connect (to avoid repeating code) ---
function connectToDB($host, $user, $pass, $db) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        // Log error but don't output directly, as it breaks the event stream
        error_log("DB Connection Error: " . $e->getMessage());
        return null;
    }
}

$conn = connectToDB($host, $user, $pass, $db); // Initial connection

if (!$conn) {
    echo "data: " . json_encode(['error' => 'Failed to connect to DB.']) . "\n\n";
    ob_flush();
    flush();
    exit;
}

$lastUpdate = date('Y-m-d H:i:s');

while (true) {
    // Try to reset the server's 30-second time limit
    set_time_limit(30);

    // Check if the client is still connected
    if (connection_aborted()) {
        break; // Stop the loop if the user closes the page
    }

    try {
        // Check if the DB connection is still alive
        if (!$conn->ping()) {
            $conn->close();
            $conn = connectToDB($host, $user, $pass, $db); // Reconnect
            if (!$conn) {
               // If reconnect fails, wait and try again
               sleep(5);
               continue; 
            }
        }
        
        // Run the query (on the 'updated_at' column)
        $result = $conn->query("SELECT MAX(updated_at) as last_update FROM orders");
        $row = $result->fetch_assoc();
        $newUpdate = $row['last_update'] ?? $lastUpdate;

        // Only send an update if the timestamp has actually changed
        if ($newUpdate > $lastUpdate) {
            $lastUpdate = $newUpdate;
            echo "data: " . json_encode(['last_update' => $lastUpdate]) . "\n\n";
            ob_flush();
            flush();
        }

    } catch (Exception $e) {
        // Send an error to the browser's console
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        ob_flush();
        flush();
        sleep(5); // Wait before retrying after an error
    }

    // Wait 5 seconds before checking again
    sleep(60);
}

if ($conn) {
    $conn->close();
}
?>