<?php
session_start();

$posts   = $_SESSION['posts'] ?? [];
$keyword = $_SESSION['keyword'] ?? '';

// セッションをクリアしておく
unset($_SESSION['posts']);
unset($_SESSION['keyword']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>検索結果</title>
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
    </header>
    <main>
        <h2>検索ワード：<?= htmlspecialchars($keyword) ?></h2>

        <?php if (empty($posts)): ?>
            <p>見つかりませんでした。</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <?php
                        // DBに保存されている media_url を search/uploads に置換
                        $image_url = str_replace('../home/uploads/', '../search/uploads/', $post['media_url'] ?? '');
                    ?>
                    <?php if (!empty($post['media_url']) && file_exists($image_url)): ?>
                        <img src="<?= htmlspecialchars($image_url) ?>" alt="投稿画像">
                    <?php endif; ?>

                    <p><?= nl2br(htmlspecialchars($post['content_text'] ?? '内容なし')) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="search.php">← 検索に戻る</a>
    </main>
</body>
</html>
