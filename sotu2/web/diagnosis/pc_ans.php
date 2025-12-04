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
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>パーソナルカラー診断 結果</title>
<style>
body { font-family:sans-serif; background:#FFC0CB; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
.result-container { background:#fff; max-width:900px; width:95%; padding:60px 50px; border-radius:30px; box-sizing:border-box; text-align:center; box-shadow:0 0 30px rgba(0,0,0,0.15);}
h2 { margin-bottom:25px; }
.result { font-size:1.3em; font-weight:bold; margin:18px 0; }
.box { background:#fafafa; padding:20px; border-radius:15px; margin-top:20px; font-size:1.1em; }
a.button { display:inline-block; margin-top:35px; padding:14px 35px; font-size:16px; background:#FF69B4; color:#fff; text-decoration:none; border-radius:18px; transition:0.2s;}
a.button:hover { background:#FF1493; }
</style>
</head>
<body>
<div class="result-container">
    <h2>パーソナルカラー診断 結果</h2>
    <p class="result">1stタイプ：<?= $typeName[$firstType] ?></p>
    <p class="result">2ndタイプ：<?= $typeName[$secondType] ?></p>
    <div class="box">
        1st：<?= $typeName[$firstType] ?> … <b><?= $firstPercent ?>%</b><br>
        2nd：<?= $typeName[$secondType] ?> … <b><?= $secondPercent ?>%</b>
    </div>
    <a href="parsonal_color.php" class="button">もう一度診断する</a>
</div>
</body>
</html>
