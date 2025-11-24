<?php
session_start();
require_once('../login/config.php'); // $pdo を作成済みと仮定

try {
    // 投稿一覧を取得（タグも結合）
    $sql = "
        SELECT p.post_id, p.user_id, p.media_url, p.content_text, p.created_at,
               GROUP_CONCAT(t.tag_name SEPARATOR ', ') AS tags
        FROM Post p
        LEFT JOIN posttag pt ON p.post_id = pt.post_id
        LEFT JOIN Tag t ON pt.tag_id = t.tag_id
        GROUP BY p.post_id
        ORDER BY p.created_at DESC
    ";
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("データベースエラー：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>投稿一覧</title>
</head>
<body>

<h1>投稿一覧</h1>

<?php if (empty($posts)): ?>
    <p>投稿はまだありません。</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <?php if (!empty($post['media_url'])): ?>
                <img src="<?= htmlspecialchars($post['media_url']) ?>" width="200"><br>
            <?php endif; ?>
            <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
            <p>タグ: <?= htmlspecialchars($post['tags']) ?></p>
            <p>投稿日: <?= htmlspecialchars($post['created_at']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
