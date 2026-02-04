<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    http_response_code(403);
    exit;
}

$user_id  = (int)$_SESSION["user_id"];
$order_id = (int)($_POST["id"] ?? 0);
$status   = $_POST["status"] ?? "";

$allowed = ["pending", "preparing", "on_the_way", "delivered"];

if (!$order_id || !in_array($status, $allowed, true)) {
    http_response_code(400);
    exit;
}

// Bu sipariş gerçekten bu müşteriye mi ait?
$q = mysqli_query($conn, "
    SELECT OrderID FROM orders 
    WHERE OrderID = $order_id AND CustomerID = $user_id
    LIMIT 1
");
if (!$q || mysqli_num_rows($q) == 0) {
    http_response_code(403);
    exit;
}

$status = mysqli_real_escape_string($conn, $status);
mysqli_query($conn, "
    UPDATE orders 
    SET Status = '$status'
    WHERE OrderID = $order_id
");

echo "ok";
