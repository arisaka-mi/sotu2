<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) exit('投稿がありません');

// すでにいいねしているか確認
$stmt = $pdo->prepare("SELECT * FROM PostLike WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);
$liked = $stmt->fetch();

if ($liked) {
    // いいね解除
    $stmt = $pdo->prepare("DELETE FROM PostLike WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $status = "unliked";
} else {
    // いいね追加
    $stmt = $pdo->prepare("INSERT INTO PostLike (user_id, post_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $post_id]);
    $status = "liked";

    // ★ 通知作成
    $stmt = $pdo->prepare("
        INSERT INTO Notifications (user_id, from_user_id, type, post_id)
        VALUES ((SELECT user_id FROM Post WHERE post_id = ?), ?, 'like', ?)
    ");
    $stmt->execute([$post_id, $user_id, $post_id]);
}

// いいね数取得
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM PostLike WHERE post_id = ?");
$stmt->execute([$post_id]);
$like_count = $stmt->fetch()['cnt'];

echo json_encode([
    'status' => $status,
    'like_count' => $like_count
]);
