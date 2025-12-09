<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION["user_id"];
$followed_id = $_POST["followed_id"] ?? null;

if (!$followed_id) exit('エラー');

// すでにフォローしているか確認
$stmt = $pdo->prepare("SELECT * FROM Follow WHERE follower_id = ? AND followed_id = ?");
$stmt->execute([$user_id, $followed_id]);

if ($stmt->fetch()) {
    exit('すでにフォロー済み');
}

// フォロー処理
$stmt = $pdo->prepare("INSERT INTO Follow (follower_id, followed_id) VALUES (?, ?)");
$stmt->execute([$user_id, $followed_id]);

// 通知作成（任意）
$stmt = $pdo->prepare("
    INSERT INTO Notifications (user_id, from_user_id, type)
    VALUES (?, ?, 'follow')
");
$stmt->execute([$followed_id, $user_id]);

header("Location: profile.php?user_id=" . $followed_id);
exit();
