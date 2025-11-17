<?php
session_start();

// 初期化
if (!isset($_SESSION['pc_score'])) {
    $_SESSION['pc_score'] = [
        "spring" => 0,
        "summer" => 0,
        "autumn" => 0,
        "winter" => 0
    ];
}

$q = isset($_POST['q']) ? intval($_POST['q']) : 1;
$add = isset($_POST['add']) ? $_POST['add'] : null;

// スコア加算
if ($add) {
    $_SESSION['pc_score'][$add]++;
}

// -------------------------------
// 質問データ（16問）
// -------------------------------
$questions = [

    // 1
    [
        "q" => "あなたの肌の第一印象に近いのは？（手の甲・腕の内側で確認）",
        "a" => [
            "spring" => "黄みが強く健康的なベージュ",
            "summer" => "赤みが少なく明るいクリア肌",
            "autumn" => "黄みが落ち着いてやや深みのある肌",
            "winter" => "赤みが少なく透明感のある白肌"
        ]
    ],

    // 2
    [
        "q" => "肌の明るさは？（顔・首の境目で確認）",
        "a" => [
            "spring" => "明るくて白め",
            "summer" => "明るく透明感のある白肌",
            "autumn" => "標準〜やや暗め",
            "winter" => "白くてくっきり明暗差がある"
        ]
    ],

    // 3
    [
        "q" => "肌の質感は？",
        "a" => [
            "spring" => "ツヤ・血色感がある",
            "summer" => "ソフトでマット寄り",
            "autumn" => "落ち着いたツヤ感",
            "winter" => "クリアで光沢少なめ"
        ]
    ],

    // 4
    [
        "q" => "静脈（手首の血管）の色は？（手首で確認）",
        "a" => [
            "spring" => "緑っぽい",
            "summer" => "青〜紫",
            "autumn" => "緑っぽく深みあり",
            "winter" => "青〜紫でくっきり"
        ]
    ],

    // 5
    [
        "q" => "日焼けしたらどうなる？",
        "a" => [
            "spring" => "すぐ焼けて小麦色になる",
            "summer" => "赤くなって徐々に焼ける",
            "autumn" => "焼けると黄味寄り",
            "winter" => "赤くなりやすく、日焼けは浅め"
        ]
    ],

    // 6
    [
        "q" => "ゴールド／シルバーのどちらが肌に合う？",
        "a" => [
            "spring" => "ゴールドが元気に見える",
            "summer" => "シルバーが柔らかく見える",
            "autumn" => "ゴールドで落ち着いて見える",
            "winter" => "シルバーで肌が引き締まって見える"
        ]
    ],

    // 7
    [
        "q" => "目の色はどちらに近い？",
        "a" => [
            "spring" => "ライトブラウン・黄み寄り",
            "summer" => "明るめブラウン・赤み控えめ",
            "autumn" => "ダークブラウン・黄み寄り",
            "winter" => "ブラック〜ダークブラウン・アッシュ系"
        ]
    ],

    // 8
    [
        "q" => "目の印象は？（白目と黒目のコントラストを確認）",
        "a" => [
            "spring" => "白目はクリーム色寄り・コントラスト弱め",
            "summer" => "白目青白く・柔らかコントラスト",
            "autumn" => "白目クリーム色寄り・ややくっきり",
            "winter" => "白目青白く・コントラスト強め"
        ]
    ],

    // 9
    [
        "q" => "髪の元の色は？",
        "a" => [
            "spring" => "明るめブラウン・赤みあり",
            "summer" => "明るめブラウン・赤み控えめ",
            "autumn" => "濃いブラウン・赤み寄り",
            "winter" => "黒〜ダークブラウン・アッシュ系"
        ]
    ],

    // 10
    [
        "q" => "似合いやすい服の色は？（比べて気分が良い方）",
        "a" => [
            "spring" => "ベージュ・コーラル",
            "summer" => "パステル・グレー・ラベンダー",
            "autumn" => "キャメル・オリーブ・テラコッタ",
            "winter" => "ネイビー・ブラック・白"
        ]
    ],

    // 11
    [
        "q" => "似合うリップの傾向は？",
        "a" => [
            "spring" => "コーラル・オレンジ系",
            "summer" => "ローズ・青みピンク",
            "autumn" => "テラコッタ・ブラウン系",
            "winter" => "プラム・赤紫系"
        ]
    ],

    // 12
    [
        "q" => "コスメの質感で肌が綺麗に見えるのは？",
        "a" => [
            "spring" => "ツヤ・透明感が映える",
            "summer" => "ソフトマットでナチュラル",
            "autumn" => "落ち着いたツヤ感",
            "winter" => "透明感クリア・光沢少なめ"
        ]
    ],

    // 13
    [
        "q" => "髪色で褒められやすいのは？",
        "a" => [
            "spring" => "ベージュ・オレンジ系",
            "summer" => "アッシュ・ソフトブラウン",
            "autumn" => "赤みブラウン・キャラメル系",
            "winter" => "アッシュ・黒系"
        ]
    ],

    // 14
    [
        "q" => "しっくりくるアクセは？",
        "a" => [
            "spring" => "イエローゴールド",
            "summer" => "プラチナ・ホワイトゴールドの柔らかめ",
            "autumn" => "イエローゴールド・落ち着き系",
            "winter" => "プラチナ・ホワイトゴールド・シャープ"
        ]
    ],

    // 15
    [
        "q" => "顔のコントラスト（白黒の差）は？",
        "a" => [
            "spring" => "やわらかくぼんやり",
            "summer" => "やわらかく・くすみ少なめ",
            "autumn" => "落ち着いて明暗差やや強め",
            "winter" => "強くハッキリ"
        ]
    ],

    // 16
    [
        "q" => "明るい色 vs 暗い色、どちらが似合う？",
        "a" => [
            "spring" => "明るい色（ライトベージュ・明るいピンク）",
            "summer" => "パステル・柔らかい色",
            "autumn" => "深み・くすみ系の色",
            "winter" => "ネイビー・黒・鮮やかな色"
        ]
    ]
];

