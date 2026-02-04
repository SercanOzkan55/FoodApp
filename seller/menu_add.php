<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "seller") {
    header("Location: ../login.php");
    exit;
}

include "../db.php";

$seller_id = (int)$_SESSION["user_id"];

// Satıcının restoranı yoksa önce restoran oluştur
$restRes = mysqli_query($conn, "SELECT * FROM restaurants WHERE seller_id = '$seller_id' LIMIT 1");
if (mysqli_num_rows($restRes) == 0) {
    header("Location: restaurant_create.php");
    exit;
}
$restaurant    = mysqli_fetch_assoc($restRes);
$restaurant_id = (int)$restaurant["id"];

$errors  = [];
$success = "";

/* ===================== SİPARİŞ DURUM GÜNCELLE ===================== */
if (isset($_GET["order"]) && isset($_GET["status"])) {
    $oid = (int)$_GET["order"];
    $st  = $_GET["status"];
    $allowed = ["pending", "preparing", "on_the_way"];
    
    if (in_array($st, $allowed, true)) {
        $safe_st = mysqli_real_escape_string($conn, $st);
        
        if ($st === "on_the_way") {
            // Kuryeye verildiği anı NOW() ile mühürle
            $update_sql = "UPDATE orders SET Status='$safe_st', on_the_way_at = NOW() WHERE OrderID = $oid AND RestaurantID = $restaurant_id";
        } else {
            $update_sql = "UPDATE orders SET Status='$safe_st' WHERE OrderID = $oid AND RestaurantID = $restaurant_id";
        }
        mysqli_query($conn, $update_sql);
    }
    header("Location: menu_add.php#orders");
    exit;
}

/* ============ MENÜ SİLME ============ */
if (isset($_GET["delete"])) {
    $mid = (int)$_GET["delete"];
    mysqli_query($conn, "DELETE FROM menus WHERE id = '$mid' AND restaurant_id = '$restaurant_id'");
    header("Location: menu_add.php");
    exit;
}

/* ============ MENÜ EKLEME ============ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["item_name"])) {
    $item_name = trim($_POST["item_name"] ?? "");
    $price     = trim($_POST["price"] ?? "");

    if ($item_name === "") $errors[] = "Yemeğin adını giriniz.";
    if ($price === "" || !is_numeric($price)) $errors[] = "Geçerli bir fiyat giriniz.";

    $imageFileName = null;
    if (!empty($_FILES["image"]["name"])) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $imageFileName = time() . "_" . rand(1000, 9999) . "." . $ext;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir . $imageFileName)) $errors[] = "Görsel yüklenemedi.";
    }

    if (empty($errors)) {
        $p = (float)$price;
        $stmt = mysqli_prepare($conn, "INSERT INTO menus (restaurant_id, item_name, price, image) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isds", $restaurant_id, $item_name, $p, $imageFileName);
        mysqli_stmt_execute($stmt);
        $success = "Menü öğesi başarıyla eklendi.";
    }
}

/* ====================== VERİLERİ ÇEK ====================== */
$menus = mysqli_query($conn, "SELECT * FROM menus WHERE restaurant_id = '$restaurant_id' ORDER BY id DESC");
$orders = mysqli_query($conn, "SELECT o.*, u.fullname FROM orders o JOIN users u ON u.id = o.CustomerID WHERE o.RestaurantID = $restaurant_id ORDER BY CASE o.Status WHEN 'pending' THEN 1 WHEN 'preparing' THEN 2 WHEN 'on_the_way' THEN 3 WHEN 'delivered' THEN 4 ELSE 5 END, o.OrderDate DESC");

