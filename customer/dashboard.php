<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// SQL Sorgusu: Favori olanları en üste taşı
$restaurants_query = "
    SELECT 
        r.id, 
        r.restaurant_name, 
        r.description, 
        r.logo, 
        r.rating, 
        r.rating_count,
        CASE WHEN f.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_favorite
    FROM restaurants r
    LEFT JOIN favorites f ON r.id = f.restaurant_id AND f.user_id = $user_id
    ORDER BY 
        is_favorite DESC,
        r.rating DESC,
        r.id DESC
";

$restaurants = mysqli_query($conn, $restaurants_query);

include "header.php";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Restoranlar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* GENEL */
body {
    background:#f8f9fa;
    margin:0;
    font-family:'Poppins', sans-serif;
    transition: background 0.5s;
}

/* ARAMA VE FİLTRELEME ALANI */
.search-container {
    background: white;
    padding: 30px 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    transition: background 0.5s;
}

.search-inner {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.search-box-wrapper {
    position: relative;
    margin-bottom: 20px;
}

.search-box-wrapper i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #ff4b4b;
    font-size: 20px;
}

.search-input {
    width: 100%;
    padding: 18px 20px 18px 60px;
    border-radius: 50px;
    border: 2px solid #eee;
    font-size: 16px;
    font-family: inherit;
    outline: none;
    transition: all 0.3s;
    box-sizing: border-box;
}

.search-input:focus {
    border-color: #ff4b4b;
    box-shadow: 0 0 15px rgba(255, 75, 75, 0.1);
}

.categories {
    display: flex;
    justify-content: center;
    gap: 15px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.cat-btn {
    background: #f0f0f0;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    color: #555;
    transition: all 0.3s;
    white-space: nowrap;
}

.cat-btn:hover, .cat-btn.active {
    background: #ff4b4b;
    color: white;
}

/* KARTLAR */
.grid {
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    justify-content: center;
    max-width: 1400px;
    margin: 0 auto;
}

.card {
    width: 320px;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
    animation: fadeIn 0.6s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.restaurant-img {
    width: 100%;
    height: 190px;
    object-fit: cover;
    background: #eee;
}

.card-content {
    padding: 20px;
}

.card-content h3 {
    margin: 0 0 8px 0;
    font-size: 20px;
    color: #2d3436;
}

.card-content p {
    margin: 0 0 15px 0;
    color: #636e72;
    font-size: 14px;
    height: 40px;
    overflow: hidden;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f1f1;
    padding-top: 15px;
}

.rating-badge {
    background: #fff9db;
    color: #fab005;
    padding: 5px 10px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
}

/* DARK MODE */
body.dark-mode { background: #121212; }
body.dark-mode .search-container { background: #1e1e1e; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
body.dark-mode .search-input { background: #2b2b2b; border-color: #444; color: white; }
body.dark-mode .cat-btn { background: #333; color: #aaa; }
body.dark-mode .card { background: #1e1e1e; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 1px solid #333; }
body.dark-mode .card-content h3 { color: white; }
body.dark-mode .card-content p { color: #bbb; }
body.dark-mode .card-footer { border-top-color: #333; }
body.dark-mode .rating-badge { background: #2d2a16; }
</style>
</head>
<body>

<?php render_customer_header(); ?>

<div class="search-container">
    <div class="search-inner">
        <div class="search-box-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="restSearch" class="search-input" placeholder="Restoran veya mutfak ara..." onkeyup="filterRestaurants()">
        </div>
        <div class="categories">
            <button class="cat-btn active" onclick="filterCat('all', this)">Tümü</button>
            <button class="cat-btn" onclick="filterCat('Kebap', this)">🔥 Kebap</button>
            <button class="cat-btn" onclick="filterCat('Pizza', this)">🍕 Pizza</button>
            <button class="cat-btn" onclick="filterCat('Tatlı', this)">🍰 Tatlı</button>
            <button class="cat-btn" onclick="filterCat('Burger', this)">🍔 Burger</button>
        </div>
    </div>
</div>

<div class="grid" id="restaurantGrid">
<?php while ($r = mysqli_fetch_assoc($restaurants)): ?>
    <div class="card" data-name="<?= strtolower(htmlspecialchars($r["restaurant_name"])) ?>" data-desc="<?= strtolower(htmlspecialchars($r["description"])) ?>">
        
        <button type="button" class="favorite-btn" 
                data-rest-id="<?= (int)$r["id"] ?>" 
                data-is-fav="<?= (int)$r["is_favorite"] ?>"
                onclick="event.stopPropagation(); toggleFavorite(this, <?= (int)$r["id"] ?>)">
            <?= $r["is_favorite"] ? '❤️' : '🤍' ?>
        </button>

        <img class="restaurant-img" src="<?= !empty($r["logo"]) ? "../seller/uploads/" . htmlspecialchars($r["logo"]) : "https://via.placeholder.com/400x180?text=Restaurant" ?>" onerror="this.src='https://via.placeholder.com/400x180?text=Restaurant';">

        <div class="card-content">
            <h3><?= htmlspecialchars($r["restaurant_name"]) ?></h3>
            <p><?= htmlspecialchars($r["description"]) ?></p>

            <div class="card-footer">
                <div class="rating-badge">
                    ⭐ <?= $r["rating_count"] > 0 ? number_format($r["rating"],1) : 'Yeni' ?>
                </div>
                <a href="restaurant.php?id=<?= (int)$r["id"] ?>" class="btn-menu" onclick="event.stopPropagation();">
                    İncele
                </a>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

<script>
// AKILLI FİLTRELEME FONKSİYONU
function filterRestaurants() {
    let input = document.getElementById('restSearch').value.toLowerCase();
    let cards = document.querySelectorAll('#restaurantGrid .card');

    cards.forEach(card => {
        let name = card.getAttribute('data-name');
        let desc = card.getAttribute('data-desc');
        
        if (name.includes(input) || desc.includes(input)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

// KATEGORİ FİLTRELEME (Simülasyon)
function filterCat(cat, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    let cards = document.querySelectorAll('#restaurantGrid .card');
    let input = cat === 'all' ? '' : cat.toLowerCase();
    
    cards.forEach(card => {
        let name = card.getAttribute('data-name');
        let desc = card.getAttribute('data-desc');
        if (input === '' || name.includes(input) || desc.includes(input)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

// FAVORİ FONKSİYONU (Öncekiyle aynı, toast entegreli)
function toggleFavorite(button, restId) {
    let isFav = parseInt(button.getAttribute('data-is-fav'));
    let newFavStatus = isFav === 1 ? 0 : 1;
    button.setAttribute('data-is-fav', newFavStatus);
    button.innerHTML = newFavStatus === 1 ? '❤️' : '🤍';

    fetch('toggle_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `restaurant_id=${restId}&action=${newFavStatus === 1 ? 'add' : 'remove'}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(newFavStatus === 1 ? "Favorilere eklendi! ❤️" : "Favorilerden çıkarıldı.", 'success');
        } else {
            showToast("Hata oluştu.", 'error');
        }
    });
}
</script>

</body>
</html>