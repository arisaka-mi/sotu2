<?php
require_once 'notify.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM noti_test
    WHERE notified_user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSONで返す想定
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($notifications, JSON_UNESCAPED_UNICODE);
