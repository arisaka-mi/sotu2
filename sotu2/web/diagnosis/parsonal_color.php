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

// 質問データ（16問）
$questions = [
    ["q"=>"あなたの肌の第一印象に近いのは？（手の甲・腕の内側で確認）","a"=>[
        "spring"=>"黄みが強く健康的なベージュ",
        "summer"=>"赤みが少なく明るいクリア肌",
        "autumn"=>"黄みが落ち着いてやや深みのある肌",
        "winter"=>"赤みが少なく透明感のある白肌"
    ]],
    ["q"=>"肌の明るさは？（顔・首の境目で確認）","a"=>[
        "spring"=>"明るくて白め",
        "summer"=>"明るく透明感のある白肌",
        "autumn"=>"標準〜やや暗め",
        "winter"=>"白くてくっきり明暗差がある"
    ]],
    ["q"=>"肌の質感は？","a"=>[
        "spring"=>"ツヤ・血色感がある",
        "summer"=>"ソフトでマット寄り",
        "autumn"=>"落ち着いたツヤ感",
        "winter"=>"クリアで光沢少なめ"
    ]],
    ["q"=>"静脈（手首の血管）の色は？（手首で確認）","a"=>[
        "spring"=>"緑っぽい",
        "summer"=>"青〜紫",
        "autumn"=>"緑っぽく深みあり",
        "winter"=>"青〜紫でくっきり"
    ]],
    ["q"=>"日焼けしたらどうなる？","a"=>[
        "spring"=>"すぐ焼けて小麦色になる",
        "summer"=>"赤くなって徐々に焼ける",
        "autumn"=>"焼けると黄味寄り",
        "winter"=>"赤くなりやすく、日焼けは浅め"
    ]],
    ["q"=>"ゴールド／シルバーのどちらが肌に合う？","a"=>[
        "spring"=>"ゴールドが元気に見える",
        "summer"=>"シルバーが柔らかく見える",
        "autumn"=>"ゴールドで落ち着いて見える",
        "winter"=>"シルバーで肌が引き締まって見える"
    ]],
    ["q"=>"目の色はどちらに近い？","a"=>[
        "spring"=>"ライトブラウン・黄み寄り",
        "summer"=>"明るめブラウン・赤み控えめ",
        "autumn"=>"ダークブラウン・黄み寄り",
        "winter"=>"ブラック〜ダークブラウン・アッシュ系"
    ]],
    ["q"=>"目の印象は？（白目と黒目のコントラストを確認）","a"=>[
        "spring"=>"白目はクリーム色寄り・コントラスト弱め",
        "summer"=>"白目青白く・柔らかコントラスト",
        "autumn"=>"白目クリーム色寄り・ややくっきり",
        "winter"=>"白目青白く・コントラスト強め"
    ]],
    ["q"=>"髪の元の色は？","a"=>[
        "spring"=>"明るめブラウン・赤みあり",
        "summer"=>"明るめブラウン・赤み控えめ",
        "autumn"=>"濃いブラウン・赤み寄り",
        "winter"=>"黒〜ダークブラウン・アッシュ系"
    ]],
    ["q"=>"似合いやすい服の色は？（比べて気分が良い方）","a"=>[
        "spring"=>"ベージュ・コーラル",
        "summer"=>"パステル・グレー・ラベンダー",
        "autumn"=>"キャメル・オリーブ・テラコッタ",
        "winter"=>"ネイビー・ブラック・白"
    ]],
    ["q"=>"似合うリップの傾向は？","a"=>[
        "spring"=>"コーラル・オレンジ系",
        "summer"=>"ローズ・青みピンク",
        "autumn"=>"テラコッタ・ブラウン系",
        "winter"=>"プラム・赤紫系"
    ]],
    ["q"=>"コスメの質感で肌が綺麗に見えるのは？","a"=>[
        "spring"=>"ツヤ・透明感が映える",
        "summer"=>"ソフトマットでナチュラル",
        "autumn"=>"落ち着いたツヤ感",
        "winter"=>"透明感クリア・光沢少なめ"
    ]],
    ["q"=>"髪色で褒められやすいのは？","a"=>[
        "spring"=>"ベージュ・オレンジ系",
        "summer"=>"アッシュ・ソフトブラウン",
        "autumn"=>"赤みブラウン・キャラメル系",
        "winter"=>"アッシュ・黒系"
    ]],
    ["q"=>"しっくりくるアクセは？","a"=>[
        "spring"=>"イエローゴールド",
        "summer"=>"プラチナ・ホワイトゴールドの柔らかめ",
        "autumn"=>"イエローゴールド・落ち着き系",
        "winter"=>"プラチナ・ホワイトゴールド・シャープ"
    ]],
    ["q"=>"顔のコントラスト（白黒の差）は？","a"=>[
        "spring"=>"やわらかくぼんやり",
        "summer"=>"やわらかく・くすみ少なめ",
        "autumn"=>"落ち着いて明暗差やや強め",
        "winter"=>"強くハッキリ"
    ]],
    ["q"=>"明るい色 vs 暗い色、どちらが似合う？","a"=>[
        "spring"=>"明るい色（ライトベージュ・明るいピンク）",
        "summer"=>"パステル・柔らかい色",
        "autumn"=>"深み・くすみ系の色",
        "winter"=>"ネイビー・黒・鮮やかな色"
    ]]
];

