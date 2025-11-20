<?php
session_start();
require_once('../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// DBからユーザー情報取得
$stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "ユーザー情報が見つかりません。";
    exit();
}





// 初回アクセス時：スコアを初期化
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = [
        "straight" => 0,
        "wave" => 0,
        "natural" => 0
    ];
}

// ---------------------
// 回答を受け取って加点
// ---------------------
if (isset($_POST['add'])) {
    $_SESSION['score'][$_POST['add']]++;
}

// 現在の質問番号（初期値1）
$q = isset($_POST['q']) ? (int)$_POST['q'] : 1;


// -------------------------------
// 全質問回答後：結果画面を表示
// -------------------------------
if ($q > 7) {
    $score = $_SESSION['score'];
    session_destroy(); // 結果表示後にリセット

    // スコアを高い順に並び替え
    arsort($score);

    // 1位の骨格タイプ
    $highestType = array_key_first($score);
    $highestScore = $score[$highestType];

    // 日本語名
    $typeName = [
        "straight" => "ストレート",
        "wave" => "ウェーブ",
        "natural" => "ナチュラル"
    ];

    // -------------------------
    // サブタイプ判定（差が1以内 or 同点）
    // -------------------------
    $subTypes = [];

    foreach ($score as $type => $s) {
        if ($type === $highestType) continue;

        // 同点 or 差が1以内なら「可能性あり」
        if (abs($highestScore - $s) <= 1) {
            $subTypes[] = $typeName[$type];
        }
    }

    // サブタイプメッセージ
    $subMessage = "";
    if (!empty($subTypes)) {
        $list = implode(" ・ ", $subTypes);
        $subMessage = "また、点数が近いため <b>{$list}</b> の可能性もあります。";
    }
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
    <meta charset="UTF-8">
        <title>診断結果</title>
        <style>
            body { font-family: sans-serif; line-height: 1.8; padding: 20px; }
            h1 { margin-bottom: 10px; }
            .result-main { font-size: 1.3em; font-weight: bold; margin: 15px 0; }
            .back-btn { margin-top: 30px; display: inline-block; }
        </style>
    </head>
    <body>

        <h1>骨格診断 結果</h1>

        <p class="result-main">
            あなたの骨格は <span style="color:#d14;"><?= $typeName[$highestType] ?></span> です。
        </p>

        <p><?= $subMessage ?></p>

        <a class="back-btn" href="kotsu.php">もう一度診断する</a>

    </body>
</html>
<?php exit; } ?>

<?php
// ---------------------------------------
// 質問と選択肢（日本語表示 + 加点先を指定）
// ---------------------------------------
$questions = [

    1 => [
        "text" => "Q1. 肩に骨を触れたとき、しっかり触れる？",
        "choices" => [
            "筋肉の厚みを感じる" => "straight",
            "骨っぽさを感じる"   => "natural",
            "ふんわりして骨が目立たない" => "wave",
            "全体的に厚みを感じる"       => "straight",
        ]
    ],

    2 => [
        "text" => "Q2. 鎖骨ははっきりしている？",
        "choices" => [
            "はい" => "natural",
            "いいえ" => "straight"
        ]
    ],

    3 => [
        "text" => "Q3. 手首や膝など関節は目立つ？",
        "choices" => [
            "はい" => "natural",
            "いいえ" => "straight"
        ]
    ],

    4 => [
        "text" => "Q4. 体全体の厚みは？",
        "choices" => [
            "均一で厚みがある" => "straight",
            "上半身薄く、下半身だけ太い" => "wave",
            "全体的にうすく骨感が強い" => "natural"
        ]
    ],

    5 => [
        "text" => "Q5. 服の着心地でしっくりくるのは？",
        "choices" => [
            "体に沿うシンプルな服" => "straight",
            "フレアやふんわり服" => "wave",
            "ゆったり・直線的シルエット" => "natural"
        ]
    ],

    6 => [
        "text" => "Q6. 肩幅の感覚は？",
        "choices" => [
            "平均的" => "straight",
            "広め"   => "natural",
            "狭め"   => "wave"
        ]
    ],

    7 => [
        "text" => "Q7. 上半身と下半身のバランスは？",
        "choices" => [
            "上半身も下半身も同じ厚み" => "straight",
            "上半身薄く、下半身太い" => "wave",
            "全体的に骨感が目立つ" => "natural"
        ]
    ],
];

?>

<!DOCTYPE html>
<html lang="ja">
    <head>
    <meta charset="UTF-8">
        <title>骨格診断</title>
        <style>
            button {
                display: block;
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                font-size: 16px;
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
