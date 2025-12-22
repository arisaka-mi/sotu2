<?php
session_start();
require_once(__DIR__ . '/../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login_form.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT upper_bt, lower_bt
    FROM user
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$upperType = $user['upper_bt'] ?? '未設定';
$lowerType = $user['lower_bt'] ?? '未設定';

/* =========================
   組み合わせ & 説明文
========================= */
$combo = $upperType . "（上半身） × " . $lowerType . "（下半身）";

$comboDetail = [
    "ストレート×ストレート" => "全体メリハリ型。重心が上寄り〜均等の王道スタイル。",
    "ストレート×ウェーブ"   => "上重心・下細のAライン型。ウエストを絞ると映えるタイプ。",
    "ストレート×ナチュラル" => "上半身しっかり・下半身スラっと。縦ラインを強調すると◎",
    "ウェーブ×ストレート"   => "上半身華奢 × 下半身しっかり。下重心のHライン型。",
    "ウェーブ×ウェーブ"     => "全体的に華奢〜下重心。フェミニン・柔らかい印象。",
    "ウェーブ×ナチュラル"   => "上半身華奢 × 下半身スラリ。Iラインを意識すると綺麗。",
    "ナチュラル×ストレート" => "上半身骨格しっかり × 下半身立体型。ボーイッシュ寄り。",
    "ナチュラル×ウェーブ"   => "上半身直線 × 下半身しっかり。下重心のAライン寄り。",
    "ナチュラル×ナチュラル" => "全体的に直線的でスタイリッシュ。ゆるシルエットが似合う。",
];

$key = $upperType . "×" . $lowerType;
$description = $comboDetail[$key] ?? "";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>骨格診断 詳細</title>
<style>
/* ===== body_ans.php と同一デザイン ===== */
body {
    font-family: sans-serif;
    background: #FFC0CB;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 0;
}

.result-container {
    background: #fff;
    max-width: 900px;
    width: 95%;
    padding: 60px 50px;
    border-radius: 30px;
    box-sizing: border-box;
    text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.15);
}

h1 {
    margin-bottom: 25px;
}

.result {
    font-size: 1.4em;
    font-weight: bold;
    margin: 18px 0;
}

.description {
    font-size: 1.15em;
    line-height: 1.7;
    margin-top: 25px;
}

a.button1 {
    display: inline-block;
    margin-top: 35px;
    padding: 14px 35px;
    font-size: 16px;
    background: #FF69B4;
    color: #fff;
    text-decoration: none;
    border-radius: 18px;
    transition: 0.2s;
    width: 175px;
}
</style>
</head>
<body>

<div class="result-container">
    <h1>骨格診断（上半身 × 下半身）</h1>

    <p class="result">◆ 上半身：<?= htmlspecialchars($upperType) ?></p>
    <p class="result">◆ 下半身：<?= htmlspecialchars($lowerType) ?></p>

    <p class="result">あなたのタイプ：<?= htmlspecialchars($combo) ?></p>

    <p class="description">
        <?= nl2br(htmlspecialchars($description)) ?>
    </p>

    <a href="../profile/profile_setting.php" class="button1">
        プロフィールに戻る
    </a>
</div>

</body>
</html>
