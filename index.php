<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>FoodApp - Lezzete Açılan Kapı</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff7e5f, #feb47b); /* Daha sıcak ve modern bir degrade */
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden; /* Taşmaları engelle */
            position: relative;
        }

        /* Arka Plan Baloncukları - Estetik Dokunuş */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            filter: blur(80px); /* Daha yumuşak bulanıklık */
            z-index: 0;
        }
        body::before {
            top: -50px;
            left: -50px;
            animation: bubbleMove1 15s infinite ease-in-out alternate;
        }
        body::after {
            bottom: -50px;
            right: -50px;
            animation: bubbleMove2 15s infinite ease-in-out alternate;
        }

        .card {
            background: rgba(255, 255, 255, 0.9); /* Hafif şeffaf arka plan */
            backdrop-filter: blur(10px); /* Arka planı bulanıklaştır */
            padding: 40px 50px;
            border-radius: 25px; /* Daha yumuşak köşeler */
            box-shadow: 0 15px 50px rgba(0,0,0,0.25); /* Daha belirgin ve yayvan gölge */
            text-align: center;
            width: 380px;
            animation: fadeInScale 0.8s ease-out;
            z-index: 1; /* Diğer elemanların üstünde olması için */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Hafif bir çerçeve */
        }

        /* FoodApp Logo Stili */
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            color: #ff4757; /* Ana renk */
            font-size: 48px; /* Daha büyük logo */
            font-weight: 700;
            letter-spacing: 1px;
        }
        .logo i {
            font-size: 55px; /* Kase ikonunun boyutu */
            margin-right: 12px;
            transform: rotate(-10deg); /* Hafif eğim */
        }
        .tagline {
            font-size: 16px;
            color: #666;
            margin-top: -15px; /* Logo ile arayı kapat */
            margin-bottom: 30px;
            font-weight: 400;
        }

        /* Butonlar */
        .btn {
            width: 100%;
            padding: 18px 20px; /* Daha geniş padding */
            margin: 15px 0;
            border: none;
            border-radius: 15px; /* Daha yumuşak buton köşeleri */
            font-size: 19px; /* Daha büyük yazı */
            color: white;
            font-weight: 600; /* Daha kalın yazı */
            cursor: pointer;
            transition: all 0.3s ease; /* Tüm geçişlere yumuşaklık */
            display: flex; /* İkon ve metni aynı hizada tutmak için */
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15); /* Butonlara gölge */
        }

        .btn i {
            font-size: 24px; /* Buton ikonlarının boyutu */
            margin-right: 15px;
            opacity: 0.9;
        }
        
        .btn .right-icon { /* Sağdaki ikon için ayrı stil */
            position: absolute;
            right: 20px;
            margin-right: 0; /* Normal margin-right'ı sıfırla */
        }

        /* Giriş Yap Butonu */
        .login-btn {
            background: linear-gradient(90deg, #ff6b6b, #ee5253); /* Kırmızımsı-turuncumsu degrade */
        }
        .login-btn:hover {
            background: linear-gradient(90deg, #ee5253, #ff6b6b);
            box-shadow: 0 12px 25px rgba(232, 65, 24, 0.3);
            transform: translateY(-3px); /* Hafif yukarı kaydırma efekti */
        }

        /* Kayıt Ol Butonu */
        .register-btn {
            background: linear-gradient(90deg, #48dbfb, #0abde3); /* Mavi-turkuaz degrade */
        }
        .register-btn:hover {
            background: linear-gradient(90deg, #0abde3, #48dbfb);
            box-shadow: 0 12px 25px rgba(12, 101, 192, 0.3);
            transform: translateY(-3px);
        }

        /* Animasyonlar */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes bubbleMove1 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(100px, 100px) scale(1.1); }
            100% { transform: translate(0, 0) scale(1); }
        }
        @keyframes bubbleMove2 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-100px, -100px) scale(0.9); }
            100% { transform: translate(0, 0) scale(1); }
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">
            <i class="fa-solid fa-utensils"></i> FoodApp
        </div>
        <div class="tagline">Lezzete Açılan Kapı</div>

        <button class="btn login-btn" onclick="window.location='login.php'">
            <i class="fa-solid fa-right-to-bracket"></i>
            Giriş Yap
            <i class="fa-solid fa-lock right-icon"></i> </button>

        <button class="btn register-btn" onclick="window.location='register.php'">
            <i class="fa-solid fa-user-plus"></i>
            Kayıt Ol
            <i class="fa-solid fa-user-lock right-icon"></i> </button>
    </div>
</body>
</html>