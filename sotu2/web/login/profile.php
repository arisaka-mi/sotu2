<?php
session_start();
// DB接続設定（適宜変更してください）
$host = 'localhost';
$dbname = 'sotu2';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB接続エラー: ' . $e->getMessage());
}

// ログイン済みかチェック
if (!isset($_SESSION['id'])) {
    die('ログインしてください');
}

$userId = $_SESSION['id'];

// フォーム送信時に名前を更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['new_name'])) {
    $newName = $_POST['new_name'];

    // バリデーション（例: 空文字や長さチェックなどは必要に応じて）
    $newName = trim($newName);
    if ($newName === '') {
        echo "名前は空にできません。";
    } else {
        // DBを更新
        $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
        $stmt->execute([':name' => $newName, ':id' => $userId]);

        // セッションの名前も更新
        $_SESSION['name'] = $newName;

        // リダイレクトしてフォーム再送信防止
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// セッションから現在の名前を取得
$username = isset($_SESSION['name']) ? $_SESSION['name'] : '';

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>プロフィール</title>
</head>
<body>
    <h1>プロフィール画面</h1>
    <h1><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="profile_setting.php">プロフィールを編集</a>

<!--削除予定-->
    <a href="logout.php">ログアウト</a>
</body>
</html>

