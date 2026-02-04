<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit;
}

$order_id = (int)($_GET["id"] ?? 0);
$user_id  = (int)$_SESSION["user_id"];

// SİPARİŞ BİLGİLERİ
$order = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT o.*, r.restaurant_name, r.id AS RestID
    FROM orders o
    JOIN restaurants r ON r.id = o.RestaurantID
    WHERE o.OrderID = $order_id AND o.CustomerID = $user_id
"));

if (!$order) {
    die("Sipariş bulunamadı.");
}

// BU SİPARİŞ DEĞERLENDİRİLMİŞ Mİ?
$ratingRow = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT rating, comment
    FROM order_ratings 
    WHERE order_id = $order_id AND user_id = $user_id
"));
$already_rated = $ratingRow ? true : false;

// PUAN GÖNDERİLDİ
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$already_rated) {

    $rating = (int)($_POST["rating"] ?? 0);
    // Yorumu güvenli bir şekilde al
    $comment = mysqli_real_escape_string($conn, trim($_POST["comment"] ?? ""));

    if ($rating < 1 || $rating > 5) $rating = 5;

    // Yorumu da kaydet
    mysqli_query($conn,"
        INSERT INTO order_ratings (order_id, user_id, restaurant_id, rating, comment)
        VALUES ($order_id, $user_id, {$order['RestID']}, $rating, '$comment')
    ");

    // RESTORAN ORTALAMASINI GÜNCELLE
    $avg = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT AVG(rating) AS avgRating, COUNT(*) AS total
        FROM order_ratings
        WHERE restaurant_id = {$order['RestID']}
    "));

    mysqli_query($conn,"
        UPDATE restaurants 
        SET rating = {$avg['avgRating']}, rating_count = {$avg['total']}
        WHERE id = {$order['RestID']}
    ");

    // Oyladıktan sonra restorana dön
    header("Location: restaurant.php?id=" . $order["RestID"] . "&rated=1");
    exit;
}

include "header.php";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sipariş #<?= $order_id ?> Detayları</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* GENEL STİLLER */
body { 
    background:#f5f5f5; 
    font-family:'Poppins', sans-serif; 
    margin:0; 
    transition: background 0.3s, color 0.3s;
}
.box {
    max-width:900px; 
    margin:40px auto; 
    background:white; 
    padding:30px; 
    border-radius:20px; 
    box-shadow:0 8px 30px rgba(0,0,0,0.15);
    transition: background 0.3s, box-shadow 0.3s;
}

/* DURUM ETİKETLERİ */
.status {
    padding:8px 16px;
    border-radius:999px;
    font-size:13px;
    font-weight: 600;
    color:white;
    display:inline-block;
    margin-bottom: 15px;
}
.pending { background:#f1c40f; }
.preparing { background:#3498db; }
.delivered { background:#2ecc71; }
.on_the_way { background:#e67e22; }

/* METİN VE BİLGİ */
.order-info strong {
    font-weight: 600;
    color: #333;
}
.order-info {
    font-size: 16px;
    line-height: 1.6;
    color: #555;
}
h2 {
    color: #ff4b4b;
    font-weight: 700;
    margin-top: 0;
    margin-bottom: 5px;
}
h3 {
    margin-top: 25px;
    color: #333;
    font-size: 20px;
}

/* YILDIZ OYLAMA */
.star { 
    font-size:38px; 
    cursor:pointer; 
    color:#ddd; 
    transition: color 0.2s;
}
.star.hover, .star.selected { 
    color:#ffc107; 
}
.rate-box { 
    margin-top:20px; 
    border-top: 1px solid #eee;
    padding-top: 20px;
}
#stars {
    margin: 15px 0;
}

/* YORUM ALANI */
textarea {
    width: 100%;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid #ddd;
    resize: vertical;
    min-height: 100px;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    margin-top: 15px;
    transition: border-color 0.3s;
}
textarea:focus {
    outline: none;
    border-color: #ffc107;
}

/* BUTONLAR */
.back-btn {
    display:inline-block; 
    padding:12px 25px;
    background:#ff4b4b; 
    color:white; 
    text-decoration:none;
    border-radius:12px; 
    margin-top:20px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}
.back-btn:hover {
    background: #e84118;
    transform: translateY(-1px);
}
.rate-box button {
    background: #2ecc71 !important;
}
.rate-box button:hover {
    background: #27ae60 !important;
}
.rated-message {
    color:#2ecc71; 
    font-weight:600; 
    margin-top:10px;
    font-size: 16px;
}
.user-comment-display {
    background: #f0f0f0;
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
    font-style: italic;
    font-size: 15px;
    color: #555;
    white-space: pre-wrap; /* Yorumdaki satır sonlarını koru */
}


/* ====================================
   DARK MODE UYUMLULUĞU
   ==================================== */
body.dark-mode{ 
    background:#121212;
}
body.dark-mode .box{
    background:#1e1e1e;
    box-shadow:0 8px 30px rgba(0,0,0,0.6);
}
body.dark-mode h2, body.dark-mode h3 {
    color:#f5f5f5; 
}
body.dark-mode .order-info strong,
body.dark-mode .order-info {
    color:#bbb; 
}
body.dark-mode .rate-box {
    border-top: 1px solid #333;
}
body.dark-mode textarea {
    background: #2b2b2b;
    color: #f5f5f5;
    border-color: #444;
}
body.dark-mode textarea:focus {
    border-color: #ffc107;
}
body.dark-mode .user-comment-display {
    background: #2b2b2b;
    color: #bbb;
}
</style>
</head>
<body>

<?php render_customer_header(); ?>

<div class="box">
    <h2><?= htmlspecialchars($order["restaurant_name"]); ?></h2>

    <?php
        $cls = "pending";
        $txt = "Bekliyor";

        if ($order["Status"] === "preparing") {
            $cls = "preparing";
            $txt = "Hazırlanıyor";
        } elseif ($order["Status"] === "on_the_way") {
            $cls = "on_the_way";
            $txt = "Kuryede";
        } elseif ($order["Status"] === "delivered") {
            $cls = "delivered";
            $txt = "Teslim Edildi";
        }
    ?>
    <div class="status <?= $cls ?>"><?= $txt ?></div>

    <div class="order-info">
        <strong>Toplam Tutar:</strong> <?= number_format($order["Total"],2,",",".") ?> ₺<br>
        <strong>Sipariş Tarihi:</strong> <?= date("d.m.Y H:i", strtotime($order["OrderDate"])) ?><br>
        <strong>Teslimat Adresi:</strong> <?= htmlspecialchars($order["Address"]) ?>
    </div>

    <?php if ($order["Status"] === "delivered"): ?>

        <div class="rate-box">
            <h3>Siparişi Oyla ve Yorum Yap</h3>
            
            <?php if ($already_rated): ?>
                <?php
                    $rated_stars = '';
                    for ($i = 0; $i < (int)$ratingRow["rating"]; $i++) {
                        $rated_stars .= '<span class="star selected">★</span>';
                    }
                    for ($i = (int)$ratingRow["rating"]; $i < 5; $i++) {
                        $rated_stars .= '<span class="star">★</span>';
                    }
                ?>
                <p class="rated-message">
                    ✔ Bu siparişi zaten puanladınız.
                </p>
                
                <div id="stars" style="pointer-events: none;">
                    <?= $rated_stars ?>
                </div>

                <?php if (!empty($ratingRow["comment"])): ?>
                    <h4>Yorumunuz:</h4>
                    <div class="user-comment-display">
                        <?= nl2br(htmlspecialchars($ratingRow["comment"])) ?>
                    </div>
                <?php endif; ?>

                <a href="restaurant.php?id=<?= $order["RestID"] ?>" class="back-btn" style="background: #3498db;">
                    Restorana Dön
                </a>

            <?php else: ?>
                <form method="POST">
                    <div id="stars">
                        <span class="star" data-v="1">★</span>
                        <span class="star" data-v="2">★</span>
                        <span class="star" data-v="3">★</span>
                        <span class="star" data-v="4">★</span>
                        <span class="star" data-v="5">★</span>
                    </div>
                    <input type="hidden" name="rating" id="rating" value="0">
                    
                    <textarea name="comment" placeholder="Restoran ve sipariş hakkında yorumunuzu buraya yazabilirsiniz (isteğe bağlı)..."></textarea>

                    <button type="submit" class="back-btn" style="background:#2ecc71;">
                        Gönder
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
let stars = document.querySelectorAll(".star");
let rating = document.getElementById("rating");

// Yorum kutusunda oylama yapılmamışsa, varsayılan olarak 5 yıldız seçili gelmesi için.
// Yıldızlar yüklendikten sonra çalışır.
document.addEventListener('DOMContentLoaded', () => {
    // Eğer bir seçim yoksa (rating.value === 0), varsayılan olarak 5'i işaretle
    if (rating && rating.value === "0") {
        rating.value = 5;
        stars.forEach(x => x.classList.add("selected"));
    }
});


stars.forEach(s => {
    // Mouse Üzerinde Durum (Hover)
    s.addEventListener("mouseover", () => {
        // Seçim yapılmamışsa hover durumunu uygula
        if (rating.value == 0) {
            stars.forEach(x => x.classList.remove("hover"));
            for (let i = 0; i < s.dataset.v; i++) {
                stars[i].classList.add("hover");
            }
        }
    });

    // Tıklama (Seçme)
    s.addEventListener("click", () => {
        rating.value = s.dataset.v;
        stars.forEach(x => x.classList.remove("selected", "hover")); 
        for (let i = 0; i < s.dataset.v; i++) {
            stars[i].classList.add("selected");
        }
    });
});

// Mouse yıldızların dışına çıkınca hover temizle
document.addEventListener("mouseout", (e) => {
    // Yıldız kutusunun dışına çıkınca ve bir seçim yapılmamışsa (rating.value === 0) temizle
    if (!e.target.closest("#stars") && rating.value == 0) {
        stars.forEach(x => x.classList.remove("hover"));
    }
});
</script>

</body>
</html>