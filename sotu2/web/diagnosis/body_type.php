<?php
session_start();

// 初回スコア初期化
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = [
        "upper_straight"=>0,"upper_wave"=>0,"upper_natural"=>0,
        "lower_straight"=>0,"lower_wave"=>0,"lower_natural"=>0
    ];
}

if (isset($_POST['add'])) {
    $key = $_POST['add'];
    if(isset($_SESSION['score'][$key])) $_SESSION['score'][$key]++;
}

$q = isset($_POST['q']) ? (int)$_POST['q'] : 1;
$totalQuestions = 14;

// ----------------------
// 質問ごと画像のパス設定
// ----------------------
$questionImages = [
    1=>"img/sin_sakotu.PNG",
    2=>"img/sin_ninoude.PNG",
    3=>"img/sin_sakotu.PNG",
    4=>"img/sin_zensinn.PNG",
    5=>"img/sin_kubi.PNG",
    6=>"img/sin_senaka.PNG",
    7=>"img/sin_sakotu.PNG",
    8=>"img/sin_kenkoukotu.PNG",
    9=>"img/sin_kahansin.PNG",
    10=>"img/sin_kahansin.PNG",
    11=>"img/sin_kahansin.PNG",
    12=>"img/sin_kahansin.PNG",
    13=>"img/sin_kahansin.PNG",
    14=>"img/sin_kahansin.PNG"
];

// 画像パス取得（なければ sample_image.jpg）
$currentImage = isset($questionImages[$q]) 
    ? $questionImages[$q] 
    : "sample_image.jpg";

// 質問データ
$questions = [
    1=>["text"=>"Q1. 鎖骨まわりの印象は？","choices"=>[
        "骨感が強くくっきり見える"=>"upper_natural",
        "普通に見える"=>"upper_straight",
        "柔らかく目立たない"=>"upper_wave"
    ]],
    2=>["text"=>"Q2. 二の腕の特徴は？","choices"=>[
        "細くても骨感あり"=>"upper_natural",
        "太るとしっかり太る"=>"upper_straight",
        "ふんわり丸く見える"=>"upper_wave"
    ]],
    3=>["text"=>"Q3. 肩の形は？","choices"=>[
        "角ばって直線的"=>"upper_natural",
        "標準的で丸すぎず広すぎない"=>"upper_straight",
        "なで肩・丸い印象"=>"upper_wave"
    ]],
    4=>["text"=>"Q4. 胸・上半身の厚みは？","choices"=>[
        "メリハリあり"=>"upper_straight",
        "薄めで全体薄い"=>"upper_natural",
        "前後薄く華奢"=>"upper_wave"
    ]],
    5=>["text"=>"Q5. 首の長さ・細さは？","choices"=>[
        "長くて細い"=>"upper_natural",
        "普通"=>"upper_straight",
        "短めでふんわり"=>"upper_wave"
    ]],
    6=>["text"=>"Q6. 背中・肩甲骨の印象は？","choices"=>[
        "骨が浮きやすい"=>"upper_natural",
        "目立たない"=>"upper_straight",
        "柔らかくふっくら"=>"upper_wave"
    ]],
    7=>["text"=>"Q7. 肩幅の印象は？","choices"=>[
        "広め・直線的"=>"upper_natural",
        "普通"=>"upper_straight",
        "狭め"=>"upper_wave"
    ]],
    8=>["text"=>"Q8. 上半身の太り方は？","choices"=>[
        "胸・肩・腕が先に増える"=>"upper_straight",
        "全体的に均等"=>"upper_natural",
        "上半身はあまり太らず柔らかく見える"=>"upper_wave"
    ]],
    9=>["text"=>"Q9. お尻の形は？","choices"=>[
        "立体的でメリハリ"=>"lower_natural",
        "丸くて下側が広がる"=>"lower_wave",
        "上向きでハリあり"=>"lower_straight"
    ]],
    10=>["text"=>"Q10. 太ももの太り方は？","choices"=>[
        "筋肉がつきやすく張りやすい"=>"lower_natural",
        "外側に張りやすい"=>"lower_wave",
        "全体的に太くなる"=>"lower_straight"
    ]],
    11=>["text"=>"Q11. 膝の印象は？","choices"=>[
        "骨ばっている"=>"lower_natural",
        "普通"=>"lower_straight",
        "丸く太め"=>"lower_wave"
    ]],
    12=>["text"=>"Q12. ふくらはぎの特徴は？","choices"=>[
        "筋肉つきやすく張りやすい"=>"lower_natural",
        "平均的"=>"lower_straight",
        "柔らかく丸く太りやすい"=>"lower_wave"
    ]],
    13=>["text"=>"Q13. 脚のラインは？","choices"=>[
        "直線的でスッと細い"=>"lower_natural",
        "標準的"=>"lower_straight",
        "O脚ぎみ・外側張り強め"=>"lower_wave"
    ]],
    14=>["text"=>"Q14. 下半身の太り方は？","choices"=>[
        "均等につきやすい"=>"lower_natural",
        "下半身から目立つ"=>"lower_wave",
        "下半身はあまり太らず上半身が先に増える"=>"lower_straight"
    ]]
];

