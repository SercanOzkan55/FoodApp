<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "seller") {
    header("Location: ../login.php");
    exit;
}

include "../db.php";

$seller_id = (int)$_SESSION["user_id"];
$errors = [];

// Var olan restoran bilgilerini çek
$restaurant_name = "";
$description     = "";
$current_logo    = "";

$check = mysqli_query($conn, "SELECT * FROM restaurants WHERE seller_id = '$seller_id' LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    $rest            = mysqli_fetch_assoc($check);
    $restaurant_name = $rest["restaurant_name"];
    $description     = $rest["description"];
    $current_logo    = $rest["logo"];
}

// FORM GÖNDERİLDİ
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $restaurant_name = trim($_POST["restaurant_name"] ?? "");
    $description     = trim($_POST["description"] ?? "");

    if ($restaurant_name === "") {
        $errors[] = "Restoran adı zorunludur.";
    }

    $logoFileName = $current_logo;
    if (!empty($_FILES["logo"]["name"])) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
        $logoFileName = time() . "_" . rand(1000, 9999) . "." . $ext;
        $targetPath   = $uploadDir . $logoFileName;

        if (!move_uploaded_file($_FILES["logo"]["tmp_name"], $targetPath)) {
            $errors[] = "Logo yüklenirken bir hata oluştu.";
        }
    }

    if (empty($errors)) {
        $check = mysqli_query($conn, "SELECT id FROM restaurants WHERE seller_id = '$seller_id' LIMIT 1");

        if (mysqli_num_rows($check) == 0) {
            $stmt = mysqli_prepare($conn, "INSERT INTO restaurants (seller_id, restaurant_name, description, logo) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isss", $seller_id, $restaurant_name, $description, $logoFileName);
            mysqli_stmt_execute($stmt);
        } else {
            $row = mysqli_fetch_assoc($check);
            $rid = (int)$row["id"];
            $stmt = mysqli_prepare($conn, "UPDATE restaurants SET restaurant_name = ?, description = ?, logo = ? WHERE id = ? AND seller_id = ?");
            mysqli_stmt_bind_param($stmt, "sssii", $restaurant_name, $description, $logoFileName, $rid, $seller_id);
            mysqli_stmt_execute($stmt);
        }
        header("Location: menu_add.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Restoran Bilgileri - FoodApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f2f5;
            --card: #ffffff;
            --text: #2d3436;
            --primary: #6c5ce7;
            --border: #dfe6e9;
            --input-bg: #ffffff;
        }

        body.dark-mode {
            --bg: #0f111a;
            --card: #1a1d29;
            --text: #f5f6fa;
            --border: #2d3436;
            --input-bg: #25293a;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }

        .box {
            background: var(--card);
            width: 480px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            position: relative;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            color: var(--primary);
        }

        .field { margin-bottom: 20px; }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .field input[type="text"],
        .field textarea {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 2px solid var(--border);
            background: var(--input-bg);
            color: var(--text);
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }

        .field input:focus, .field textarea:focus {
            border-color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 8px 15px rgba(108, 92, 231, 0.3);
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(108, 92, 231, 0.4);
        }

        .errors {
            background: #ff7675;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            font-size: 14px;
        }

        .logo-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            padding: 10px;
            background: var(--bg);
            border-radius: 12px;
        }

        .logo-preview img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
        }

        .top-link {
            position: absolute;
            top: 20px;
            left: 25px;
        }

        .top-link a {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Tema Değiştirici */
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 25px;
            background: var(--border);
            border: none;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text);
        }
    </style>
</head>
<body class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<div class="box">
    <div class="top-link">
        <a href="menu_add.php"><i class="fa-solid fa-arrow-left"></i> Panele Dön</a>
    </div>

    <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fa-solid <?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
    </button>

    <h2>Restoran Profilini Düzenle</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e) echo "<div><i class='fa-solid fa-circle-xmark'></i> " . htmlspecialchars($e) . "</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="field">
            <label><i class="fa-solid fa-store"></i> Restoran Adı</label>
            <input type="text" name="restaurant_name" placeholder="Örn: Lezzet Durağı"
                   value="<?php echo htmlspecialchars($restaurant_name); ?>" required>
        </div>

        <div class="field">
            <label><i class="fa-solid fa-align-left"></i> Restoran Açıklaması</label>
            <textarea name="description" rows="3" placeholder="Müşterilerinize restoranınızı tanıtın..."><?php
                echo htmlspecialchars($description);
            ?></textarea>
        </div>

        <div class="field">
            <label><i class="fa-solid fa-image"></i> Restoran Logosu</label>
            <input type="file" name="logo" accept="image/*">
            <?php if ($current_logo): ?>
                <div class="logo-preview">
                    <img src="uploads/<?php echo htmlspecialchars($current_logo); ?>" alt="Logo">
                    <small>Mevcut logoyu değiştirmek için yeni bir dosya seçin.</small>
                </div>
            <?php endif; ?>
        </div>

        <button class="btn" type="submit">
            <i class="fa-solid fa-floppy-disk"></i> Ayarları Kaydet ve Devam Et
        </button>
    </form>
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