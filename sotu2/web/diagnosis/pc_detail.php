<?php
session_start();
require_once(__DIR__ . '/../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login_form.php");
    exit();
}

/* =========================
   ユーザーの PC情報取得
========================= */
$stmt = $pdo->prepare("
    SELECT pc_id, pc_second_id
    FROM user
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['pc_id'] || !$user['pc_second_id']) {
    echo "パーソナルカラー診断がまだ保存されていません。";
    exit;
}

/* =========================
   pc_id → pc_name
========================= */
$stmt = $pdo->prepare("
    SELECT pc_id, pc_name
    FROM parsonal_color
    WHERE pc_id IN (?, ?)
");
$stmt->execute([$user['pc_id'], $user['pc_second_id']]);
$pcs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* =========================
   表示用変換
========================= */
$pcReverseMap = [
    "イエベ春" => "spring",
    "ブルべ夏" => "summer",
    "イエベ秋" => "autumn",
    "ブルべ冬" => "winter"
];

$firstType  = $pcReverseMap[$pcs[$user['pc_id']]];
$secondType = $pcReverseMap[$pcs[$user['pc_second_id']]];

$typeName = [
    "spring"=>"イエベ春（スプリング）",
    "summer"=>"ブルべ夏（サマー）",
    "autumn"=>"イエベ秋（オータム）",
    "winter"=>"ブルべ冬（ウィンター）"
];

$pcDetail = [
    "spring" => "明るく黄み寄りの色が得意。コーラル・アイボリー・明るいベージュが肌を健康的に見せます。",
    "summer" => "やわらかく青み寄りの色が得意。ラベンダー・グレー・ローズ系で上品な印象に。",
    "autumn" => "深みと黄みのある色が得意。カーキ・ブラウン・テラコッタで大人っぽく。",
    "winter" => "コントラストの強い色が得意。ブラック・ネイビー・鮮やかな赤でシャープに。"
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>パーソナルカラー診断 詳細</title>
<style>
/* pc_ans.php と完全一致 */
body {
    font-family: sans-serif;
    background: #FFC0CB;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
.result-container {
    background: #fff;
    max-width: 900px;
    width: 95%;
    padding: 60px 50px;
    border-radius: 30px;
    text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.15);
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
    background: #FF69B4;
    color: #fff;
    border-radius: 18px;
    text-decoration: none;
}
</style>
</head>
<body>

<div class="result-container">
    <h1>パーソナルカラー診断</h1>

    <p class="result">◆ 1stタイプ：<?= $typeName[$firstType] ?></p>
    <p class="result">◆ 2ndタイプ：<?= $typeName[$secondType] ?></p>

    <p class="result">
        あなたのタイプ：
        <?= $typeName[$firstType] ?> × <?= $typeName[$secondType] ?>
    </p>

    <p class="description">
        <?= nl2br($pcDetail[$firstType]) ?>
    </p>

    <a href="../profile/profile_setting.php" class="button1">
        プロフィールに戻る
    </a>
</div>

</body>
</html>
