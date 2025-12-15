<?php
session_start();

if (!isset($_SESSION['pc_score'])) {
    echo "診断データがありません。";
    exit;
}

$score = $_SESSION['pc_score'];

arsort($score);
$types = array_keys($score);
$firstType = $types[0];
$secondType = $types[1];

$firstScore = $score[$firstType];
$secondScore = $score[$secondType];
$total = $firstScore + $secondScore;
if ($total == 0) $total = 1;

$firstPercent = round($firstScore / $total * 100);
$secondPercent = 100 - $firstPercent;

$typeName = [
    "spring"=>"春（スプリング）",
    "summer"=>"夏（サマー）",
    "autumn"=>"秋（オータム）",
    "winter"=>"冬（ウィンター）"
];

/* 説明文（骨格の comboDetail と同じ役割） */
$pcDetail = [
    "spring" => "明るく黄み寄りの色が得意。コーラル・アイボリー・明るいベージュが肌を健康的に見せます。",
    "summer" => "やわらかく青み寄りの色が得意。ラベンダー・グレー・ローズ系で上品な印象に。",
    "autumn" => "深みと黄みのある色が得意。カーキ・ブラウン・テラコッタで大人っぽく。",
    "winter" => "コントラストの強い色が得意。ブラック・ネイビー・鮮やかな赤でシャープに。"
];

$description = $pcDetail[$firstType];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>パーソナルカラー診断 結果</title>

<style>
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

/* ボタン（骨格と完全一致） */
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
a.button1:hover {
    background: #FF1493;
}

a.button2 {
    display: inline-block;
    margin-top: 20px;
    padding: 14px 35px;
    font-size: 16px;
    background: #ff6973ff;
    color: #fff;
    text-decoration: none;
    border-radius: 18px;
    transition: 0.2s;
}
a.button2:hover {
    background: #ff1427ff;
}

@media(max-width:768px){
    .result-container {
        padding: 40px 20px;
        width: 95%;
    }
}
</style>
</head>

<body>

<div class="result-container">
    <h1>パーソナルカラー診断</h1>

    <p class="result">◆ 1stタイプ：<?= $typeName[$firstType] ?></p>
    <p class="result">◆ 2ndタイプ：<?= $typeName[$secondType] ?></p>

    <p class="result">
        あなたのタイプ：<?= $typeName[$firstType] ?> × <?= $typeName[$secondType] ?>
    </p>

    <p class="description">
        <?= nl2br($description) ?>
    </p>

    <a href="parsonal_color.php" class="button1">もう一度診断する</a>

    <form action="save_personal_color.php" method="post">
        <input type="hidden" name="first" value="<?= $firstType ?>">
        <input type="hidden" name="second" value="<?= $secondType ?>">
        <a href="save_personal_color.php" class="button2"
           onclick="this.closest('form').submit(); return false;">
           プロフィールに保存する
        </a>
    </form>
</div>

</body>
</html>
