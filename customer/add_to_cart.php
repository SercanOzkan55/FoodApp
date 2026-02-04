<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit;
}

$user    = (int)$_SESSION["user_id"];
$menu_id = (int)($_GET["id"] ?? 0);

if ($menu_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Aynı ürün varsa adet artır
$check = mysqli_query($conn,
  "SELECT * FROM cart WHERE user_id=$user AND menu_id=$menu_id"
);

if (mysqli_num_rows($check)) {
    mysqli_query($conn,
      "UPDATE cart SET quantity = quantity + 1 
       WHERE user_id=$user AND menu_id=$menu_id");
} else {
    mysqli_query($conn,
      "INSERT INTO cart (user_id, menu_id, quantity)
       VALUES ($user, $menu_id, 1)");
}

// Sepete dön
header("Location: cart.php?toast=added");
exit;

