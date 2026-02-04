<?php
session_start();
include "../db.php";

// Güvenlik ve Yetki Kontrolü
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id  = (int)$_SESSION["user_id"];
$order_id = (int)($_GET["id"] ?? 0);

if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// 1. Sipariş ve Zaman Damgasını Çek
$order_query = mysqli_query($conn, "
    SELECT o.*, r.restaurant_name, o.on_the_way_at 
    FROM orders o
    JOIN restaurants r ON r.id = o.RestaurantID
    WHERE o.OrderID = $order_id AND o.CustomerID = $user_id
");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    die("Sipariş bulunamadı.");
}

$status = $order["Status"];
$isDelivered = ($status === "delivered");
$progress = 0;

// 2. Başlangıç Zamanını Sabitle (Baştan başlamayı kesin engelleyen kısım)
if ($status === "on_the_way" && empty($order["on_the_way_at"])) {
    $now = date("Y-m-d H:i:s");
    mysqli_query($conn, "UPDATE orders SET on_the_way_at = '$now' WHERE OrderID = $order_id");
    $order["on_the_way_at"] = $now;
}

// 3. İlerleme ve Hız Hesaplama
srand($order_id); 
$totalDuration = rand(45, 80); // Her sipariş için sabit toplam süre

if (($status === "on_the_way" || $isDelivered) && !empty($order["on_the_way_at"])) {
    $startTime = strtotime($order["on_the_way_at"]);
    $currentTime = time();
    $elapsedSeconds = $currentTime - $startTime;

    if ($elapsedSeconds < 0) $elapsedSeconds = 0;
    
    // Geçen saniyeye göre yüzde hesabı
    $progress = ($elapsedSeconds / $totalDuration) * 100;

    if ($progress >= 100) {
        $progress = 100;
        // %100 olunca veritabanını güncelle (Trigger puan eklemesi için)
        if (!$isDelivered) {
            mysqli_query($conn, "UPDATE orders SET Status='delivered' WHERE OrderID = $order_id");
            $isDelivered = true;
            $status = "delivered";
        }
    }
}

include "header.php"; 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Canlı Takip #<?= $order_id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8f9fa; margin:0; font-family:'Poppins', sans-serif; transition: 0.3s; }
        body.dark-mode { background: #121212; color: #f5f5f5; }
        .track-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .track-card { background: white; border-radius: 25px; padding: 35px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); text-align: center; }
        body.dark-mode .track-card { background: #1e1e1e; color: white; }
        
        .map-area { 
            position: relative; height: 160px; background: rgba(108, 92, 231, 0.1); 
            border-radius: 20px; margin: 40px 0; overflow: hidden; display: flex; align-items: center; padding: 0 60px; 
        }
        .road-line { width: 100%; height: 8px; background: rgba(0,0,0,0.05); border-radius: 10px; position: relative; }
        .road-progress { position: absolute; left: 0; top: 0; height: 100%; background: #6c5ce7; border-radius: 10px; transition: width 1s linear; }
        
        /* MOTOR YÖNÜ: scaleX(-1) motorun sağa (eve) bakmasını sağlar */
        .courier-bike { 
            position: absolute; top: -45px; font-size: 40px; 
            transform: scaleX(-1); transition: left 1s linear; z-index: 10; margin-left: -20px; 
        }
        
        .marker { font-size: 32px; position: absolute; z-index: 5; top: -16px; }
        .status-badge { background: #6c5ce7; color: white; padding: 10px 20px; border-radius: 15px; font-weight: 700; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php if(function_exists('render_customer_header')) render_customer_header(); ?>

<div class="track-container">
    <div class="track-card">
        <div class="status-badge" id="badgeText"><?= $isDelivered ? 'TESLİM EDİLDİ' : 'KURYEDE' ?></div>
        <h2><?= $order['restaurant_name'] ?> - Canlı Takip</h2>

        <?php if ($status === 'pending' || $status === 'preparing'): ?>
            <p style="padding: 40px;">Siparişiniz hazırlanıyor, kurye yola çıktığında burada göreceksiniz.</p>
        <?php else: ?>
            <div class="map-area">
                <div class="road-line">
                    <div class="road-progress" id="roadProgress" style="width: <?= $progress ?>%;"></div>
                    <i class="fa-solid fa-shop marker" style="left:-10px; color:#ff4b4b;"></i>
                    <i class="fa-solid fa-house-chimney marker" style="right:-10px; color:#2ecc71;"></i>
                    <div class="courier-bike" id="courier" style="left: <?= $progress ?>%;">🛵</div>
                </div>
            </div>

            <h3 id="progressText">Kurye Yaklaşıyor: %<?= floor($progress) ?></h3>
            <div id="liveCoords" style="font-size:14px; color:#888; margin-bottom:20px;"></div>
            
            <script>
            document.addEventListener("DOMContentLoaded", function () {
                const roadProgress = document.getElementById("roadProgress");
                const courier = document.getElementById("courier");
                const progressText = document.getElementById("progressText");
                const liveCoords = document.getElementById("liveCoords");

                let p = parseFloat("<?= $progress ?>"); 
                const totalDur = parseInt("<?= $totalDuration ?>");
                const isDelivered = <?= $isDelivered ? 'true' : 'false' ?>;

                function updateUI(val) {
                    const safe = Math.min(Math.max(val, 0), 100);
                    roadProgress.style.width = safe + "%";
                    courier.style.left = safe + "%";
                    
                    if (safe >= 100) {
                        progressText.innerHTML = "Sipariş Teslim Edildi! 😋";
                        // 100 olunca Siparişlerim sayfasına yönlendir
                        setTimeout(() => { window.location.href = 'orders.php'; }, 2000);
                    } else {
                        progressText.innerText = "Kurye Yaklaşıyor: %" + Math.floor(safe);
                        const lat = (41.0082 + (safe * 0.0001)).toFixed(4);
                        const lng = (28.9784 + (safe * 0.0001)).toFixed(4);
                        liveCoords.innerHTML = `<i class="fa-solid fa-location-dot"></i> Konum: ${lat} , ${lng}`;
                    }
                }

                updateUI(p);

                if (!isDelivered && p < 100) {
                    const step = 100 / totalDur;
                    const timer = setInterval(() => {
                        p += step;
                        if (p >= 100) {
                            p = 100;
                            updateUI(100);
                            clearInterval(timer);
                        } else {
                            updateUI(p);
                        }
                    }, 1000);
                }
            });
            </script>
        <?php endif; ?>

        <a href="orders.php" style="display:block; margin-top:20px; color:#6c5ce7; font-weight:600; text-decoration:none;">← Siparişlerime Dön</a>
    </div>
</div>

</body>
</html>