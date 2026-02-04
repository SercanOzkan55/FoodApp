<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Kullanıcı giriş mantığı
    $email    = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");

    if (mysqli_num_rows($query) === 0) {
        $error = "❌ Email veya şifre hatalı";
    } else {
        $user = mysqli_fetch_assoc($query);

        if (!password_verify($password, $user["password"])) {
            $error = "❌ Email veya şifre hatalı";
        } else {
            // ✅ GİRİŞ BAŞARILI
            $_SESSION["user_id"]  = $user["id"];
            $_SESSION["fullname"] = $user["fullname"];
            $_SESSION["role"]     = strtolower($user["role"]);

            if ($_SESSION["role"] === "admin") {
                header("Location: admin/dashboard.php");
                exit;
            }
            if ($_SESSION["role"] === "seller") {
                header("Location: seller/dashboard.php");
                exit;
            }
            if ($_SESSION["role"] === "customer") {
                header("Location: customer/dashboard.php");
                exit;
            }

            $error = "❌ Rol bulunamadı";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>FoodApp - Giriş Yap</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
/* GENEL */
* {
    box-sizing: border-box;
}
body {
    font-family: 'Poppins', sans-serif;
    /* Daha canlı ve derin degrade */
    background: linear-gradient(135deg, #ff9a8d, #ff4e41); 
    height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* KART KUTUSU (Login Box) */
.login-box {
    background: #fff;
    width: 420px;
    padding: 45px;
    border-radius: 25px; /* Daha yumuşak köşeler */
    text-align: center;
    box-shadow: 0 18px 45px rgba(0,0,0,.3); /* Daha belirgin gölge */
    animation: slideIn 0.6s ease-out;
}

/* LOGO VE BAŞLIK */
.logo-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 25px;
}
.logo-container i {
    font-size: 55px;
    color: #ff4757;
    margin-bottom: 5px;
}
.logo-container h2 {
    margin: 5px 0 0;
    color: #333;
    font-weight: 700;
    font-size: 30px;
}
.tagline {
    font-size: 14px;
    color: #888;
    margin-top: 5px;
}

/* FORM GRUBU */
.form-group {
    position: relative;
    margin-bottom: 20px;
}

.form-group input {
    width: 100%;
    padding: 16px 50px 16px 18px; /* Soldan ve sağdan boşluk artırıldı */
    border-radius: 15px; /* Daha yumuşak input köşeleri */
    border: 1px solid #ddd;
    font-size: 16px;
    transition: all 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #ff6b6b; /* Odak rengi değiştirildi */
    box-shadow: 0 0 15px rgba(255,107,107,0.4);
}

/* Şifre Gösterme İkonu */
.toggle-pass {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #aaa;
    font-size: 20px;
    transition: color 0.2s;
}
.toggle-pass:hover {
    color: #ff4757;
}

/* Hata Kutusu */
.error-box {
    background: #ffebeb;
    color: #cc0000;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    border: 1px solid #ffcccc;
}

/* BUTON */
button {
    width: 100%;
    padding: 16px;
    /* Butona degrade efekti */
    background: linear-gradient(90deg, #ff6b6b, #ff4757); 
    color: white;
    border: none;
    border-radius: 15px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(255,71,87,0.3);
}

button:hover {
    background: linear-gradient(90deg, #ff4757, #ff6b6b);
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(255,71,87,0.4);
}

/* Animasyon */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>

<body>

<div class="login-box">
    <div class="logo-container">
        <i class="fa-solid fa-utensils"></i>
        <h2>Giriş Yap</h2>
        <span class="tagline">Lezzete Açılan Kapı</span>
    </div>

    <?php if ($error): ?>
        <div class="error-box"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <input type="email" name="email" placeholder="Email Adresi" required>
        </div>

        <div class="form-group">
            <input type="password" name="password" id="password" placeholder="Şifreniz" required>
            <i class="fa-solid fa-eye-slash toggle-pass" id="toggleIcon" onclick="togglePassword()"></i>
        </div>

        <button>GİRİŞ YAP</button>
    </form>

</div>

<script>
function togglePassword() {
    const pass = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");
    
    if (pass.type === "password") {
        pass.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    } else {
        pass.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    }
}
</script>

</body>
</html>