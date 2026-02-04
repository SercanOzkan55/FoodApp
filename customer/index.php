<?php
session_start();
include "../db.php";
include "header.php";

$restaurants = mysqli_query($conn, "SELECT * FROM restaurants");
?>

<style>
.card-container {
    padding: 30px;
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
}
.card {
    width: 300px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,.08);
    transition: .3s;
}
.card:hover { transform: translateY(-6px); }
.card img {
    width: 100%;
    height: 170px;
    object-fit: cover;
}
.card-body { padding: 18px; }
.btn {
    background: #ff4b4b;
    color: white;
    padding: 10px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    margin-top: 10px;
}
</style>

<h2 style="padding:30px 30px 0">🍽 Restoranlar</h2>

<div class="card-container">
<?php while($r = mysqli_fetch_assoc($restaurants)): ?>
    <div class="card">
        <img src="../seller/uploads/<?= $r["logo"] ?>"
             onerror="this.src='https://via.placeholder.com/400x170'">
        <div class="card-body">
            <h4><?= $r["restaurant_name"] ?></h4>
            <p><?= $r["description"] ?></p>
            <a class="btn" href="restaurant.php?id=<?= $r["id"] ?>">Menüyü Gör</a>
        </div>
    </div>
<?php endwhile; ?>
</div>
