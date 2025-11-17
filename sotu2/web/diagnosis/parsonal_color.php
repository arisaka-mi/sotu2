<?php
session_start();

// 初回ロード時：スコア初期化
if (!isset($_SESSION['pc_score'])) {
    $_SESSION['pc_score'] = [
        "spring" => 0,
        "summer" => 0,
        "autumn" => 0,
        "winter" => 0
    ];
}

// 回答の加算処理
if (isset($_POST['add'])) {
    $_SESSION['pc_score'][$_POST['add']]++;
}

$q = isset($_POST['q']) ? (int)$_POST['q'] : 1;


// -----------------------------------
// 全質問回答後：結果ページへ
// -----------------------------------
if ($q > 6) {

    $score = $_SESSION['pc_score'];
    session_destroy();

    // 高い順に並び替え
    arsort($score);

    // 上位2つを取り出し
    $types = array_keys($score);
    $firstType = $types[0];
    $secondType = $types[1];

    $firstScore = $score[$firstType];
    $secondScore = $score[$secondType];

    // 日本語名
    $typeName = [
        "spring" => "春（スプリング）",
        "summer" => "夏（サマー）",
        "autumn" => "秋（オータム）",
        "winter" => "冬（ウィンター）"
    ];

    // 割合計算
    $total = $firstScore + $secondScore;
    if ($total == 0) $total = 1; // 念のためゼロ除算対策

    $firstPercent = round($firstScore / $total * 100);
    $secondPercent = 100 - $firstPercent;
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
    <meta charset="UTF-8">
        <title>PC診断結果</title>
        <style>
                body{font-family:sans-serif; line-height:1.8; padding:20px;}
                h1{margin-bottom:10px;}
                .percBox{
                    margin-top:20px;
                    padding:10px 15px;
                    background:#f6f6f6;
                    border-radius:8px;
                }
        </style>
    </head>
    <body>

        <h1>PC診断 結果</h1>

        <p style="font-size:1.3em; font-weight:bold;">
            あなたの1stタイプは  
            <span style="color:#d14;"><?= $typeName[$firstType] ?></span> です。
        </p>

        <div class="percBox">
            <p>
                ■ 1st：<b><?= $typeName[$firstType] ?></b> … <?= $firstPercent ?>%<br>
                ■ 2nd：<b><?= $typeName[$secondType] ?></b> … <?= $secondPercent ?>%
            </p>
        </div>

        <a href="pc.php">もう一度診断する</a>

    </body>
</html>

<?php exit; } ?>



<?php
// -----------------------------
// 質問内容
// -----------------------------
$questions = [

    1 => [
        "text" => "Q1. 肌の印象は？",
        "choices" => [
            "明るく黄みより" => "spring",
            "明るく青みより" => "summer",
            "落ち着いた黄み" => "autumn",
            "白くツヤ＋コントラスト強め" => "winter",
        ]
    ],

    2 => [
        "text" => "Q2. 瞳の印象は？",
        "choices" => [
            "明るくやわらかい茶色" => "spring",
            "落ち着いた茶色～明るめブラウン" => "summer",
            "深みのある茶色" => "autumn",
            "黒目がはっきり・コントラスト強め" => "winter",
        ]
    ],

    3 => [
        "text" => "Q3. 髪の色は？",
        "choices" => [
            "明るめブラウン / ゴールド系" => "spring",
            "柔らか暗めブラウン" => "summer",
            "赤みのあるブラウン" => "autumn",
            "黒髪 / ダークブラウン" => "winter",
        ]
    ],

    4 => [
        "text" => "Q4. 顔の印象は？",
        "choices" => [
            "かわいく可愛らしい" => "spring",
            "やわらかく上品" => "summer",
            "落ち着いた大人っぽい" => "autumn",
            "はっきりシャープ" => "winter",
        ]
    ],

    5 => [
        "text" => "Q5. 似合う色の傾向は？",
        "choices" => [
            "明るく澄んだ色" => "spring",
            "ソフトでくすみのある色" => "summer",
            "深みのある黄みの色" => "autumn",
            "はっきりコントラスト強め" => "winter",
        ]
    ],

    6 => [
        "text" => "Q6. アクセサリー・服の色でしっくりくるのは？",
        "choices" => [
            "ゴールド × 明るい色" => "spring",
            "シルバー × 柔らかい色" => "summer",
            "ゴールド × アースカラー" => "autumn",
            "シルバー × モノトーン" => "winter",
        ]
    ],

];
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
    <meta charset="UTF-8">
        <title>PC診断</title>
        <style>
        button{
            display:block;
            width:100%;
            padding:12px;
            margin:10px 0;
            font-size:16px;
        }
        </style>
    </head>
    <body>

        <h1><?= $questions[$q]["text"] ?></h1>

        <?php foreach ($questions[$q]["choices"] as $label => $addScore): ?>
        <form method="post">
            <input type="hidden" name="q" value="<?= $q + 1 ?>">
            <input type="hidden" name="add" value="<?= $addScore ?>">
            <button type="submit"><?= $label ?></button>
        </form>
        <?php endforeach; ?>

    </body>
</html>
