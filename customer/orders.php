<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* ==========================================================
   OTOMATİK TESLİMAT KONTROLÜ (REAL-TIME ÇÖZÜMÜ)
   Kullanıcı bu sayfayı her açtığında/yenilediğinde çalışır.
   Süresi (60 saniye) dolan kuryeleri otomatik teslim eder.
   ========================================================== */
$auto_query = "UPDATE orders 
               SET Status = 'delivered' 
               WHERE CustomerID = $user_id 
               AND Status = 'on_the_way' 
               AND on_the_way_at <= (NOW() - INTERVAL 60 SECOND)";
mysqli_query($conn, $auto_query);


// Kullanıcının güncel siparişlerini çek
$orders = mysqli_query($conn, "
    SELECT * FROM orders
    WHERE CustomerID = $user_id
    ORDER BY OrderDate DESC
");

$new_order_id = isset($_GET["new"]) ? (int)$_GET["new"] : 0;

include "header.php";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Siparişlerim - FoodApp</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* GENEL TASARIM */
body {
    background:#f8f9fa;
    margin:0;
    font-family:'Poppins', sans-serif;
    transition: all 0.5s;
}

.orders-container {
    max-width:900px;
    margin:30px auto;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.05);
}

.header-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

/* SİPARİŞ KARTLARI */
.order-card {
    background: #fff;
    border-radius:15px;
    padding:20px;
    margin-bottom:20px;
    border:1px solid #eee;
    position:relative;
    transition: transform 0.3s;
}
.order-card:hover {
    transform: scale(1.01);
}

.status-badge {
    padding:6px 14px;
    border-radius:10px;
    font-size:12px;
    color:white;
    font-weight: 700;
}
.status-pending { background:#f1c40f; }
.status-preparing { background:#3498db; }
.status-onway { background:#e67e22; }
.status-delivered { background:#2ecc71; }

/* Kurye İzle Butonu */
.track-btn {
    display:inline-flex;
    align-items: center;
    gap: 8px;
    margin-top:15px;
    padding:10px 20px;
    border-radius:12px;
    background:#6c5ce7;
    color:white;
    text-decoration:none;
    font-size:14px;
    font-weight:bold;
    transition: 0.3s;
}
.track-btn:hover {
    background: #5b4bc4;
    box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
}

/* Animasyonlu Scooter */
.scooter-anim {
    position:absolute;
    right:20px;
    bottom:20px;
    font-size:30px;
    animation: drive 1.5s infinite linear;
    opacity: 0.8;
}

@keyframes drive {
    0% { transform: translateX(0) scaleX(-1); }
    50% { transform: translateX(-15px) scaleX(-1); }
    100% { transform: translateX(0) scaleX(-1); }
}

/* DARK MODE */
body.dark-mode { background:#121212; }
body.dark-mode .orders-container { background:#1e1e1e; color:#fff; }
body.dark-mode .order-card { background:#252525; border-color:#333; }
</style>
</head>
<body>

<?php render_customer_header(); ?>

<div class="orders-container">
    <div class="header-title">
        <h2 style="margin:0;"><i class="fa-solid fa-receipt" style="color:#ff4b4b;"></i> Siparişlerim</h2>
        <a href="dashboard.php" style="text-decoration:none; color:#ff4b4b; font-weight:600; font-size:14px;">
            <i class="fa-solid fa-arrow-left"></i> Restoranlara Dön
        </a>
    </div>

    <?php if (mysqli_num_rows($orders) == 0): ?>
        <div style="text-align:center; padding:50px;">
            <i class="fa-solid fa-box-open fa-3x" style="color:#ccc; margin-bottom:15px;"></i>
            <p>Henüz hiçbir sipariş vermediniz.</p>
        </div>
    <?php else: ?>

        <?php while ($o = mysqli_fetch_assoc($orders)): 
            $badgeClass = "status-pending"; $statusText = "Bekliyor";
            if ($o["Status"] === "preparing") { $badgeClass = "status-preparing"; $statusText = "Hazırlanıyor"; }
            elseif ($o["Status"] === "on_the_way") { $badgeClass = "status-onway"; $statusText = "Kuryede"; }
            elseif ($o["Status"] === "delivered") { $badgeClass = "status-delivered"; $statusText = "Teslim Edildi"; }
        ?>
        <div class="order-card">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <span style="font-size:12px; color:#888;">Sipariş #<?php echo $o["OrderID"]; ?></span>
                    <h3 style="margin:5px 0; font-size:18px;">
                        <?php echo number_format($o["Total"],2,",","."); ?> ₺
                    </h3>
                    <small style="color:#aaa;"><i class="fa-regular fa-calendar"></i> <?php echo $o["OrderDate"]; ?></small>
                </div>
                <span class="status-badge <?php echo $badgeClass; ?>">
                    <?php echo $statusText; ?>
                </span>
            </div>

            <p style="margin:15px 0; font-size:13px; color:#666; border-left:3px solid #eee; padding-left:10px;">
                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($o["Address"]); ?>
            </p>

            <div style="display:flex; gap:10px; align-items:center;">
                <a href="order_detail.php?id=<?php echo $o['OrderID']; ?>"
                   style="color:#3498db; font-size:13px; font-weight:700; text-decoration:none;">
                    İncele <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                </a>

                <?php if ($o["Status"] === "on_the_way"): ?>
                    <a href="track_order.php?id=<?php echo $o['OrderID']; ?>" class="track-btn">
                        <i class="fa-solid fa-motorcycle"></i> Kuryeyi İzle
                    </a>
                    <div class="scooter-anim">🛵</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>

    <?php endif; ?>
</div>

</body>
</html>