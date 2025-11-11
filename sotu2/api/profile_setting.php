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

// ログインチェック
if (!isset($_SESSION['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "ログインしてください。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = $_SESSION['id'];

// POSTでユーザー名を変更
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['new_name'] ?? '');

    if ($newName === '') {
        echo json_encode([
            "status" => "error",
            "message" => "名前は空にできません。"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // DB更新
    $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
    $stmt->execute([':name' => $newName, ':id' => $userId]);

    // セッション更新
    $_SESSION['name'] = $newName;

    echo json_encode([
        "status" => "ok",
        "message" => "ユーザー名を更新しました。",
        "user" => [
            "id" => $userId,
            "name" => $newName
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// GETの場合：現在のユーザー情報を返す
$stmt = $pdo->prepare("SELECT id, name, mail FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "ユーザーが見つかりません。"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    "status" => "ok",
    "user" => $user
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
