<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Yetkisiz erişim");
}

require_once "../db.php";
require_once "ai_sql.php";

$sql = "";
$result = null;

/* AI'den SQL al */
if (isset($_POST['ask_ai'])) {
    $prompt = trim($_POST['prompt']);
    $sql = ask_ai_for_sql($prompt);

    if (!isset($_SESSION['ai_history'])) {
        $_SESSION['ai_history'] = [];
    }
    array_unshift($_SESSION['ai_history'], [
        'prompt' => $prompt,
        'sql'    => $sql,
        'time'   => date('H:i')
    ]);
    $_SESSION['ai_history'] = array_slice($_SESSION['ai_history'], 0, 5);
}

/* SQL çalıştır */
if (isset($_POST['run_sql'])) {
    $sql = trim($_POST['sql_text']); // Boşlukları temizle
    
    // İlk kelimeyi yakalamak için daha sağlam bir yöntem
    $first_word = "";
    if (preg_match('/^\s*([a-z]+)/i', $sql, $matches)) {
        $first_word = strtolower($matches[1]);
    }

    // Kontrol: İlk kelime 'select' veya 'call' değilse hata ver
    if ($first_word !== 'select' && $first_word !== 'call') {
        $error_msg = "❌ Güvenlik nedeni ile sadece SELECT ve CALL sorguları çalıştırabilirsin.";
    } 
    // Tehlikeli kelimeleri engellemeye devam et
    elseif (preg_match('/\b(drop|truncate|delete)\b/i', $sql)) {
        $error_msg = "❌ Tehlikeli SQL engellendi";
    } 
    else {
        // CALL sorguları bazen birden fazla sonuç kümesi döndürebilir
        // mysqli_query tekli sonuçlar için yeterlidir, ancak procedure'den veri gelmezse 
        // mysqli_multi_query gerekebilir.
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            $error_msg = "SQL Hatası: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>AI Komuta Merkezi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --primary: #6c5ce7;
            --secondary: #00b894;
            --border: #dfe6e9;
            --input-bg: #ffffff;
        }

        body.dark-mode {
            --bg-color: #0f111a;
            --card-bg: #1a1d29;
            --text-main: #f5f6fa;
            --primary: #a29bfe;
            --secondary: #55efc4;
            --border: #2d3436;
            --input-bg: #25293a;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            padding: 40px;
            margin: 0;
            transition: all 0.3s ease;
        }

        .container {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            max-width: 1200px;
            margin: auto;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .back-link {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn {
            background: #ff7675;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
        }

        .ai-title {
            font-size: 28px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Prompt Box */
        .prompt-box {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .prompt-box input {
            flex: 1;
            padding: 16px;
            border-radius: 15px;
            border: 2px solid var(--border);
            background: var(--input-bg);
            color: var(--text-main);
            font-size: 16px;
        }

        .prompt-box button {
            padding: 16px 30px;
            background: var(--primary);
            border: none;
            border-radius: 15px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
        }

        /* Hazır Promptlar */
        .quick-prompts {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .quick-prompts button {
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text-main);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
        }

        /* SQL Editor Area */
        .sql-editor-container {
            margin-top: 30px;
            animation: fadeIn 0.5s ease;
        }

        textarea {
            width: 100%;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid var(--primary);
            background: #25293a;
            color: #55efc4;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            line-height: 1.6;
        }

        .run-btn {
            margin-top: 15px;
            padding: 15px 30px;
            background: var(--secondary);
            border: none;
            border-radius: 12px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Design */
        .results-container {
            overflow-x: auto;
            margin-top: 30px;
            background: var(--input-bg);
            border-radius: 15px;
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--primary);
            color: white;
        }

        /* History Sidebar */
        .history-box {
            width: 300px;
            background: var(--input-bg);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid var(--border);
        }

        .history-item {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 10px;
        }

        .history-item small {
            font-family: 'Fira Code', monospace;
            color: var(--secondary);
            display: block;
            margin-top: 5px;
            font-size: 11px;
        }

        .error-alert {
            background: #ff7675;
            color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .flex-layout {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        @media (max-width: 900px) {
            .flex-layout { flex-direction: column; }
            .history-box { width: 100%; }
        }
    </style>
</head>
<body class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<div class="container">
    <div class="header-row">
        <a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Komuta Merkezine Dön</a>
        <a href="logout.php" onclick="return confirm('Çıkış yapmak istiyor musun?')" class="logout-btn">Çıkış Yap</a>
    </div>

    <h2 class="ai-title"><i class="fa-solid fa-robot"></i> AI SQL Komuta Merkezi</h2>

    <?php if (isset($error_msg)): ?>
        <div class="error-alert"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="post" class="prompt-box">
        <input id="promptInput" name="prompt" value="<?= htmlspecialchars($_POST['prompt'] ?? '') ?>" 
               placeholder="Örn: En çok sipariş veren 5 müşteriyi harcamalarıyla getir..." required>
        <button name="ask_ai"><i class="fa-solid fa-wand-magic-sparkles"></i> AI'ye Sor</button>
    </form>

    <div class="quick-prompts">
        <button type="button" onclick="setPrompt('adı ali olan müşterileri getir')">Ali isimli müşteriler</button>
        <button type="button" onclick="setPrompt('son 5 siparişi restoran adıyla birlikte getir')">Son 5 Sipariş</button>
        <button type="button" onclick="setPrompt('restoranların ortalama puanlarını listele')">Restoran Puanları</button>
        <button type="button" onclick="setPrompt('bugün gelen toplam sipariş tutarı nedir')">Günlük Ciro</button>
    </div>

    <div class="flex-layout">
        <div style="flex:1; width: 100%;">
            <?php if ($sql): ?>
            <div class="sql-editor-container">
                <h3 style="margin-bottom:10px;"><i class="fa-solid fa-code"></i> Üretilen Sorgu</h3>
                <form method="post">
                    <textarea name="sql_text" rows="6"><?= htmlspecialchars($sql) ?></textarea>
                    <button name="run_sql" class="run-btn" onclick="return confirm('Sorgu çalıştırılsın mı?')">
                        <i class="fa-solid fa-play"></i> SORGULAMAYI BAŞLAT
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($result && $result instanceof mysqli_result): ?>
            <div class="results-container">
                <h3><i class="fa-solid fa-table-list"></i> Veri Çıktısı (<?= mysqli_num_rows($result); ?> Satır)</h3>
                <table>
                    <thead>
                        <tr>
                            <?php while ($f = mysqli_fetch_field($result)): ?>
                                <th><?= $f->name ?></th>
                            <?php endwhile; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <?php foreach ($r as $v): ?>
                                <td><?= htmlspecialchars($v) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="history-box">
            <h4 style="margin-top:0;"><i class="fa-solid fa-clock-rotate-left"></i> Son Sorgular</h4>
            <?php if (!empty($_SESSION['ai_history'])): ?>
                <?php foreach ($_SESSION['ai_history'] as $h): ?>
                    <div class="history-item">
                        <strong style="font-size:13px;"><?= htmlspecialchars($h['prompt']) ?></strong>
                        <small><?= htmlspecialchars(substr($h['sql'], 0, 80)) ?>...</small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size:12px; opacity:0.6;">Henüz işlem yapılmadı.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function setPrompt(text) {
    document.getElementById('promptInput').value = text;
}

// Admin panelindeki cookie bazlı tema kontrolü
const currentTheme = document.cookie.split('; ').find(row => row.startsWith('theme='))?.split('=')[1];
if (currentTheme === 'dark') {
    document.body.classList.add('dark-mode');
}
</script>

</body>
</html>