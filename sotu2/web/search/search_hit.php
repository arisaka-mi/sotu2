<?php
session_start();

$posts   = $_SESSION['posts'] ?? [];
$keyword = $_SESSION['keyword'] ?? '';

// セッションをクリアしておく（必要なら）
unset($_SESSION['posts']);
unset($_SESSION['keyword']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>検索結果</title>
</head>
<body>

<h2>検索ワード：<?= htmlspecialchars($keyword) ?></h2>

<?php if (empty($posts)): ?>
    <p>見つかりませんでした。</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div>
            <h3><?= htmlspecialchars($post['title']) ?></h3>
            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="search.php">← 検索に戻る</a>

</body>
</html>