if ($q > $totalQuestions) {
    header("Location: body_ans.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>骨格診断 質問</title>
<style>
body { 
    margin:0; 
    padding:0; 
    font-family:sans-serif; 
    background:#FFEB3B; 
    display:flex; 
    justify-content:center; 
}
.container { 
    display:flex; 
    max-width:1000px; 
    width:90%; 
    margin:50px auto; 
    background:#fff; 
    border-radius:25px; 
    overflow:hidden; 
    min-height:600px; 
}
.question-area { 
    flex:3; 
    padding:50px 40px 80px 40px; 
    display:flex; 
    flex-direction:column; 
    gap:30px; 
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
}
.choice-form { 
    margin-bottom:12px; 
}
.choice-btn { 
    width: 500px; 
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
    flex:3; 
    display:flex; 
    padding:20px; /* ← 左寄せした上で少し余白 */
}

.image-area img { 
    width:100%;     /* ← 画像を小さく（お好みで変更OK） */
    height:auto; 
    object-fit:contain; 
    border-radius:20px; 
    display:block; 
}

.back-btn {
    margin-top: 20px;
    width: 250px;
    padding: 14px;
    font-size: 16px;
    border: none;
    border-radius: 15px;
    background: #87CEFA; /* 空色 */
    color: #fff;
    cursor: pointer;
    transition: 0.2s;
}

.back-btn:hover {
    background: #6bb8e6; /* 少し濃い空色 */
}



@media(max-width:768px){
    .container { flex-direction:column; min-height:auto; }
    .question-area, .image-area { flex:unset; width:100%; }
    .image-area { margin-top:20px; height:300px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="question-area">
        <div class="q-number"><?= $q ?>/<?= $totalQuestions ?></div>
        <div class="q-text"><?= $questions[$q]["text"] ?></div>
        <?php foreach($questions[$q]["choices"] as $label=>$addScore): ?>
        <form method="post" class="choice-form">
            <input type="hidden" name="q" value="<?= $q+1 ?>">
            <input type="hidden" name="add" value="<?= $addScore ?>">
            <button type="submit" class="choice-btn"><?= $label ?></button>
        </form>
        <?php endforeach; ?>  
            <!-- ▼ 前の質問に戻るボタン -->
        <?php if ($q > 1): ?>
        <form method="post">
            <input type="hidden" name="q" value="<?= $q-1 ?>">
            <button type="submit" class="back-btn">← 前の質問に戻る</button>
        </form>
        <?php endif; ?> 
        <?php if ($q == 1): ?>
        <a href = "diagnosis_form.php">
            <button type="submit" class="back-btn" >← 診断選択に戻る</button>
        </a>
        <?php endif; ?>  
    </div>
    <div class="image-area">
        <img src="<?= $currentImage ?>" alt="質問イメージ">
    </div>
</div>
</body>
</html>