// 最終質問チェック
$totalQuestions = count($questions);
if ($q > $totalQuestions) {
    header("Location: pc_ans.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>パーソナルカラー診断</title>
<style>
body { 
    margin:0; 
    padding:0; 
    font-family:sans-serif; 
    background:#FFEB3B; 
    display:flex; 
    justify-content:center; 
    padding-top:50px; 
    padding-bottom:50px; /* 上下均等の背景 */
}
.container { 
    display:flex; 
    max-width:1000px; 
    width:90%; 
    background:#fff; 
    border-radius:25px; 
    overflow:hidden; 
    min-height:400px; /* 白部分の縦幅を少し狭く */
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}
.question-area { 
    flex:3; 
    padding:30px 30px 60px 30px; /* 上下のパディングを少し減らす */
    display:flex; 
    flex-direction:column; 
    gap:20px; 
}
.q-number { 
    font-weight:bold; 
    font-size:16px; 
    background:#FF9800; 
    color:#fff; 
    padding:6px 12px; 
    border-radius:10px; 
    width:max-content; 
}
.q-text { 
    font-size:22px; 
    font-weight:bold; 
    width: 520px;
}
.choice-form { 
    margin-bottom:12px; 
}
.choice-btn { 
    width:500px; 
    padding:16px; 
    font-size:16px; 
    border:none; 
    border-radius:15px; 
    text-align:left; 
    background:#f5f5f5; 
    cursor:pointer; 
    transition:0.2s; 
}
.choice-btn:hover { 
    background:#FFCC80; 
}
.image-area { 
    flex:2; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    overflow:hidden; 
}
.image-area img { 
    width:100%; 
    height:100%; 
    object-fit:cover; 
    border-radius:20px; 
    display:block; 
}
@media(max-width:768px){
    .container { flex-direction:column; min-height:auto; }
    .question-area, .image-area { flex:unset; width:100%; }
    .image-area { margin-top:20px; height:250px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="question-area">
        <div class="q-number"><?= $q ?>/<?= $totalQuestions ?></div>
        <div class="q-text">Q<?= $q ?>. <?= $questions[$q-1]["q"] ?></div>
        <?php foreach($questions[$q-1]["a"] as $type=>$label): ?>
        <form method="post" class="choice-form">
            <input type="hidden" name="q" value="<?= $q+1 ?>">
            <input type="hidden" name="add" value="<?= $type ?>">
            <button type="submit" class="choice-btn"><?= $label ?></button>
        </form>
        <?php endforeach; ?>
    </div>
    <div class="image-area">
        <img src="pc_sample_image.jpg" alt="質問イメージ">
    </div>
</div>
</body>
</html>
