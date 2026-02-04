<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST["fullname"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // ROLE — KÜÇÜK HARFE ÇEVİR (EN ÖNEMLİ DÜZELTME)
    $role = strtolower($_POST["role"]);   // "Customer" -> "customer"

    // Şifre HASHLE
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Email var mı kontrol
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {

        echo "
        <div style='
            font-family: Arial;
            width: 400px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 10px;
            background: #ffe0e0;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        '>
            <h2 style='color: red;'>Bu email zaten kayıtlı!</h2>
            <p>3 saniye içinde tekrar kayıt ekranına yönlendiriliyorsunuz...</p>
        </div>

        <script>
            setTimeout(function() {
                window.location.href = 'register.php';
            }, 3000);
        </script>
        ";
        exit;
    }

    // Kullanıcıyı ekle
    mysqli_query($conn, "INSERT INTO users (fullname, email, password, role)
        VALUES ('$fullname', '$email', '$hashed', '$role')");

    // BAŞARI MESAJI
    echo "
    <div style='
        font-family: Arial;
        width: 400px;
        margin: 100px auto;
        padding: 20px;
        border-radius: 10px;
        background: #d4ffd4;
        text-align: center;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    '>
        <h2 style='color: green;'>Kayıt Başarılı!</h2>
        <p>3 saniye içinde giriş ekranına yönlendirileceksiniz...</p>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
    ";
}
?>
