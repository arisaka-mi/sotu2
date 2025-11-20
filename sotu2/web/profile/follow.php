<?php
session_start();
require_once('../login/config.php');

if (!isset($_SESSION['user_id'])) exit("ログインしてください");

$follower_id = $_SESSION['user_id'];
$followed_id = $_POST['followed_id'];

// 自分自身はフォローできない
if ($follower_id == $followed_id) {
    header("Location: profile.php?user_id=$followed_id");
    exit();
}

$stmt = $pdo->prepare("
    INSERT IGNORE INTO Follow (follower_id, followed_id)
    VALUES (:follower, :followed)
");

$stmt->execute([
    ':follower' => $follower_id,
    ':followed' => $followed_id
]);

header("Location: profile.php?user_id=$followed_id");
exit();
