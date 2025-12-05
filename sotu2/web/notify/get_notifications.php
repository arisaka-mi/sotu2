<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    exit('ログインしてください');
}

$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT n.*, u.username, u.profile_img, p.content AS post_content
    FROM Notifications n
    LEFT JOIN User u ON n.from_user_id = u.user_id
    LEFT JOIN Post p ON n.post_id = p.post_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($notifications);
