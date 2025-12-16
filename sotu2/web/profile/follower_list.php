<?php
session_start();
require_once('../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = (int)($_GET['user_id'] ?? 0);

// このユーザーをフォローしている一覧
$stmt = $pdo->prepare("
    SELECT u.user_id, u.u_name, u.u_name_id, u.pro_img
    FROM Follow f
    JOIN User u ON f.follower_id = u.user_id
    WHERE f.followed_id = :id
");
$stmt->execute([':id' => $user_id]);
$follower_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>フォロワー一覧</title>
    <style>
        /* ===== 全体 ===== */
        body {
            
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", sans-serif;
        }

        /* main（サイドバー考慮） */
        main {
            margin-left: 250px; /* サイドバー幅に合わせる */
            padding: 30px;
        }

        /* タイトル */
        main h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        /* ===== フォロー一覧 ===== */
        .follower-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* 各ユーザー（カード） */
        .follow-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        /* ホバー */
        .follow-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        /* ユーザーアイコン */
        .follow-item img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ユーザー名リンク */
        .follow-item a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
        }

        .follow-item a:hover {
            text-decoration: underline;
        }

        /* ユーザーID */
        .follow-item .user-id {
            color: #777;
            font-size: 14px;
            margin-left: 6px;
        }

        /* フォローなし */
        .empty-message {
            color: #666;
            font-size: 16px;
            margin-top: 20px;
        }


    </style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <h1>フォロワー一覧</h1>

    <?php if (count($follower_list) > 0): ?>
        <div class="follower-list">
            <?php foreach ($follower_list as $row): ?>
                <div class="follow-item">
                    <img src="<?= htmlspecialchars($row['pro_img'] ?? 'u_img/default.png') ?>" width="50" style="border-radius:50%;">
                    <a href="profile.php?user_id=<?= $row['user_id'] ?>">
                        <?= htmlspecialchars($row['u_name']) ?> (@<?= htmlspecialchars($row['u_name_id']) ?>)
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>フォロワーはいません。</p>
    <?php endif; ?>
</main>
</body>
</html>
