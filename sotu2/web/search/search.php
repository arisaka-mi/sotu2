<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    // ログインしていない場合は login.php などへリダイレクト
    header("Location: ../login/login.php");
    exit;
}

require_once '../login/config.php'; // DB接続

// おすすめ投稿を取得（最新の public ）
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
</head>
<body>

<h1>投稿検索</h1>

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
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
            <small>投稿日: <?= htmlspecialchars($post['created_at']) ?></small>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>おすすめ投稿はありません。</p>
<?php endif; ?>

</body>
</html>
