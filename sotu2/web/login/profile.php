<?php
session_start();
require_once('config.php'); // DB接続

// ログインしていない場合はログイン画面にリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login_form.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// DBからユーザー情報を取得
$stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "ユーザー情報が見つかりません。";
    exit();
}

// 表示用変数
$img_icon = $user['pro_img'] ?? 'default_icon.png'; // デフォルトアイコン
$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES, 'UTF-8');
$u_text = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES, 'UTF-8'); // 自己紹介
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>プロフィール</title>
<style>
    body { 
        font-family: sans-serif; 
        margin: 0; 
        padding: 0; 
    }
    .profile-container { 
        max-width: 500px; 
        margin: 50px auto; 
        padding: 20px; 
        background: #fff; 
        border-radius: 10px; 
        text-align: center;
    }
    .profile-icon {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 20px; /* ←横中央寄せ */
        display: block;      /* ←これが必要 */
        border: 1px solid #ebebebff;
    }

    h1 { 
        margin: 0; 
        font-size: 24px;
    }
    h2 { 
        margin: 5px 0 20px 0; 
        font-size: 18px; 
        color: #555; 
    }
    p { 
        margin-bottom: 20px; 
        line-height: 1.5; 
    }
    .btn { 
        display: inline-block; 
        padding: 10px 20px; 
        margin-right: 10px; 
        background: #c6c6c6ff; 
        color: #fff; 
        text-decoration: none; 
        border-radius: 5px; 
        width: 140px;
    }
    .btn:hover { 
        background: #c6c6c6ff;
    }
</style>
</head>
<body>
<div class="profile-container">
    <!-- アイコン -->
    <img src="<?= htmlspecialchars($img_icon, ENT_QUOTES) ?>" alt="プロフィール画像" class="profile-icon">
    
    <!-- ユーザー名 -->
    <h1><?= $u_name ?></h1>
    
    <!-- ユーザーID -->
    <h2>@<?= $u_name_id ?></h2>

    <!--自己紹介-->
    <p><?= $u_text ?></p>
    
    <!-- ボタン -->
    <a href="profile_setting.php" class="btn">プロフィール編集</a>
    <a href="../diagnosis/diagnosis_form.php" class="btn">診断画面へ</a>
</div>
</body>
</html>