// -----------------------------------
// 結果表示
// -----------------------------------
if ($q > count($questions)) {
    $score = $_SESSION['pc_score'];
    session_destroy();

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
        "spring" => "春（スプリング）",
        "summer" => "夏（サマー）",
        "autumn" => "秋（オータム）",
        "winter" => "冬（ウィンター）"
    ];

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>診断結果</title>
        <style>
            body{font-family:sans-serif; padding:20px;}
            .box{background:#fafafa; padding:15px; border-radius:8px;}
        </style>
    </head>
    <body>

        <h2>パーソナルカラー診断 結果</h2>

        <p><b>あなたの1stタイプ：</b> <?= $typeName[$firstType] ?></p>
        <p><b>あなたの2ndタイプ：</b> <?= $typeName[$secondType] ?></p>

        <div class="box">
            1st：<?= $typeName[$firstType] ?> … <b><?= $firstPercent ?>%</b><br>
            2nd：<?= $typeName[$secondType] ?> … <b><?= $secondPercent ?>%</b>
        </div>

        <br>
            <a href="parsonal_color.php">もう一度診断する</a>

    </body>
</html>

<?php exit; } ?>

<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
    <title>PC診断</title>
    <style>
    body{font-family:sans-serif; padding:20px;}
    button{
        width:100%; 
        padding:15px; 
        margin:8px 0; 
        border:none;
        background:#eee; 
        border-radius:8px;
        font-size:16px;
    }
    </style>
    </head>
    <body>

        <h2>質問 <?= $q ?> / <?= count($questions) ?></h2>
        <p><?= $questions[$q-1]["q"] ?></p>

        <?php foreach ($questions[$q-1]["a"] as $type => $label): ?>
        <form method="post">
            <input type="hidden" name="q" value="<?= $q+1 ?>">
            <input type="hidden" name="add" value="<?= $type ?>">
            <button type="submit"><?= $label ?></button>
        </form>
    <?php endforeach; ?>

    </body>
</html>
