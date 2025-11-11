<?php
header('Content-Type: application/json; charset=UTF-8');

// POSTデータを取得
$name = $_POST['name'] ?? '';
$mail = $_POST['mail'] ?? '';
$pass_raw = $_POST['pass'] ?? '';

// 入力チェック
if ($name === '' || $mail === '' || $pass_raw === '') {
    echo json_encode([
        "status" => "error",
        "message" => "すべての項目を入力してください。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// パスワードをハッシュ化
$pass = password_hash($pass_raw, PASSWORD_DEFAULT);

// DB接続
$dsn = "mysql:host=localhost; dbname=kadai; charset=utf8";
$username = "root";
$password = "";

try {
    $dbh = new PDO($dsn, $username, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "DB接続エラー: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 同じメールアドレスが登録済みか確認
$sql = "SELECT * FROM users WHERE mail = :mail";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':mail', $mail);
$stmt->execute();
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member) {
    echo json_encode([
        "status" => "error",
        "message" => "このメールアドレスはすでに登録されています。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 新規登録処理
$sql = "INSERT INTO users (name, mail, pass) VALUES (:name, :mail, :pass)";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':name', $name);
$stmt->bindValue(':mail', $mail);
$stmt->bindValue(':pass', $pass);
$stmt->execute();

// 登録成功レスポンス
echo json_encode([
    "status" => "ok",
    "message" => "会員登録が完了しました。",
    "user" => [
        "name" => $name,
        "mail" => $mail
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
