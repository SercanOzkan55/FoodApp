<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// SEPET
$cart = mysqli_query($conn, "
    SELECT cart.*, menus.price, menus.id AS MenuID, menus.restaurant_id
    FROM cart
    JOIN menus ON menus.id = cart.menu_id
    WHERE cart.user_id = $user_id
");

if (mysqli_num_rows($cart) == 0) {
    die("Sepetiniz boş!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $address = trim($_POST["address"] ?? "");
    if ($address == "") {
        die("Adres boş olamaz!");
    }

    // TOPLAM + RESTORAN
    $total = 0;
    $restaurant_id = 0;

    mysqli_data_seek($cart, 0);
    while ($c = mysqli_fetch_assoc($cart)) {
        $total += $c["price"] * $c["quantity"];
        $restaurant_id = $c["restaurant_id"];
    }

    // Kullanıcının mevcut puanı
    $points = 0;
    $pq = mysqli_query($conn,"SELECT points FROM user_points WHERE user_id=$user_id");
    if ($pq && mysqli_num_rows($pq)) {
        $points = (int)mysqli_fetch_assoc($pq)["points"];
    }

    // PUAN KULLANIMI (20 puan = 1 TL)
    $max_discount = floor($points / 20);   // max indirim TL
    $use_points   = isset($_POST["use_points"]) ? 1 : 0;

    $used_points  = 0;
    $discount_tl  = 0;
    $pay_amount   = $total;

    if ($use_points && $max_discount > 0) {
        // Toplamdan daha fazla indirim olmasın
        $discount_tl = min($max_discount, $total);
        $used_points = $discount_tl * 20; // 1 TL için 20 puan

        if ($used_points > $points) {
            $used_points = $points; // Güvenlik
        }

        $pay_amount = $total - $discount_tl;
    }

    // SİPARİŞ
    mysqli_query($conn, "
        INSERT INTO orders (CustomerID, RestaurantID, Total, Address, Status, used_points)
        VALUES ($user_id, $restaurant_id, $pay_amount, '$address', 'pending', $used_points)
    ");

    $order_id = mysqli_insert_id($conn);

    // CUSTOMER NAME
    $customer_name = mysqli_real_escape_string($conn, $_SESSION["fullname"]);

    // ORDER ITEMS
    mysqli_data_seek($cart, 0);
    while ($c = mysqli_fetch_assoc($cart)) {
        mysqli_query($conn, "
            INSERT INTO order_items (OrderID, MenuID, Quantity, Price, CustomerName)
            VALUES ($order_id, {$c["MenuID"]}, {$c["quantity"]}, {$c["price"]}, '$customer_name')
        ");
    }

    // PAYMENT (ödenen tutar üzerinden)
    mysqli_query($conn, "
        INSERT INTO payments (OrderID, Amount)
        VALUES ($order_id, $pay_amount)
    ");

    // ===== PUAN GÜNCELLE =====
    // 1 TL = 2.5 puan (ödenen tutar üzerinden kazanç)
    $earned_points = floor($pay_amount * 2.5);

    if ($points > 0 || $earned_points > 0) {
        mysqli_query($conn, "
            INSERT INTO user_points (user_id, points)
            VALUES ($user_id, " . max(0, $earned_points - $used_points) . ")
            ON DUPLICATE KEY UPDATE 
                points = GREATEST(0, points - $used_points + $earned_points)
        ");
    }

    // SEPET TEMİZLE
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

    // Siparişler sayfasına yönlendir
    header("Location: orders.php?new=" . $order_id);
    exit;
}

// POST dışı erişim
header("Location: cart.php");
exit;
