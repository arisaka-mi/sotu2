<?php
session_start();
require_once('../login/config.php');
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) die("ログインしてください");

$post_id = $_POST['post_id'] ?? null;
$parent_cmt_id = $_POST['parent_cmt_id'] ?? null;
$content = trim($_POST['content'] ?? '');

if ($post_id && $content !== '') {
    $stmt = $pdo->prepare("INSERT INTO Comment (post_id, user_id, cmt, parent_cmt_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $content, $parent_cmt_id ?: null]);
}

header("Location: index.php");
exit;
