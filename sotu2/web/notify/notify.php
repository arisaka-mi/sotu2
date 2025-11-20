<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$pdo = new PDO(
    'mysql:host=localhost;dbname=noti_test;charset=utf8',
    'db_user',    
    'db_password'
);

function createNotification($toUser, $fromUser, $type, $contentId) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO noti_test (notified_user_id, actor_user_id, notify_type, tweet_id, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$toUser, $fromUser, $type, $contentId]);
}






>