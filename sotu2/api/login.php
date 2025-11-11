<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// POSTデータを取得
$mail = $_POST['mail'] ?? '';
$pass = $_POST['pass'] ?? '';

// 入力チェック
if ($mail === '' || $pass === '') {
    echo json_encode([
        "status" => "error",
        "message" => "メールアドレスとパスワードを入力してください。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// DB接続
$dsn = "mysql:host=localhost; dbname=kadai; charset=utf8";
$username = "root";
$password = "";

try {
    $dbh = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "データベース接続に失敗しました: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ユーザーを検索
$sql = "SELECT * FROM users WHERE mail = :mail";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':mail', $mail);
$stmt->execute();
$member = $stmt->fetch(PDO::FETCH_ASSOC);

// パスワードの確認
if (!$member) {
    echo json_encode([
        "status" => "error",
        "message" => "メールアドレスが登録されていません。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!password_verify($pass, $member['pass'])) {
    echo json_encode([
        "status" => "error",
        "message" => "パスワードが間違っています。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ログイン成功
$_SESSION['id'] = $member['id'];
$_SESSION['name'] = $member['name'];

echo json_encode([
    "status" => "ok",
    "message" => "ログイン成功",
    "user" => [
        "id" => $member['id'],
        "name" => $member['name'],
        "mail" => $member['mail']
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
