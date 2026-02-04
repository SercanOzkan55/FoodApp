<?php
if (!isset($_SESSION)) session_start();
include_once "../db.php";

function render_customer_header() {
    $user_id   = $_SESSION["user_id"] ?? 0;
    $fullname  = $_SESSION["fullname"] ?? "Kullanıcı";
    $conn = $GLOBALS["conn"];

    /* ===== SEPET SAYISI ===== */
    $count = 0;
    if ($user_id) {
        $q = mysqli_query($conn, "SELECT SUM(quantity) AS t FROM cart WHERE user_id=$user_id");
        if ($q) {
            $row = mysqli_fetch_assoc($q);
            $count = $row["t"] ?? 0;
        }
    }

    /* ===== PUAN / SEVİYE / PROGRESS HESAPLAMA ===== */
    $points = 0;
    $pq = mysqli_query($conn,"SELECT points FROM user_points WHERE user_id=$user_id");
    if ($pq && mysqli_num_rows($pq)) {
        $points = (int)mysqli_fetch_assoc($pq)["points"];
    }

    // Seviye eşikleri ve ilerleme mantığı
    $level = "Bronze";
    $levelColor = "#cd7f32";
    $nextLevel = "Silver";
    $targetPoints = 1500;
    $levelShadow = "#965e23";

    if ($points >= 5000) {
        $level = "Gold";
        $levelColor = "#FFD700";
        $levelShadow = "#ccab00";
        $targetPoints = 5000; // Maksimum seviye
        $nextLevel = "Efsane";
    } elseif ($points >= 1500) {
        $level = "Silver";
        $levelColor = "#c0c0c0";
        $levelShadow = "#999999";
        $targetPoints = 5000;
        $nextLevel = "Gold";
    }

    // İlerleme yüzdesi hesaplama
    $progressPercent = ($points / $targetPoints) * 100;
    if($progressPercent > 100) $progressPercent = 100;
    $remaining = $targetPoints - $points;

    /* ===== PROFİL RESMİ ÇEKME (profile_image sütununa göre) ===== */
    $profile_img = "";
    $img_q = mysqli_query($conn, "SELECT profile_image FROM users WHERE id=$user_id");
    if ($img_q && mysqli_num_rows($img_q) > 0) {
        $user_data = mysqli_fetch_assoc($img_q);
        $profile_img = $user_data["profile_image"] ?? ""; 
    }

    if (!$profile_img) {
        $avatar = "https://ui-avatars.com/api/?name=" . urlencode($fullname) . "&background=ff4b4b&color=ffffff&size=128&bold=true";
    } else {
        $avatar = "uploads/avatars/" . $profile_img;
    }
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* GENEL HEADER */
.top-bar {
    background:#fff; border-bottom:1px solid #eee; padding:12px 30px;
    display:flex; justify-content:space-between; align-items:center;
    font-family:'Poppins', sans-serif; position:sticky; top:0; z-index:1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: all 0.5s;
}

/* PROGRESS BAR */
.progress-container {
    width: 100px; height: 6px; background: #eee;
    border-radius: 10px; margin-top: 5px; overflow: hidden;
}
.progress-bar {
    height: 100%; background: <?= $levelColor ?>;
    width: <?= $progressPercent ?>%; transition: width 0.8s ease-in-out;
    box-shadow: 0 0 5px <?= $levelColor ?>;
}
.next-level-info {
    font-size: 10px; color: #888; margin-top: 3px; font-weight: 600;
}

/* TOAST BİLDİRİM SİSTEMİ */
#toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
.toast {
    background: #fff; color: #333; padding: 15px 25px; border-radius: 12px;
    margin-bottom: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: flex; align-items: center; gap: 12px; font-weight: 600;
    animation: toastIn 0.5s ease forwards; min-width: 250px;
}
.toast.success { border-left: 6px solid #2ecc71; }
.toast.error { border-left: 6px solid #e74c3c; }

@keyframes toastIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes toastOut { to { transform: translateX(120%); opacity: 0; } }

/* AVATAR DÜZENLEME */
.avatar-wrapper { position: relative; width: 45px; height: 45px; cursor: pointer; }
.avatar-wrapper img { width: 100%; height: 100%; border-radius: 50%; border: 3px solid #ff4b4b; object-fit: cover; }
.avatar-edit-icon { 
    position: absolute; bottom: 0; right: 0; background: #ff4b4b; color: white; 
    width: 18px; height: 18px; border-radius: 50%; font-size: 10px; 
    display: flex; align-items: center; justify-content: center; border: 2px solid white;
}

/* MODAL */
.modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
.modal-content { background: white; padding: 30px; border-radius: 20px; width: 350px; text-align: center; }

/* DARK MODE UYUMU */
body.dark-mode .top-bar { background: #1e1e1e; border-color: #333; color: #fff; }
body.dark-mode .progress-container { background: #333; }
body.dark-mode .toast { background: #2b2b2b; color: #fff; }
body.dark-mode .modal-content { background: #1e1e1e; color: white; }

/* DİĞER */
.top-left { font-size: 24px; font-weight: 700; color: #ff4b4b; text-decoration: none; }
.top-right { display: flex; align-items: center; gap: 20px; }
.cart-btn { background:#ff6b6b; color:#fff; padding:8px 18px; border-radius:12px; text-decoration:none; position:relative; font-weight:600; }
.cart-count { background:white; color:#ff4b4b; border: 2px solid #ff4b4b; padding:2px 7px; border-radius:999px; position:absolute; top:-10px; right:-10px; font-size:12px; font-weight:700; }
.dark-toggle { background:#f0f0f0; border:none; border-radius:999px; width:45px; height:45px; cursor:pointer; position:relative; overflow:hidden; }
.profile-box { display:flex; align-items:center; gap:12px; position:relative; }
.profile-menu { position:absolute; right:0; top:60px; background:white; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.1); display:none; min-width:180px; z-index: 1001; }
.profile-menu a { display:block; padding:12px 15px; text-decoration:none; color:#333; font-size:14px; }
.profile-menu a:hover { background:#fff3f3; color:#ff4b4b; }
.upload-btn { background: #ff4b4b; color: white; padding: 10px 20px; border-radius: 10px; cursor: pointer; display: inline-block; margin-top: 15px; }
</style>

<div id="toast-container"></div>

<div id="avatarModal" class="modal">
    <div class="modal-content">
        <h3>Profil Resmini Değiştir</h3>
        <form action="update_avatar.php" method="post" enctype="multipart/form-data">
            <input type="file" name="profile_pic" id="avatarInput" accept="image/*" style="display:none;" onchange="this.form.submit()">
            <label for="avatarInput" class="upload-btn"><i class="fa-solid fa-camera"></i> Resim Seç</label>
        </form>
        <button onclick="document.getElementById('avatarModal').style.display='none'" style="margin-top:15px; background:none; border:none; color:#888; cursor:pointer;">Vazgeç</button>
    </div>
</div>

<div class="top-bar">
    <a href="dashboard.php" class="top-left">🍽 FoodApp</a>
    <div class="top-middle">Hoş geldin, <strong style="color:#ff4b4b"><?= htmlspecialchars($fullname) ?></strong></div>
    <div class="top-right">
        <button id="darkToggle" class="dark-toggle"><span class="icon">🌙</span></button>
        <a href="cart.php" class="cart-btn">🛒 Sepet <?php if ($count > 0): ?><span class="cart-count"><?= $count ?></span><?php endif; ?></a>
        <div class="profile-box">
            <div class="avatar-wrapper" onclick="document.getElementById('avatarModal').style.display='flex'">
                <img src="<?= $avatar ?>" id="avatarImg">
                <div class="avatar-edit-icon"><i class="fa-solid fa-camera"></i></div>
            </div>
            <div style="display:flex; flex-direction:column; cursor:pointer;" onclick="toggleProfileMenu(event)">
                <span style="font-size:14px; font-weight:700;"><?= htmlspecialchars(explode(" ",$fullname)[0]) ?> ⌄</span>
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="font-size:10px; padding:2px 8px; border-radius:999px; color:#fff; background:<?= $levelColor ?>;"><?= $level ?></span>
                    <span style="font-size:11px; font-weight:700; color:#ff9800;">⭐ <?= $points ?></span>
                </div>
                <div class="progress-container"><div class="progress-bar"></div></div>
                <?php if($remaining > 0): ?><span class="next-level-info"><?= $nextLevel ?> için <?= $remaining ?> puan</span><?php endif; ?>
            </div>
            <div class="profile-menu" id="profileMenu">
                <a href="orders.php">🧾 Siparişlerim</a>
                <a href="../logout.php">🚪 Çıkış Yap</a>
            </div>
        </div>
    </div>
</div>

<script>
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = (type === 'success' ? '✅' : '❌') + ` <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'toastOut 0.5s ease forwards'; setTimeout(() => toast.remove(), 500); }, 3500);
}

function toggleProfileMenu(e) {
    const pMenu = document.getElementById("profileMenu");
    pMenu.style.display = pMenu.style.display === "block" ? "none" : "block";
    e.stopPropagation();
}

document.addEventListener("click", (e) => {
    if(!e.target.closest('.profile-box')) document.getElementById("profileMenu").style.display = "none";
});

const toggle = document.getElementById("darkToggle");
toggle.onclick = () => {
    document.body.classList.toggle("dark-mode");
    const isDark = document.body.classList.contains("dark-mode");
    localStorage.setItem("dark", isDark ? "on" : "off");
    showToast(isDark ? "Karanlık Mod 🌙" : "Aydınlık Mod ☀️");
};
if(localStorage.getItem("dark") === "on") document.body.classList.add("dark-mode");

window.onclick = (e) => { if (e.target == document.getElementById('avatarModal')) document.getElementById('avatarModal').style.display = "none"; }
</script>
<?php } ?>