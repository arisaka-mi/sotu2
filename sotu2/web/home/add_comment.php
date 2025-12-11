<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;
$comment = $_POST['comment'] ?? '';
$parent_cmt_id = $_POST['parent_cmt_id'] ?? null; // ← 返信

if (!$post_id || $comment === '') {
    exit('データ不足です');
}

try {
    // コメント追加
    $sql = "
        INSERT INTO Comment (post_id, user_id, cmt, cmt_at, parent_cmt_id)
        VALUES (?, ?, ?, NOW(), ?)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id, $user_id, $comment, $parent_cmt_id]);

    // 投稿主への通知
    $sql2 = "
        INSERT INTO Notifications (user_id, from_user_id, type, post_id, created_at)
        VALUES ((SELECT user_id FROM Post WHERE post_id = ?), ?, 'comment', ?, NOW())
    ";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$post_id, $user_id, $post_id]);

} catch (PDOException $e) {
    exit("データベースエラー: " . $e->getMessage());
}

// 通常のフォームなので リダイレクトでOK
header("Location: timeline_public.php");
exit;

