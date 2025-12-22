<?php
session_start();

// ------------------------
// スコア取得
// ------------------------
if (!isset($_SESSION['score'])) {
    echo "診断データがありません。";
    exit;
}

$score = $_SESSION['score'];

// ------------------------
// 上半身判定
// ------------------------
$upper = [
    "upper_straight" => $score["upper_straight"],
    "upper_wave" => $score["upper_wave"],
    "upper_natural" => $score["upper_natural"]
];
arsort($upper);
$upperType = array_key_first($upper);

// ------------------------
// 下半身判定
// ------------------------
$lower = [
    "lower_straight" => $score["lower_straight"],
    "lower_wave" => $score["lower_wave"],
    "lower_natural" => $score["lower_natural"]
];
arsort($lower);
$lowerType = array_key_first($lower);

// ------------------------
// タイプ日本語
// ------------------------
$typeName = [
    "upper_straight" => "ストレート",
    "upper_wave" => "ウェーブ",
    "upper_natural" => "ナチュラル",
    "lower_straight" => "ストレート",
    "lower_wave" => "ウェーブ",
    "lower_natural" => "ナチュラル",
];

// ------------------------
// 組み合わせ
// ------------------------
$combo = $typeName[$upperType] . "（上半身） × " . $typeName[$lowerType] . "（下半身）";

// ------------------------
// 組み合わせごとの説明（←元のまま）
// ------------------------
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

$key = $typeName[$upperType] . "×" . $typeName[$lowerType];
$description = $comboDetail[$key] ?? "";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>骨格診断 結果</title>
<style>
/* ← ここから下、元のCSSを一切変更していません */
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
h1 { margin-bottom: 25px; }
.result { font-size: 1.4em; font-weight: bold; margin: 18px 0; }
.description { font-size: 1.15em; line-height: 1.7; margin-top: 25px; }
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
.button2 {
    display: inline-block;
    margin-top: 35px;
    padding: 14px 35px;
    font-size: 16px;
    background: #ff6973ff;
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 18px;
}
</style>
</head>
<body>
<div class="result-container">
    <h1>骨格診断（上半身 × 下半身）</h1>

    <p class="result">◆ 上半身：<?= $typeName[$upperType] ?></p>
    <p class="result">◆ 下半身：<?= $typeName[$lowerType] ?></p>

    <p class="result">あなたのタイプ：<?= $combo ?></p>
    <p class="description"><?= nl2br($description) ?></p>

    <a href="body_type.php" class="button1">もう一度診断する</a>

    <form action="save_body_type.php" method="post">
        <input type="hidden" name="upper_bt" value="<?= $typeName[$upperType] ?>">
        <input type="hidden" name="lower_bt" value="<?= $typeName[$lowerType] ?>">
        <input type="hidden" name="final_bt" value="骨格<?= $typeName[$upperType] ?>">
        <button type="submit" class="button2">プロフィールに保存する</button>
    </form>
</div>
</body>
</html>
