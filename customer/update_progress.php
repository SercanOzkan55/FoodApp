<?php
session_start();
include "../db.php";

// Sadece oturum açmış ve müşteri olan kullanıcılar erişebilir
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Yetkisiz Erişim"]);
    exit;
}

// POST verilerini al
$order_id = (int)($_POST["id"] ?? 0);
$progress = (int)($_POST["progress"] ?? 0);
$user_id  = (int)$_SESSION["user_id"];

if ($order_id <= 0 || $progress < 0 || $progress > 100) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
    exit;
}

// Güvenli sorgu (sadece müşterinin kendi siparişini güncellemesini sağlar)
$query = "UPDATE orders SET DeliveryProgress = $progress WHERE OrderID = $order_id AND CustomerID = $user_id AND Status = 'on_the_way'";

if (mysqli_query($conn, $query)) {
    echo json_encode(["success" => true, "progress" => $progress]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . mysqli_error($conn)]);
}

// Simülasyon olduğu için, kurye %100'e ulaştığında
// update_status.php'yi çağırmayı JavaScript'e bıraktık.
?>