<?php
$pdo = new PDO("mysql:host=localhost;dbname=sotu2;charset=utf8", "root", "");
$posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>投稿一覧</title>
</head>
<body>

<h1>投稿一覧</h1>
<!--これを最終的にホームのタイムラインに載せる予定-->

<?php foreach ($posts as $post): ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <img src="<?= $post['image_path'] ?>" width="200"><br>
        <p><?= htmlspecialchars($post['description']) ?></p>
        <p>タグ: <?= htmlspecialchars($post['tags']) ?></p>
    </div>
<?php endforeach; ?>

</body>
</html>
