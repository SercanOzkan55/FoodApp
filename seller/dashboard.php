<?php
session_start();

// Sadece satıcılar erişebilsin
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "seller") {
    header("Location: ../login.php");
    exit;
}

include "../db.php";

$seller_id = (int)$_SESSION["user_id"];

// Bu satıcının restoranı var mı?
$check = mysqli_query($conn, "SELECT id FROM restaurants WHERE seller_id = '$seller_id' LIMIT 1");

if (mysqli_num_rows($check) == 0) {
    // Restoran yok → önce restoran oluştur
    header("Location: restaurant_create.php");
    exit;
} else {
    // Restoran var → direkt menü / sipariş ekranına
    header("Location: menu_add.php");
    exit;
}
