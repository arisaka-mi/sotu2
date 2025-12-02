<?php
session_start();
require_once('../login/config.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("ログインしてください");

$post_id = $_POST['post_id'] ?? null;

if ($post_id) {

    // すでにいいねしているか確認
    $stmt = $pdo->prepare("SELECT like_id FROM PostLike WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $like = $stmt->fetch();

    if ($like) {
        // いいね解除
        $stmt = $pdo->prepare("DELETE FROM PostLike WHERE like_id = ?");
        $stmt->execute([$like['like_id']]);
    } else {
        // いいね追加
        $stmt = $pdo->prepare("INSERT INTO PostLike (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
    }
}

// ⭐ redirect は timeline.php に戻すのが正しい
header("Location: ./timeline.php");
exit;
