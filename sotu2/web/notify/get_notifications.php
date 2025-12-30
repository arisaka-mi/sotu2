<?php
session_start();
require_once '../login/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        n.id,
        n.type,
        n.post_id,
        n.created_at,
        u.u_name AS username,
        u.pro_img AS profile_img,
        p.content_text AS post_content
    FROM Notifications n
    LEFT JOIN User u 
        ON n.from_user_id = u.user_id
    LEFT JOIN Post p 
        ON n.post_id = p.post_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");

$stmt->execute([$user_id]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($notifications, JSON_UNESCAPED_UNICODE);
exit;
