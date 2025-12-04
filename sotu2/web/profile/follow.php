<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION["user_id"];
$follow_user_id = $_POST["follow_user_id"] ?? null;

if (!$follow_user_id) exit('エラー');

// すでにフォローしているか確認
$stmt = $pdo->prepare("SELECT * FROM Follow WHERE follower_id = ? AND followed_id = ?");
$stmt->execute([$user_id, $follow_user_id]);

if ($stmt->fetch()) {
    exit('すでにフォロー済み');
}

// フォロー処理
$stmt = $pdo->prepare("INSERT INTO Follow (follower_id, followed_id) VALUES (?, ?)");
$stmt->execute([$user_id, $follow_user_id]);

// ★ 通知作成
$stmt = $pdo->prepare("
    INSERT INTO Notifications (user_id, from_user_id, type)
    VALUES (?, ?, 'follow')
");
$stmt->execute([$follow_user_id, $user_id]);

echo json_encode(['status' => 'ok']);
