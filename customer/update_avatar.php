<?php
session_start();
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $user_id = (int)$_SESSION['user_id'];
    $file = $_FILES['profile_pic'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allowed)) {
        // Benzersiz isim oluştur
        $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
        $target_dir = "uploads/avatars/";

        // Klasör yoksa oluştur
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
            // VERİTABANI GÜNCELLEME (profile_image sütununu kullanıyoruz)
            mysqli_query($conn, "UPDATE users SET profile_image='$new_name' WHERE id=$user_id");
            header("Location: dashboard.php?msg=success");
            exit;
        }
    }
}
header("Location: dashboard.php?msg=error");
exit;