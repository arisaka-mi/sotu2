<?php
session_start(); // セッション開始（login.phpで保存した情報を使う）
header('Content-Type: application/json; charset=UTF-8');

// ログインしていなければリダイレクト
if (!isset($_SESSION['id'])) {
    header("Location: login_from.php");
    exit();
}

// DB接続
$host = "localhost";
$dbname = "kadai";   // login.php で使っている DB 名に合わせる
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 投稿処理
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["text"])) {
        $text = $_POST["text"];
        $user_id = $_SESSION['id']; // ログイン中のユーザーIDを利用

        $stmt = $pdo->prepare("INSERT INTO post (user_id, text) VALUES (:user_id, :text)");
        $stmt->execute([
            ":user_id" => $user_id,
            ":text" => $text
        ]);
    }

    // タイムライン表示（最新10件）
    $stmt = $pdo->query("SELECT p.*, u.name 
                         FROM post p 
                         JOIN users u ON p.user_id = u.id 
                         ORDER BY p.created_at DESC 
                         LIMIT 10");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "DB接続エラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>タイムライン</title>
</head>
<body>
    <h1>タイムライン</h1>
    <p>ログイン中: <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, "UTF-8") ?> さん</p>

    <!-- 投稿フォーム -->
    <form method="post" action="">
        <textarea name="text" rows="3" cols="40" placeholder="いまどうしてる？"></textarea><br>
        <button type="submit">投稿</button>
    </form>

    <hr>

    <!-- 投稿一覧 -->
    <?php foreach ($posts as $post): ?>
        <div>
            <strong><?= htmlspecialchars($post["name"], ENT_QUOTES, "UTF-8") ?></strong><br>
            <p><?= nl2br(htmlspecialchars($post["text"], ENT_QUOTES, "UTF-8")) ?></p>
            <small>投稿日時: <?= $post["created_at"] ?></small>
        </div>
        <hr>
    <?php endforeach; ?>
</body>
</html>
