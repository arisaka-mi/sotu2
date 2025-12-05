<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;
$comment = $_POST['comment'] ?? '';

if (!$post_id || $comment === '') exit('データ不足です');

// コメント追加
$stmt = $pdo->prepare("
    INSERT INTO Comment (post_id, user_id, content, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$post_id, $user_id, $comment]);

// ★ 通知作成
$stmt = $pdo->prepare("
    INSERT INTO Notifications (user_id, from_user_id, type, post_id)
    VALUES ((SELECT user_id FROM Post WHERE post_id = ?), ?, 'comment', ?)
");
$stmt->execute([$post_id, $user_id, $post_id]);

echo json_encode(['status' => 'ok']);
