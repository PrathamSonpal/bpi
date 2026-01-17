<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// If session not set (user not logged in)
if (!isset($_SESSION['loggedIn']) || !$_SESSION['loggedIn']) {
    echo json_encode([
        "success" => false,
        "message" => "User not logged in",
        "role" => null
    ]);
    exit;
}

// Return session info
echo json_encode([
    "success" => true,
    "user_id" => $_SESSION['user_id'] ?? null,
    "username" => $_SESSION['username'] ?? null,
    "role" => $_SESSION['role'] ?? "unknown"
]);
?>
