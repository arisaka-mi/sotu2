<?php
session_start();
require_once('../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_from.php');
    exit();
}

// URLの user_id があれば他人のプロフィール、なければ自分
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];

// プロフィール対象ユーザーの情報取得
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        bt.bt_name,
        pc.pc_name
    FROM user u
    LEFT JOIN body_type bt ON u.bt_id = bt.bt_id
    LEFT JOIN parsonal_color pc ON u.pc_id = pc.pc_id
    WHERE u.user_id = :user_id
");
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
if (!empty($user['pro_img']) && file_exists(__DIR__ . '/u_img/' . $user['pro_img'])) {
    $img_path = 'u_img/' . $user['pro_img'];
} else {
    $img_path = 'u_img/default.png';
}



// 表示用
$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES, 'UTF-8');
$u_text = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES, 'UTF-8');

// フォロー数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Follow WHERE follower_id = :id");
$stmt->execute([':id' => $profile_user_id]);
$follow_count = $stmt->fetchColumn();

// フォロワー数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Follow WHERE followed_id = :id");
$stmt->execute([':id' => $profile_user_id]);
$follower_count = $stmt->fetchColumn();


// 投稿取得（最新順）+ いいね数
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM PostLike pl WHERE pl.post_id = p.post_id) AS like_count
    FROM Post p
    WHERE p.user_id = :user_id
    ORDER BY p.created_at DESC
");
$stmt->execute([':user_id' => $profile_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

//コメントのカウント
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM PostLike pl WHERE pl.post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM Comment c WHERE c.post_id = p.post_id) AS comment_count
    FROM Post p
    WHERE p.user_id = :user_id
    ORDER BY p.created_at DESC
");
$stmt->execute([':user_id' => $profile_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        main {
            max-width: 800px;   /* 好きな横幅 */
            margin: 40px auto;  /* ← これで中央寄せ */
            padding: 0 16px;    /* 画面端対策（スマホ） */
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
            margin: 0 auto 20px; 
            display: block;
            border: 1px solid #ebebebff;
        }

        h1 { 
            margin: 0; 
            font-size: 24px; 
        }
        h2 { margin: 5px 0 20px; 
            font-size: 18px; 
            color: #555; 
        }
        p { margin-bottom: 20px; 
            line-height: 1.5; 
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #c6c6c6ff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            width: 140px;
            border: none;
            outline: none;
            cursor: pointer;
        }

        /* プロフィール編集・診断ボタン用 */
        .profile-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .profile-actions .btn {
            width: 180px;
            margin-right: 0;
        }


        .btn:hover { 
            background: #b5b5b5; 
        }

        .unfollow-btn {
            background: #ff6b6b !important;
            border: none !important;
            outline: none !important;
        }
        .unfollow-btn:hover {
            background: #e55b5b !important;
        }

        .follow-block { 
            display: flex; 
            justify-content: center; 
            gap: 40px; 
            margin: 10px 0 20px; 
        }
        .follow-item { 
            text-align: center; 
        }
        .follow-number { 
            font-size: 24px; 
            font-weight: bold; 
            color: #333; 
        }
        .follow-label { 
            font-size: 14px; 
            color: #555; 
            margin-top: 4px; 
        }

        /* 既存のスタイルの下に追加 */
.post-list{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}

.post{
    aspect-ratio:3/4;
    background:#fff;
    border:1px solid #ccc;
    border-radius:12px;
    padding:10px;
    cursor:pointer;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    transition:.2s;
}
.post:hover{
    transform:translateY(-4px);
    box-shadow:0 6px 16px rgba(0,0,0,.15);
}
/* 画像 */
.post img{
    width:100%;
    aspect-ratio:1/1;
    object-fit:cover;
    border-radius:8px;
    margin-bottom:8px;
    flex-shrink:0;
}
/* ===== テキストエリア ===== */
.post-body{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    overflow:hidden;
}
.post-text{
    font-size:14px;
    line-height:1.6;
    font-weight:500;
    margin-bottom:4px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    flex-shrink:0;
}
.post small{
    font-size:12px;
    color:#666;
    flex-shrink:0;
}
.post p{
    font-size:14px;
    line-height:1.6;
    margin-bottom:6px;
    display:-webkit-box;
    -webkit-line-clamp:2;   /* ← 3 → 2 に */
    -webkit-box-orient:vertical;
    overflow:hidden;
}

.empty-state {
    grid-column: 1 / -1;   /* ★ 全列を使う */
    
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    margin: 30px auto;
    padding: 40px 24px;
    max-width: 420px;

    background: #fff;
    border-radius: 20px;
    text-align: center;
}

.empty-icon{
    font-size:48px;
    line-height:1;
}

.empty-state h2{
    font-size:18px;
    font-weight:600;
    color:#333;
}
    </style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <div class="profile-container">

        <img src="<?= htmlspecialchars($img_path, ENT_QUOTES) ?>" class="profile-icon">

        <h1><?= $u_name ?></h1>
        <h2>@<?= $u_name_id ?></h2>

        <p><?= $u_text ?></p>

        <div class="follow-block">
            <div class="follow-item">
                <a href="follow_list.php?user_id=<?= $profile_user_id ?>" style="text-decoration:none; color:inherit;">
                    <span class="follow-number"><?= $follow_count ?></span>
                    <span class="follow-label">フォロー</span>
                </a>
            </div>
            <div class="follow-item">
                <a href="follower_list.php?user_id=<?= $profile_user_id ?>" style="text-decoration:none; color:inherit;">
                    <span class="follow-number"><?= $follower_count ?></span>
                    <span class="follow-label">フォロワー</span>
                </a>
            </div>
        </div>


        <!-- フォローボタン -->
        <?php if ($logged_in_user_id != $profile_user_id): ?>
            <?php if ($is_following): ?>
                <form action="unfollow.php" method="POST">
                    <input type="hidden" name="followed_id" value="<?= $profile_user_id ?>">
                    <button class="btn unfollow-btn">フォロー解除</button>
                </form>
            <?php else: ?>
                <form action="follow.php" method="POST">
                    <input type="hidden" name="followed_id" value="<?= $profile_user_id ?>">
                    <button class="btn">フォローする</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 自分のプロフィールのときだけ表示 -->
        <?php if ($logged_in_user_id == $profile_user_id): ?>
            <div class="profile-actions">
                <a href="profile_setting.php" class="btn">プロフィール編集</a>
                <a href="../diagnosis/diagnosis_form.php" class="btn">診断画面へ</a>
            </div>
        <?php endif; ?>

    </div>
        <!-- 投稿一覧 -->
        <div class="post-list">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <?php if (!empty($post['media_url'])): ?>
                            <img src="<?= htmlspecialchars($post['media_url'], ENT_QUOTES) ?>" alt="投稿画像" class="post-img">
                        <?php endif; ?>
                        <div class="post-body">
                        <p class="post-text"><?= nl2br(htmlspecialchars($post['content_text'], ENT_QUOTES)) ?></p>
                        <small>
                            投稿日: <?= htmlspecialchars($post['created_at']) ?>
                            <span class="like-count"> <img src="img/like_2.png" alt="ハート" style="width:20px; "> <?= $post['like_count'] ?></span>
                            <span class="comment-count"> <img src="img/comment.png" alt="コメント" style="width:23px; "> <?= $post['comment_count'] ?></span>
                        </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">😭</div>
                    <h2>まだ投稿がありません</h2>
                </div>
            <?php endif; ?>
        </div>
</main>
</body>
</html>
