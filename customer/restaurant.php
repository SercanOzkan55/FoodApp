<?php
session_start();
include "../db.php";
include "header.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$_SESSION["last_restaurant"] = $id;

/* RESTORAN */
$restaurant = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM restaurants WHERE id = $id")
);
if (!$restaurant) {
    die("Restoran bulunamadı");
}

/* LOGO YOLU */
$logo_filename = $restaurant["logo"];
$logo_path = "../seller/uploads/" . $logo_filename;

if (!$logo_filename || !file_exists($logo_path)) {
    $logo_path = "https://via.placeholder.com/800x500?text=Restaurant";
}

/* MENÜ */
$menu = mysqli_query($conn, "SELECT * FROM menus WHERE restaurant_id = $id");

/* YORUMLAR */
$reviews_query = mysqli_query($conn, "
    SELECT 
        o.rating, 
        o.comment, 
        o.order_id, 
        u.fullname AS username,
        u.profile_image  /* Profil resmi buraya eklendi */
    FROM order_ratings o
    JOIN users u ON u.id = o.user_id
    WHERE o.restaurant_id = $id AND o.comment IS NOT NULL AND o.comment != ''
    ORDER BY o.order_id DESC
");

$reviews = mysqli_fetch_all($reviews_query, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($restaurant["restaurant_name"]) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">


<style>
body{
    background:#f5f5f5;
    margin:0;
    font-family:'Poppins', sans-serif;
    transition: background 0.3s;
}

.wrapper{
    padding:30px;
    max-width:1100px;
    margin:auto;
    transition: color 0.3s;
}

/* 🔥 BLUR BANNER SİSTEMİ */
.banner-container{
    position:relative;
    width:100%;
    height:260px;
    border-radius:20px;
    overflow:hidden;
    margin-bottom:20px;
}
.banner-bg{
    position:absolute;
    inset:0;
    background:url('<?= $logo_path ?>') center/cover no-repeat;
    filter:blur(12px) brightness(0.7);
    transform:scale(1.2);
}
.banner-foreground{
    position:absolute;
    left:50%;
    top:50%;
    transform:translate(-50%, -50%);
    max-height:240px;
    border-radius:14px;
    box-shadow:0 8px 25px rgba(0,0,0,.25);
    max-width: 90%;
    object-fit: contain;
    background-color: rgba(255, 255, 255, 0.9);
}
.review-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.review-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ff4b4b;
}

/* MENÜ GRID */
.menu-grid{
    display:flex;
    flex-wrap:wrap;
    gap:22px;
    margin-top:30px;
}
.card{
    width:260px;
    background:white;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 4px 14px rgba(0,0,0,.12);
    transition: background 0.3s, box-shadow 0.3s;
}
.card img{
    width:100%;
    height:150px;
    object-fit:cover;
}
.card-body{
    padding:15px;
}
.card-body h4 {
    margin-top: 0;
    font-size: 18px;
    font-weight: 600;
}
.card-body p {
    margin-bottom: 10px;
    font-size: 16px;
}

/* Adet butonları */
.qty-box{
    display:flex;
    align-items:center;
    gap:12px;
    margin-top:10px;
}
.qty-btn{
    width:34px;
    height:34px;
    border-radius:50%;
    border:none;
    background:#ff4b4b;
    color:white;
    font-size:20px;
    cursor:pointer;
    transition: background 0.3s;
}
.qty-num{
    min-width:24px;
    text-align:center;
    font-size:16px;
    font-weight:bold;
    color: #333;
    transition: color 0.3s;
}
.fixed-cart{
    position:fixed;
    right:22px;
    bottom:22px;
    background:#28a745;
    color:white;
    padding:16px 26px;
    border-radius:999px;
    font-size:17px;
    cursor:pointer;
    display:none;
    box-shadow:0 10px 25px rgba(0,0,0,.2);
}

/* ====================================
   YORUMLAR BÖLÜMÜ STİLLERİ
   ==================================== */
.reviews-section {
    margin-top: 50px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.reviews-section h3 {
    color: #333;
    font-size: 24px;
    margin-bottom: 25px;
    font-weight: 700;
}
.review-card {
    background: #ffffff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    transition: background 0.3s, box-shadow 0.3s;
}
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    border-bottom: 1px dashed #eee;
    padding-bottom: 8px;
}
.review-username {
    font-weight: 600;
    color: #ff4b4b;
    font-size: 16px;
}
.review-rating {
    font-size: 18px;
}
.star-static {
    color: #ddd;
}
.star-static.selected {
    color: #ffc107;
}
.review-text {
    color: #555;
    line-height: 1.6;
    margin-top: 10px;
    font-style: italic;
    white-space: pre-wrap; /* Satır atlamayı korur */
}
.no-reviews {
    color: #777;
    padding: 15px;
    border: 1px dashed #ddd;
    border-radius: 10px;
    text-align: center;
}

/* ====================================
   DARK MODE UYUMLULUĞU
   ==================================== */
body.dark-mode{ 
    background:#121212;
    color:#f5f5f5;
}
body.dark-mode h2,
body.dark-mode p,
body.dark-mode h4{
    color:#f5f5f5;
}
body.dark-mode .card{
    background:#1e1e1e;
    box-shadow: 0 4px 14px rgba(255,255,255,0.05);
    border: 1px solid #333;
}
body.dark-mode .card-body h4,
body.dark-mode .card-body p,
body.dark-mode .qty-num{
    color:#f5f5f5;
}
body.dark-mode .qty-btn{
    background:#e67e22;
    color:white;
}

/* Dark Mode Yorum Düzeltmeleri */
body.dark-mode .reviews-section h3 {
    color: #f5f5f5;
}
body.dark-mode .review-card {
    background: #1e1e1e;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
    border: 1px solid #333;
}
body.dark-mode .review-header {
    border-bottom: 1px dashed #444;
}
body.dark-mode .review-username {
    color: #ffb8b8;
}
body.dark-mode .review-text {
    color: #bbb;
}
body.dark-mode .star-static {
    color: #555; /* Boş yıldızlar koyu */
}
body.dark-mode .star-static.selected {
    color: #ffc107;
}
body.dark-mode .no-reviews {
    color: #bbb;
    border: 1px dashed #444;
    background: #1e1e1e;
}
</style>
</head>

<body>
<?php render_customer_header(); ?>

<div class="wrapper">

    <div class="banner-container">
        <div class="banner-bg"></div>
        <img src="<?= $logo_path ?>" class="banner-foreground" alt="<?= htmlspecialchars($restaurant["restaurant_name"]) ?>">
    </div>

    <h2><?= htmlspecialchars($restaurant["restaurant_name"]) ?></h2>
    <p><?= htmlspecialchars($restaurant["description"]) ?></p>

    <form method="POST" action="add_multiple_to_cart.php" id="menuForm">

        <div class="menu-grid">
        <?php while($m = mysqli_fetch_assoc($menu)): ?>

            <?php
            $menu_image_path = "../seller/uploads/" . $m["image"];
            $menu_image_url = $m["image"] && file_exists($menu_image_path) 
                            ? $menu_image_path 
                            : "https://via.placeholder.com/400x150?text=Menu";
            ?>

            <div class="card">
                <img src="<?= $menu_image_url ?>" alt="<?= htmlspecialchars($m["item_name"]) ?>">
                <div class="card-body">
                    <h4><?= htmlspecialchars($m["item_name"]) ?></h4>
                    <p style="color: #ff4b4b; font-weight: bold;"><?= number_format($m["price"],2,",",".") ?> ₺</p>

                    <div class="qty-box">
                        <button type="button" class="qty-btn" onclick="updateQty(<?= $m['id'] ?>,-1)">−</button>
                        <div class="qty-num" id="qty-<?= $m['id'] ?>">0</div>
                        <button type="button" class="qty-btn" onclick="updateQty(<?= $m['id'] ?>,1)">+</button>
                    </div>

                    <input type="hidden" name="items[<?= $m['id'] ?>]" id="input-<?= $m['id'] ?>" value="0">
                </div>
            </div>

        <?php endwhile; ?>
        </div>
        <button type="submit" class="fixed-cart" id="cartBtn">🛒 Sepete Git</button>
    </form>
    
    <div class="reviews-section">
    <h3>Müşteri Yorumları (<?= count($reviews) ?>)</h3>

    <?php if (count($reviews) > 0): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-user-info">
                        <?php 
                        // Profil resmi kontrolü
                        $review_avatar = $review['profile_image'] 
                            ? "uploads/avatars/" . $review['profile_image'] 
                            : "https://ui-avatars.com/api/?name=" . urlencode($review['username']) . "&background=ff4b4b&color=ffffff&bold=true";
                        ?>
                        <img src="<?= $review_avatar ?>" class="review-avatar" alt="User">
                        <span class="review-username"><?= htmlspecialchars($review['username']) ?></span>
                    </div>
                    
                    <div class="review-rating">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<span class="star-static ' . ($i <= $review['rating'] ? 'selected' : '') . '">★</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <p class="review-text">
                    <?= nl2br(htmlspecialchars($review['comment'])) ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-reviews">Bu restoran için henüz yorum yapılmamış.</p>
    <?php endif; ?>
</div>

<script>
function updateQty(id,diff){
    let q=document.getElementById("qty-"+id);
    let i=document.getElementById("input-"+id);
    let v=parseInt(i.value)+diff;
    if(v<0) v=0;
    i.value=v;
    q.innerText=v;
    toggleBtn();
}
function toggleBtn(){
    let show=false;
    document.querySelectorAll("input[name^='items']").forEach(el=>{
        if(parseInt(el.value)>0) show=true;
    });
    // Sepet butonunun görünürlüğünü ayarla
    document.getElementById("cartBtn").style.display = show ? "block" : "none";
}
// Sayfa yüklendiğinde buton durumunu kontrol et
document.addEventListener('DOMContentLoaded', toggleBtn);
</script>

</body>
</html>