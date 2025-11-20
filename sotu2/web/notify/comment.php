<?php
require_once 'notify.php'; // ← 上の共通部分を読み込む想定

$actor_id  = $_SESSION['user_id'];       // コメントした本人
$tweet_id  = $_POST['tweet_id'];         // コメント対象ツイート
$text      = $_POST['comment_text'];     // コメント内容
$toUser    = $_POST['tweet_owner'];      // ツイート作者

// ▼ コメントを保存
$stmt = $pdo->prepare("
    INSERT INTO comments (user_id, tweet_id, text)
    VALUES (?, ?, ?)
");
$stmt->execute([$actor_id, $tweet_id, $text]);

// ▼ 通知作成
createNotification(
    $toUser,
    $actor_id,
    "comment",
    $tweet_id
);

echo "ok";
