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
    </style>
</head>
<body>
    <header>
        <h1>投稿検索</h1>
    </header>
    <main>
        <!-- 検索フォーム -->
        <form method="get" action="search_control.php">
            <input type="text" name="keyword" placeholder="検索（タグ・本文）">
            <button type="submit">検索</button>
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
