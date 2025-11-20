<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// ▼ DB接続（ユーザー名/password はあなたの環境に合わせる）
$pdo = new PDO(
    'mysql:host=localhost;dbname=noti_test;charset=utf8',
    'db_user',       // ← ここはあなたのDBユーザー
    'db_password'    // ← ここはあなたのDBパスワード
);

// ▼ 通知作成関数（あなたの書式に合わせてあります）
function createNotification($toUser, $fromUser, $type, $contentId) {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO noti_test (notified_user_id, actor_user_id, notify_type, tweet_id, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");

    $stmt->execute([$toUser, $fromUser, $type, $contentId]);
}


// =====================================================
// いいね処理（like.php）
// =====================================================

// 必要なパラメータ
$actor_id   = $_SESSION['user_id'];   // いいねしたユーザー（ログイン中）
$tweet_id   = $_POST['tweet_id'];     // いいね対象ツイート
$toUser     = $_POST['tweet_owner'];  // ツイート作者の user_id


// ▼ すでにいいね済みかチェック
$stmt = $pdo->prepare("
    SELECT * FROM likes WHERE user_id = ? AND tweet_id = ?
");
$stmt->execute([$actor_id, $tweet_id]);
$already = $stmt->fetch();

if (!$already) {

    // ▼ likesテーブルに登録
    $stmt = $pdo->prepare("
        INSERT INTO likes (user_id, tweet_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$actor_id, $tweet_id]);


    // ▼ 通知作成（重要）
    createNotification(
        $toUser,        // 通知されるユーザー（ツイートの作者）
        $actor_id,      // 通知を発生させたユーザー
        "like",         // 種類
        $tweet_id       // 関係するツイート
    );
}


// ▼ レスポンス（確認用）
echo "ok";

