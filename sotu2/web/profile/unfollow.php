<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) exit("ログインしてください");

$follower_id = $_SESSION['user_id'];
$followed_id = $_POST['followed_id'];

$stmt = $pdo->prepare("
    DELETE FROM Follow
    WHERE follower_id = :follower AND followed_id = :followed
");
$stmt->execute([
    ':follower' => $follower_id,
    ':followed' => $followed_id
]);

header("Location: profile.php?user_id=$followed_id");
exit();
