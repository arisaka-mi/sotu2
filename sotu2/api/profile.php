<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

// DB接続設定
$host = 'localhost';
$dbname = 'kadai';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "DB接続エラー: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ログイン済みか確認
if (!isset($_SESSION['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "ログインしてください。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = $_SESSION['id'];

// DBからユーザー情報を取得
$stmt = $pdo->prepare("SELECT id, name, mail FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "ユーザー情報が見つかりません。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// JSON形式で返す
echo json_encode([
    "status" => "ok",
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "mail" => $user['mail']
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
