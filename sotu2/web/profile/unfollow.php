<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION["user_id"];
$followed_id = $_POST["followed_id"] ?? null;

if (!$followed_id) exit('エラー');

// フォロー解除
$stmt = $pdo->prepare("DELETE FROM Follow WHERE follower_id = ? AND followed_id = ?");
$stmt->execute([$user_id, $followed_id]);

header("Location: profile.php?user_id=" . $followed_id);
exit();