// İstatistikler için basit sayımlar
$total_orders = mysqli_num_rows($orders);
$active_orders = mysqli_num_rows(mysqli_query($conn, "SELECT OrderID FROM orders WHERE RestaurantID = $restaurant_id AND Status != 'delivered'"));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Satıcı Paneli - <?= htmlspecialchars($restaurant["restaurant_name"]); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7f6;
            --card: #ffffff;
            --text: #2d3436;
            --primary: #6c5ce7;
            --secondary: #00b894;
            --border: #dfe6e9;
        }

        body.dark-mode {
            --bg: #0f111a;
            --card: #1a1d29;
            --text: #f5f6fa;
            --border: #2d3436;
        }

        body {
            margin: 0; font-family: 'Poppins', sans-serif;
            background: var(--bg); color: var(--text);
            transition: all 0.3s ease; padding: 20px;
        }

        .container { max-width: 1200px; margin: auto; }

        /* HEADER */
        .header-box {
            background: var(--card); padding: 25px; border-radius: 20px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 25px;
        }

        .header-info h2 { margin: 0; color: var(--primary); }
        
        .header-actions { display: flex; gap: 10px; align-items: center; }

        /* STATS */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 25px;
        }
        .stat-card {
            background: var(--card); padding: 20px; border-radius: 15px;
            text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-bottom: 4px solid var(--primary);
        }
        .stat-card i { font-size: 24px; color: var(--primary); margin-bottom: 10px; }
        .stat-card div { font-size: 24px; font-weight: 700; }

        /* FORMLAR VE KARTLAR */
        .content-card {
            background: var(--card); padding: 25px; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 25px;
        }

        .section-title { margin-top: 0; display: flex; align-items: center; gap: 10px; }

        .menu-form {
            display: grid; grid-template-columns: 2fr 1fr 1.5fr 1fr;
            gap: 15px; align-items: end; margin-bottom: 30px;
        }

        input, button {
            padding: 12px; border-radius: 12px; border: 1px solid var(--border);
            background: var(--bg); color: var(--text); font-family: inherit;
        }

        button.btn-add {
            background: var(--primary); color: white; border: none;
            cursor: pointer; font-weight: 600; transition: 0.3s;
        }
        button.btn-add:hover { transform: translateY(-2px); opacity: 0.9; }

        /* TABLOLAR */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 15px; color: #888; font-size: 13px; border-bottom: 2px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid var(--border); }

        .menu-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; }

        /* DURUM ÇİPLERİ */
        .chip {
            padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
            color: white; text-transform: uppercase;
        }
        .st-pending { background: #f1c40f; }
        .st-preparing { background: #3498db; }
        .st-onway { background: #e67e22; }
        .st-delivered { background: #2ecc71; opacity: 0.7; }

        /* AKSİYON BUTONLARI */
        .action-btn {
            text-decoration: none; padding: 7px 12px; border-radius: 8px;
            font-size: 12px; font-weight: 600; transition: 0.2s;
        }
        .btn-prep { background: #3498db; color: white; }
        .btn-ship { background: #e67e22; color: white; }
        .btn-disabled { background: #eee; color: #999; cursor: not-allowed; pointer-events: none; }

        .theme-toggle {
            background: var(--border); border: none; padding: 10px;
            border-radius: 50%; cursor: pointer; color: var(--text);
        }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #ff7675; color: white; }
        .alert-success { background: #55efc4; color: #000; }
    </style>
</head>
<body class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<div class="container">
    <div class="header-box">
        <div class="header-info">
            <h2><i class="fa-solid fa-utensils"></i> <?= htmlspecialchars($restaurant["restaurant_name"]); ?></h2>
            <p style="margin:5px 0 0; color: #888; font-size:14px;"><?= htmlspecialchars($restaurant["description"]); ?></p>
        </div>
        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()"><i class="fa-solid fa-moon"></i></button>
            <a href="restaurant_create.php" style="color:var(--primary); text-decoration:none; font-weight:600; font-size:14px;">Bilgileri Düzenle</a>
            <a href="../logout.php" style="background: #ff7675; color:white; padding:10px 18px; border-radius:12px; text-decoration:none; font-size:13px; font-weight:700;">Çıkış</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fa-solid fa-list-check"></i>
            <p style="margin:0; font-size:12px; color:#888;">Toplam Sipariş</p>
            <div><?= $total_orders ?></div>
        </div>
        <div class="stat-card" style="border-color: #f1c40f;">
            <i class="fa-solid fa-clock" style="color:#f1c40f;"></i>
            <p style="margin:0; font-size:12px; color:#888;">Aktif İşlemler</p>
            <div><?= $active_orders ?></div>
        </div>
        <div class="stat-card" style="border-color: var(--secondary);">
            <i class="fa-solid fa-bowl-food" style="color:var(--secondary);"></i>
            <p style="margin:0; font-size:12px; color:#888;">Menü Çeşidi</p>
            <div><?= mysqli_num_rows($menus) ?></div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e) echo "<div>• " . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="content-card">
        <h3 class="section-title"><i class="fa-solid fa-plus-circle" style="color:var(--primary)"></i> Menü Yönetimi</h3>
        <form class="menu-form" method="post" enctype="multipart/form-data">
            <div style="display:flex; flex-direction:column; gap:5px;">
                <label style="font-size:12px; font-weight:600;">Yemek Adı</label>
                <input type="text" name="item_name" placeholder="Örn: Lahmacun" required>
            </div>
            <div style="display:flex; flex-direction:column; gap:5px;">
                <label style="font-size:12px; font-weight:600;">Fiyat (₺)</label>
                <input type="number" name="price" step="0.01" placeholder="0.00" required>
            </div>
            <div style="display:flex; flex-direction:column; gap:5px;">
                <label style="font-size:12px; font-weight:600;">Görsel</label>
                <input type="file" name="image" accept="image/*">
            </div>
            <button type="submit" class="btn-add">Ekle</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Görsel</th>
                    <th>Yemek</th>
                    <th>Fiyat</th>
                    <th style="text-align:right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = mysqli_fetch_assoc($menus)): ?>
                <tr>
                    <td>
                        <img class="menu-img" src="<?= $m["image"] ? 'uploads/'.htmlspecialchars($m["image"]) : 'https://via.placeholder.com/50' ?>">
                    </td>
                    <td style="font-weight:600;"><?= htmlspecialchars($m["item_name"]); ?></td>
                    <td><?= number_format($m["price"], 2, ",", "."); ?> ₺</td>
                    <td style="text-align:right;">
                        <a href="?delete=<?= (int)$m["id"]; ?>" onclick="return confirm('Silsin mi?');" style="color:#ff7675; text-decoration:none;"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="content-card" id="orders">
        <h3 class="section-title"><i class="fa-solid fa-receipt" style="color:var(--primary)"></i> Gelen Siparişler</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Müşteri</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th style="text-align:right;">Yönet</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($o = mysqli_fetch_assoc($orders)): 
                    $curr = $o["Status"];
                    $chipClass = "st-pending"; $stText = "Bekliyor";
                    if ($curr === "preparing") { $chipClass = "st-preparing"; $stText = "Hazırda"; }
                    elseif ($curr === "on_the_way") { $chipClass = "st-onway"; $stText = "Kuryede"; }
                    elseif ($curr === "delivered") { $chipClass = "st-delivered"; $stText = "Bitti"; }
                ?>
                <tr>
                    <td style="font-size:12px; color:#888;">#<?= $o["OrderID"] ?></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($o["fullname"]) ?></div>
                        <div style="font-size:11px; color:#888;"><?= htmlspecialchars($o["Address"]) ?></div>
                    </td>
                    <td style="font-weight:700; color:var(--secondary);"><?= number_format($o["Total"], 2, ",", "."); ?> ₺</td>
                    <td><span class="chip <?= $chipClass ?>"><?= $stText ?></span></td>
                    <td style="text-align:right;">
                        <?php if ($curr === 'pending'): ?>
                            <a href="?order=<?= $o["OrderID"] ?>&status=preparing" class="action-btn btn-prep">Onayla</a>
                        <?php elseif ($curr === 'preparing'): ?>
                            <a href="?order=<?= $o["OrderID"] ?>&status=on_the_way" class="action-btn btn-ship">Kuryeye Ver</a>
                        <?php else: ?>
                            <span style="font-size:11px; color:#888;">Tamamlandı</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleTheme() {
        const body = document.body;
        const isDark = body.classList.toggle('dark-mode');
        const icon = document.querySelector('.theme-toggle i');
        
        icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        document.cookie = "theme=" + (isDark ? 'dark' : 'light') + ";path=/;max-age=" + (30*24*60*60);
    }
</script>
</body>
</html>