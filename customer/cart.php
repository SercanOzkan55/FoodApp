<?php
session_start();
include "../db.php";
include "header.php";

$user = (int)($_SESSION["user_id"] ?? 0);

// SEPET
$data = mysqli_query($conn,"
    SELECT cart.menu_id, cart.quantity, menus.item_name, menus.price
    FROM cart
    JOIN menus ON menus.id = cart.menu_id
    WHERE cart.user_id = $user
");

// PUAN
$points = 0;
$pq = mysqli_query($conn,"SELECT points FROM user_points WHERE user_id=$user");
if ($pq && mysqli_num_rows($pq)) {
    $points = (int)mysqli_fetch_assoc($pq)["points"];
}

/*
    PUAN → İNDİRİM ORANI
    20 puan = 1 TL indirim
*/
$max_discount = floor($points / 20); // 20 puan = 1 TL
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sepet</title>

<style>
body{
    background:#f5f6f8;
    font-family:Arial, sans-serif;
    margin:0;
}

.cart-box{
    max-width:950px;
    margin:30px auto;
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.1);
}

.cart-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.cart-item{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 0;
    border-bottom:1px solid #eee;
}

.cart-item-name{
    font-weight:bold;
    font-size:15px;
}

.qty-box{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:6px;
}

.qty-btn{
    width:30px;
    height:30px;
    border-radius:50%;
    border:none;
    background:#ff4b4b;
    color:white;
    font-size:18px;
    cursor:pointer;
}

.price{
    min-width:120px;
    text-align:right;
    font-weight:bold;
}

.total-box{
    margin-top:20px;
    text-align:right;
    font-size:18px;
}

.total-box div{
    margin-top:6px;
}

.points-box{
    margin-top:20px;
    padding:15px;
    border-radius:14px;
    background:#f0fff4;
    border:1px dashed #2ecc71;
    font-size:14px;
}

.checkout-btn{
    margin-top:20px;
    width:100%;
    padding:15px;
    border:none;
    border-radius:14px;
    background:#28a745;
    color:white;
    font-size:18px;
    cursor:pointer;
}

.checkout-btn:hover{
    background:#1e7e34;
}

/* DARK MODE */
body.dark-mode{
    background:#121212;
}
body.dark-mode .cart-box{
    background:#1f1f1f;
    color:#f5f5f5;
}
body.dark-mode .cart-item{
    border-bottom-color:#333;
}
body.dark-mode .points-box{
    background:#1c3023;
}

/* ====== ÖDEME (KART) ANİMASYONU OVERLAY ====== */
.payment-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.7);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.payment-modal{
    width:320px;
    background:#ffffff;
    border-radius:18px;
    padding:20px 22px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,0.4);
    font-family:Arial, sans-serif;
}
body.dark-mode .payment-modal{
    background:#222;
    color:#f5f5f5;
}

.card-anim{
    width:220px;
    height:130px;
    margin:0 auto 15px auto;
    border-radius:18px;
    background:linear-gradient(135deg,#ff4b4b,#ffb347);
    position:relative;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,0.4);
    animation:cardFloat 2.2s infinite ease-in-out;
}
.card-chip{
    width:40px;
    height:28px;
    background:rgba(255,255,255,0.8);
    border-radius:6px;
    position:absolute;
    left:16px;
    top:20px;
}
.card-wave{
    position:absolute;
    right:16px;
    top:22px;
    font-size:22px;
    color:#fff;
}
.card-number{
    position:absolute;
    bottom:32px;
    left:16px;
    right:16px;
    color:#fff;
    font-size:14px;
    letter-spacing:2px;
}
.card-name{
    position:absolute;
    bottom:12px;
    left:16px;
    color:#fff;
    font-size:12px;
    opacity:0.9;
}
@keyframes cardFloat{
    0%{ transform:translateY(0);}
    50%{ transform:translateY(-6px);}
    100%{ transform:translateY(0);}
}

.payment-status{
    font-size:15px;
    margin-top:5px;
}

.loading-dots span{
    animation:blink 1.4s infinite both;
}
.loading-dots span:nth-child(2){ animation-delay:0.2s;}
.loading-dots span:nth-child(3){ animation-delay:0.4s;}

@keyframes blink{
    0%{opacity:0.2;}
    20%{opacity:1;}
    100%{opacity:0.2;}
}

