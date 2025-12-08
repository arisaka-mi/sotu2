<?php
session_start();
require_once('../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

// URLの user_id があれば他人のプロフィール、なければ自分
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];

// プロフィール対象ユーザーの情報取得
$stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $profile_user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("ユーザー情報が見つかりません。");
}

// ログイン中ユーザーID
$logged_in_user_id = $_SESSION['user_id'];

// フォロー判定
$is_following = false;
if ($logged_in_user_id != $profile_user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM Follow WHERE follower_id = :follower AND followed_id = :followed");
    $stmt->execute([
        ':follower' => $logged_in_user_id,
        ':followed' => $profile_user_id
    ]);
    $is_following = $stmt->fetch() ? true : false;
}

// プロフィール画像
$img_path = $user['pro_img'] ?? 'u_img/default.png';
if (!file_exists(__DIR__ . '/' . $img_path) || empty($user['pro_img'])) {
    $img_path = 'u_img/default.png';
}

// 表示用
$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES, 'UTF-8');
$u_text = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES, 'UTF-8');

// フォロー数・フォロワー数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Follow WHERE follower_id = :id");
$stmt->execute([':id' => $profile_user_id]);
$follow_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM Follow WHERE followed_id = :id");
$stmt->execute([':id' => $profile_user_id]);
$follower_count = $stmt->fetchColumn();
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

    /* フォロー・フォロワー表示枠 */
    .follow-block {
        display: flex;
        justify-content: center;
        gap: 40px;           /* フォローとフォロワーの間の距離 */
        margin: 10px 0 20px;
    }

    /* 1つ分の縦並び */
    .follow-item {
        text-align: center;
    }

    /* 数字（大きめ） */
    .follow-number {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        display: block;
        line-height: 1;
    }

    /* ラベル（フォロー / フォロワー） */
    .follow-label {
        font-size: 14px;
        color: #555;
        display: block;
        margin-top: 4px;
    }   

</style>
</head>
<body>

<div class="profile-container">
    <img src="<?= htmlspecialchars($img_path, ENT_QUOTES) ?>?<?= time() ?>" alt="プロフィール画像" class="profile-icon">

    <h1><?= $u_name ?></h1>
    <h2>@<?= $u_name_id ?></h2>
    <p class="u_text"><?= $u_text ?></p>

    <div class="follow-block">
        <div class="follow-item">
            <span class="follow-number"><?= $follow_count ?></span>
            <span class="follow-label">フォロー</span>
        </div>
        <div class="follow-item">
            <span class="follow-number"><?= $follower_count ?></span>
            <span class="follow-label">フォロワー</span>
        </div>
    </div>

    <?php if ($logged_in_user_id != $profile_user_id): ?>
        <?php if ($is_following): ?>
            <form action="unfollow.php" method="POST">
                <input type="hidden" name="followed_id" value="<?= $profile_user_id ?>">
                <button class="btn" style="background:#ff6b6b;">フォロー解除</button>
            </form>
        <?php else: ?>
            <form action="follow.php" method="POST">
                <input type="hidden" name="followed_id" value="<?= $profile_user_id ?>">
                <button class="btn" style="background:#4caf50;">フォローする</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <a href="profile_setting.php" class="btn">プロフィール編集</a>
</div>

</body>
</html>
