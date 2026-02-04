<?php
include "db.php";

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $role = strtolower($_POST["role"]); // Customer → customer

    // Şifre hash
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Email kontrolü
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $errorMessage = "Bu email zaten kayıtlı!";
    } else {
        mysqli_query($conn, "INSERT INTO users (fullname, email, password, role)
            VALUES ('$fullname', '$email', '$hashed', '$role')");

        $successMessage = "Kayıt başarıyla tamamlandı! 3 saniye içinde giriş ekranına yönlendiriliyorsunuz.";

        // 3 saniye sonra yönlendirme
        echo "<script>
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 3000);
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI';
            background: linear-gradient(135deg, #74ebd5, #acb6e5);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .form-box {
            background: white;
            padding: 40px;
            width: 430px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 10px 35px rgba(0,0,0,0.2);
        }

        .form-box i {
            font-size: 55px;
            color: #0984e3;
            margin-bottom: 10px;
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .success {
            background: #d4ffd4;
            color: green;
            border: 1px solid #8fd98f;
        }

        .error {
            background: #ffd4d4;
            color: #d9534f;
            border: 1px solid #e99e9e;
        }

        .role-select {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 15px 0 20px 0;
        }

        .role-select {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 10px 0 15px 0;
        }

        .role-option {
            width: 100px;
            height: 55px;
            border: 2px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: 0.25s;
            user-select: none;
            padding: 5px 0;
        }

        .role-option i {
            font-size: 20px;
            margin-bottom: 3px;
        }

        .role-option.active {
            border-color: #0984e3;
            background: #e8f4ff;
        }

        .input-area {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 90%;
            margin: 0 auto;
        }

        input {
            padding: 14px;
            border-radius: 10px;
            border: 2px solid #ddd;
            font-size: 16px;
        }

        button {
            width: 90%;
            padding: 14px;
            margin-top: 20px;
            background: #0984e3;
            color: white;
            font-size: 17px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: .3s;
        }

        button:hover {
            background: #0652dd;
        }
    </style>

    <script>
        function selectRole(role) {
            document.getElementById("role").value = role.toLowerCase();

            document.getElementById("customer").classList.remove("active");
            document.getElementById("seller").classList.remove("active");

            if (role === "Customer") {
                document.getElementById("customer").classList.add("active");
            } else {
                document.getElementById("seller").classList.add("active");
            }
        }
    </script>

</head>

<body>

<div class="form-box">

    <?php if ($successMessage): ?>
        <div class="message success"><?= $successMessage ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="message error"><?= $errorMessage ?></div>
    <?php endif; ?>

    <i class="fa-solid fa-user-plus"></i>
    <h2>Kayıt Ol</h2>

    <form method="POST">

        <div class="input-area">
            <input type="text" name="name" placeholder="Ad Soyad" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Şifre" required>
        </div>

        <div class="role-select">
            <div id="customer" class="role-option active" onclick="selectRole('Customer')">
                <i class="fa-solid fa-user"></i>
                Müşteri
            </div>

            <div id="seller" class="role-option" onclick="selectRole('Seller')">
                <i class="fa-solid fa-store"></i>
                Satıcı
            </div>
        </div>

        <input type="hidden" id="role" name="role" value="customer">

        <button>Kayıt Ol</button>
    </form>
</div>

</body>
</html>
