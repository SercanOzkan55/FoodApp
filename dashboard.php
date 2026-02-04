<?php
session_start();
include "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$tab = $_GET['tab'] ?? 'customers';

// İstatistikler
$customer_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='customer'"))[0];
$seller_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='seller'"))[0];

$order_count = 0;
if (mysqli_query($conn, "SHOW TABLES LIKE 'orders'")->num_rows) {
    $order_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders"))[0];
}

// Listeler
$customers = mysqli_query($conn, "SELECT * FROM users WHERE role='customer' ORDER BY id DESC");
$sellers   = mysqli_query($conn, "SELECT * FROM users WHERE role='seller' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - FoodApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #f4f6f8;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-sub: #636e72;
            --primary: #6c5ce7;
            --secondary: #00b894;
            --danger: #d63031;
            --border: #dfe6e9;
        }

        body.dark-mode {
            --bg-color: #0f111a;
            --card-bg: #1a1d29;
            --text-main: #f5f6fa;
            --text-sub: #a4b0be;
            --border: #2d3436;
        }

        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; }

        body { background: var(--bg-color); margin: 0; padding: 40px; color: var(--text-main); }

        .container { max-width: 1200px; margin: auto; }

        /* HEADER */
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; background: var(--card-bg); padding: 20px;
            border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .topbar-title { font-size: 24px; font-weight: 700; color: var(--primary); }

        .header-actions { display: flex; align-items: center; gap: 15px; }

        .theme-btn {
            background: var(--bg-color); border: none; padding: 10px;
            border-radius: 10px; cursor: pointer; color: var(--text-main); font-size: 18px;
        }

        .logout-btn {
            background: var(--danger); color: white; padding: 10px 20px;
            border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px;
        }

        /* İSTATİSTİK KARTLARI */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .stat-box {
            background: var(--card-bg); padding: 25px; border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-bottom: 4px solid var(--primary);
            display: flex; align-items: center; gap: 20px;
        }

        .stat-box i { font-size: 35px; color: var(--primary); opacity: 0.8; }
        .stat-label { font-size: 14px; color: var(--text-sub); }
        .stat-value { font-size: 28px; font-weight: 700; }

        /* AKSİYON BUTONLARI */
        .action-row { display: flex; gap: 15px; margin-bottom: 25px; }
        
        .tab-btn {
            flex: 1; padding: 15px; border-radius: 12px; text-decoration: none;
            text-align: center; font-weight: 600; color: white; display: flex;
            align-items: center; justify-content: center; gap: 10px;
        }

        .ai-panel-btn {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            width: 100%; margin-bottom: 30px; padding: 20px; font-size: 18px;
            box-shadow: 0 10px 20px rgba(108, 92, 231, 0.2);
        }

        /* TABLOLAR */
        .card { background: var(--card-bg); padding: 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }

        .search-box input {
            width: 100%; padding: 12px 20px; border-radius: 10px;
            border: 1px solid var(--border); background: var(--bg-color);
            color: var(--text-main); margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-sub); font-size: 13px; border-bottom: 2px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 14px; }

        .delete-btn { color: var(--danger); font-size: 18px; }
        .delete-btn:hover { transform: scale(1.2); }

        /* FORM */
        .inline-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) 100px; gap: 10px; margin-bottom: 20px; }
        .inline-form input { padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-color); color: var(--text-main); }
        .inline-form button { background: var(--secondary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* ANIMASYON */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card { animation: fadeIn 0.5s ease; }
    </style>
</head>
<body class="<?= $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">

<div class="container">
    <div class="topbar">
        <div class="topbar-title"><i class="fa-solid fa-gauge-high"></i> FoodApp Admin</div>
        <div class="header-actions">
            <button class="theme-btn" onclick="toggleTheme()">
                <i class="fa-solid <?= $_COOKIE['theme'] === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
            </button>
            <span>👋 <?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Çıkış</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <i class="fa-solid fa-users"></i>
            <div>
                <div class="stat-label">Toplam Müşteri</div>
                <div class="stat-value"><?= $customer_count; ?></div>
            </div>
        </div>
        <div class="stat-box" style="border-color: var(--secondary);">
            <i class="fa-solid fa-store" style="color: var(--secondary);"></i>
            <div>
                <div class="stat-label">Toplam Satıcı</div>
                <div class="stat-value"><?= $seller_count; ?></div>
            </div>
        </div>
        <div class="stat-box" style="border-color: #f1c40f;">
            <i class="fa-solid fa-motorcycle" style="color: #f1c40f;"></i>
            <div>
                <div class="stat-label">Toplam Sipariş</div>
                <div class="stat-value"><?= $order_count; ?></div>
            </div>
        </div>
    </div>

    <a href="ai_panel.php" class="tab-btn ai-panel-btn">
        <i class="fa-solid fa-robot"></i> AI SQL Komuta Merkezi
    </a>

    <div class="action-row">
        <a href="?tab=customers" class="tab-btn" style="background: #3498db; opacity: <?= $tab==='customers' ? '1' : '0.6' ?>">
            <i class="fa-solid fa-user-tag"></i> Müşterileri Yönet
        </a>
        <a href="?tab=sellers" class="tab-btn" style="background: #2ecc71; opacity: <?= $tab==='sellers' ? '1' : '0.6' ?>">
            <i class="fa-solid fa-shop"></i> Satıcıları Yönet
        </a>
    </div>

    <div class="card">
        <h3><?= $tab === 'customers' ? 'Müşteri Listesi' : 'Satıcı Listesi' ?></h3>

        <form method="POST" action="user_action.php" class="inline-form">
            <input type="hidden" name="role" value="<?= $tab === 'customers' ? 'customer' : 'seller' ?>">
            <input name="fullname" placeholder="Ad Soyad" required>
            <input name="email" type="email" placeholder="Email Adresi" required>
            <input name="password" type="password" placeholder="Şifre" required>
            <button name="add"><i class="fa-solid fa-plus"></i> Ekle</button>
        </form>

        <div class="search-box">
            <input type="text" id="liveSearch" placeholder="Listede ara..." onkeyup="filterTable()">
        </div>

        <table id="mainTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Email</th>
                    <th style="text-align: center;">Eylem</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $active_list = ($tab === 'customers') ? $customers : $sellers;
                while ($u = mysqli_fetch_assoc($active_list)): 
                ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td style="text-align: center;">
                        <a class="delete-btn" href="user_action.php?delete=<?= $u['id'] ?>" onclick="return confirm('Silinsin mi?')">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Tema Değiştirme
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-mode');
    const icon = document.querySelector('.theme-btn i');
    
    icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    document.cookie = "theme=" + (isDark ? 'dark' : 'light') + ";path=/";
}

// Canlı Arama
function filterTable() {
    const input = document.getElementById("liveSearch");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("mainTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let textContent = tr[i].textContent.toLowerCase();
        tr[i].style.display = textContent.includes(filter) ? "" : "none";
    }
}
</script>

</body>
</html>