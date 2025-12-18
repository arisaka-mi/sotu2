<?php
session_start();
require_once '../login/config.php';

/* POST以外は終了 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

/* ログインチェック */
if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;
$comment = trim($_POST['comment'] ?? '');
$parent_cmt_id = $_POST['parent_cmt_id'] ?? null;

if (!$post_id || $comment === '') {
    exit;
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
    exit; // 画面に出さない
}

/* フォーム送信なので戻す */
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
