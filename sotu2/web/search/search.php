<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

require_once '../login/config.php'; // DB接続

// おすすめ投稿を取得（最新の public 投稿）
$sql_recommend = "
SELECT *
FROM Post
WHERE visibility = 'public'
ORDER BY created_at DESC
";

$stmt_recommend = $pdo->query($sql_recommend);
$recommended_posts = $stmt_recommend->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>検索</title>
    <style>
        main {
            max-width: 800px;   /* 好きな横幅 */
            margin: 40px auto;  /* ← これで中央寄せ */
            padding: 0 16px;    /* 画面端対策（スマホ） */
        }

        /*投稿 */
        .post {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .post img {
            max-width: 200px;
            display: block;
            margin-bottom: 5px;
        }

        /* 検索バー */
        .text_kwd{
            box-sizing: border-box;
            position: relative;
            border: 1px solid #999;
            padding: 4px 40px 4px 12px;
            border-radius: 20px;
            height: 2.3em;
            width: 400px;
            overflow: hidden;
            background: #fff;
            margin: 0 auto 20px auto; /* ← 中央寄せ */
        }
        .text_kwd input[type="text"]{
            border: none;
            height: 100%;
            width: 100%;
            font-size: 14px;
        }
        .text_kwd input[type="text"]:focus {
            outline: none;
        }
        /* 検索ボタン（aタグ） */
        .text_kwd a {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }

        /* 検索アイコン画像 */
        .text_kwd .search_btn {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <?php include '../navigation/nav.php'; ?>
    </header>
    <main>
        <!-- 検索フォーム -->
        <form method="get" action="search_control.php" class="text_kwd">
            <input type="text" size="25" placeholder="キーワード検索">
            <a href="search_hit.php" data-title="search">
                <img src="../search/img/search_edge.PNG" alt="search" class="search_btn">
            </a>
        </form>

        <hr>

        <!-- おすすめ投稿 -->
        <h2>おすすめの投稿</h2>
        <?php if (!empty($recommended_posts)): ?>
            <?php foreach ($recommended_posts as $post): ?>
                <div class="post">
                    <?php
                        // DBに保存されている media_url は home/uploads/... の場合、search/uploads/... に置換
                        $image_url = str_replace('../home/uploads/', '../search/uploads/', $post['media_url'] ?? '');
                    ?>
                    <?php if (!empty($post['media_url']) && file_exists($image_url)): ?>
                        <img src="<?= htmlspecialchars($image_url) ?>" alt="投稿画像">
                    <?php endif; ?>

                    <p><?= nl2br(htmlspecialchars($post['content_text'] ?? '内容なし')) ?></p>
                    <small>投稿日: <?= htmlspecialchars($post['created_at'] ?? '') ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>おすすめ投稿はありません。</p>
        <?php endif; ?>
    </main>
</body>
</html>