/* küçük fake GPS yazısı */
.fake-gps{
    margin-top:8px;
    font-size:12px;
    color:#666;
}
body.dark-mode .fake-gps{ color:#ccc; }
</style>
</head>
<body>

<?php render_customer_header(); ?>

<div class="cart-box">

    <div class="cart-header">
        <h2>🛒 Sepetim</h2>

        <?php if (isset($_SESSION["last_restaurant"])): ?>
        <a href="restaurant.php?id=<?= $_SESSION["last_restaurant"] ?>"
           style="text-decoration:none;color:#777;">
            ← Alışverişe devam et
        </a>
        <?php else: ?>
        <a href="dashboard.php" style="text-decoration:none;color:#777;">
            ← Restoranlara dön
        </a>
        <?php endif; ?>
    </div>

    <?php if (mysqli_num_rows($data) == 0): ?>
        <p>Sepetiniz boş.</p>
    <?php else: ?>

    <form action="checkout.php" method="POST" id="checkoutForm">

    <?php
    $grand = 0;
    while ($c = mysqli_fetch_assoc($data)):
        $line  = $c["quantity"] * $c["price"];
        $grand += $line;
    ?>
    <div class="cart-item">
        <div>
            <div class="cart-item-name">
                <?= htmlspecialchars($c["item_name"]) ?>
            </div>

            <div class="qty-box">
                <button type="button" class="qty-btn"
                    onclick="updateQty(<?= $c['menu_id'] ?>,-1)">−</button>
                <span><?= (int)$c["quantity"] ?></span>
                <button type="button" class="qty-btn"
                    onclick="updateQty(<?= $c['menu_id'] ?>,1)">+</button>
            </div>
        </div>

        <div class="price">
            <?= number_format($line,2,",",".") ?> ₺
        </div>
    </div>
    <?php endwhile; ?>

    <?php
    // Puanla uygulanabilecek indirim önizlemesi
    $preview_discount = ($points >= 20)
        ? min($max_discount, $grand)
        : 0;
    ?>

    <div class="total-box">
        <div>
            Ara Toplam:
            <b id="subtotal"><?= number_format($grand,2,",",".") ?> ₺</b>
        </div>

        <?php if ($points >= 20): ?>
        <div id="discountLine" style="display:none;color:#27ae60;">
            İndirim: −<span id="discountAmount">
                <?= number_format($preview_discount,2,",",".") ?>
            </span> ₺
        </div>
        <?php endif; ?>

        <div style="margin-top:10px;font-size:22px;">
            Ödenecek Tutar:
            <b id="finalTotal"><?= number_format($grand,2,",",".") ?> ₺</b>
        </div>
    </div>

    <?php if ($points >= 20): ?>
    <div class="points-box">
        ⭐ <b>Puanınız:</b> <?= $points ?><br>
        🎁 <b>Maksimum indirim:</b> <?= $preview_discount ?> ₺  
        <small>(20 puan = 1 TL indirim)</small>
        <br><br>
        <label>
            <input type="checkbox" name="use_points" value="1" id="usePoints">
            Puan kullanarak indirim uygula
        </label>
    </div>
    <?php else: ?>
    <div class="points-box" style="border-color:#ccc;background:#fafafa;">
        ⭐ Puanınız: <?= $points ?>  
        <br>
        En az <b>20 puan</b> biriktirdiğinizde puanla indirim kullanabilirsiniz.
    </div>
    <?php endif; ?>

    <label style="display:block;margin-top:20px;">Adres</label>
    <input name="address" required
        style="width:100%;padding:12px;
        border-radius:12px;border:1px solid #ccc;">

    <button class="checkout-btn">
        💳 Kart ile Öde & Siparişi Tamamla
    </button>

    </form>
    <?php endif; ?>

</div>

<!-- ÖDEME ANİMASYONU OVERLAY -->
<div class="payment-overlay" id="paymentOverlay">
    <div class="payment-modal">
        <div class="card-anim">
            <div class="card-chip"></div>
            <div class="card-wave">📶</div>
            <div class="card-number">**** **** **** 4242</div>
            <div class="card-name">FoodApp Ödeme</div>
        </div>
        <div class="payment-status" id="paymentStatus">Kart çekiliyor...</div>
        <div class="loading-dots">
            <span>•</span><span>•</span><span>•</span>
        </div>
        <div class="fake-gps" id="gpsText">
            Konum doğrulanıyor (fake GPS)...
        </div>
    </div>
</div>

<script>
// sepette + - butonları
function updateQty(id, diff){
    fetch("update_cart.php",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"menu_id="+id+"&diff="+diff
    }).then(()=>location.reload());
}

// puan indirim önizleme
const cb = document.getElementById("usePoints");
if (cb) {
    const subtotal = <?= (float)$grand ?>;
    const discount = <?= (float)$preview_discount ?>;

    const discountLine = document.getElementById("discountLine");
    const finalTotal   = document.getElementById("finalTotal");

    cb.addEventListener("change", function () {
        if (this.checked) {
            discountLine.style.display = "block";
            finalTotal.innerText =
                (subtotal - discount).toLocaleString("tr-TR",{minimumFractionDigits:2}) + " ₺";
        } else {
            discountLine.style.display = "none";
            finalTotal.innerText =
                subtotal.toLocaleString("tr-TR",{minimumFractionDigits:2}) + " ₺";
        }
    });
}

// 💳 ÖDEME ANİMASYONU → sonra gerçek POST
const checkoutForm   = document.getElementById("checkoutForm");
const overlay        = document.getElementById("paymentOverlay");
const paymentStatus  = document.getElementById("paymentStatus");
const gpsText        = document.getElementById("gpsText");

if (checkoutForm) {
    checkoutForm.addEventListener("submit", function(e){
        e.preventDefault();

        // overlay aç
        overlay.style.display = "flex";
        paymentStatus.textContent = "Kart çekiliyor...";
        gpsText.textContent = "Konum doğrulanıyor (fake GPS)...";

        // 1. adım: kart çekiliyor
        setTimeout(() => {
            paymentStatus.textContent = "Banka onayı alınıyor...";
        }, 1300);

        // 2. adım: fake gps
        setTimeout(() => {
            paymentStatus.textContent = "Ödeme tamamlanıyor...";
            gpsText.textContent = "Teslimat adresi kontrol edildi ✔";
        }, 2500);

        // 3. adım: gerçek form submit
        setTimeout(() => {
            checkoutForm.submit(); // checkout.php'ye gider
        }, 3800);
    });
}
</script>

</body>
</html>

<?php if (isset($_GET["toast"]) && $_GET["toast"]=="added"): ?>
<div id="toast" style="
    position:fixed;
    bottom:30px;
    right:30px;
    background:#2ecc71;
    color:white;
    padding:14px 20px;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,.2);
    font-size:15px;
    z-index:9999;
">
    ✅ Ürün sepete eklendi
</div>

<script>
setTimeout(() => {
    document.getElementById("toast")?.remove();
}, 2500);
</script>
<?php endif; ?>
