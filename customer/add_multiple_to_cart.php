<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$user = (int)$_SESSION["user_id"];

if (!isset($_POST["items"]) || !is_array($_POST["items"])) {
    header("Location: dashboard.php");
    exit;
}

foreach ($_POST["items"] as $menu_id => $qty) {
    $menu_id = (int)$menu_id;
    $qty = (int)$qty;

    if ($qty <= 0) continue;

    $check = mysqli_query($conn,"
        SELECT * FROM cart
        WHERE user_id=$user AND menu_id=$menu_id
    ");

    if (mysqli_num_rows($check)) {
        mysqli_query($conn,"
            UPDATE cart
            SET quantity = quantity + $qty
            WHERE user_id=$user AND menu_id=$menu_id
        ");
    } else {
        mysqli_query($conn,"
            INSERT INTO cart (user_id, menu_id, quantity)
            VALUES ($user, $menu_id, $qty)
        ");
    }
}

header("Location: cart.php");
exit;
