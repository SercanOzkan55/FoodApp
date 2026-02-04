<?php
session_start();
include "../db.php";
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Oturum açılmamış."]);
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$rest_id = (int)($_POST["restaurant_id"] ?? 0);
$action = $_POST["action"] ?? '';

if ($rest_id <= 0 || ($action !== 'add' && $action !== 'remove')) {
    echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
    exit;
}

// SQL Injection'dan korunmak için mysqli_real_escape_string kullanılması önerilir, 
// ancak burada int cast yapıldığı için temel güvenlik sağlanmıştır.

if ($action === 'add') {
    // IGNORE ile zaten varsa hata vermesi engellenir
    $query = "INSERT IGNORE INTO favorites (user_id, restaurant_id) VALUES ($user_id, $rest_id)";
} else {
    $query = "DELETE FROM favorites WHERE user_id = $user_id AND restaurant_id = $rest_id";
}

if (mysqli_query($conn, $query)) {
    echo json_encode(["success" => true, "action" => $action]);
} else {
    // Hata durumunda DEBUG amaçlı hata mesajı gösterilebilir, production için kapatılmalıdır.
    // echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . mysqli_error($conn)]);
    echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
?>