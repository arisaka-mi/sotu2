<?php
require_once 'notify.php';

$actor_id = $_SESSION['user_id'];   // フォローした側
$toUser   = $_POST['follow_user'];  // フォローされる側

// ▼ すでにフォロー済みかチェック
$stmt = $pdo->prepare("
    SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?
");
$stmt->execute([$actor_id, $toUser]);
$already = $stmt->fetch();

if (!$already) {

    // ▼ フォロー保存
    $stmt = $pdo->prepare("
        INSERT INTO follows (follower_id, followed_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$actor_id, $toUser]);

    // ▼ 通知作成 (tweet_id は null)
    createNotification(
        $toUser,
        $actor_id,
        "follow",
        null
    );
}

echo "ok";
