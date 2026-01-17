<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$host = "sql100.infinityfree.com";   // your host
$user = "if0_39812412";              // your db user
$pass = "Bpiapp0101";                // your db pass
$db   = "if0_39812412_bpi";          // your db name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $metalin_weight = $_POST['metalin_weight'];
    $casting_date   = $_POST['casting_date'];
    $total_weight   = $_POST['total_weight'];
    $total_pcs      = $_POST['total_pcs'];
    $metalout_weight = $_POST['metalout_weight'];
    $melo           = $_POST['melo'];

    $stmt = $conn->prepare("INSERT INTO casting_log (casting_date, metalin_weight, total_weight, total_pcs, metalout_weight, melo) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddids", $casting_date, $metalin_weight, $total_weight, $total_pcs, $metalout_weight, $melo);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Casting entry saved successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
