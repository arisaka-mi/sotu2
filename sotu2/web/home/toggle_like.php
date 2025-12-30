<?php
session_start();
require_once '../login/config.php';

header('Content-Type: application/json; charset=UTF-8');

/* =========================
   ログインチェック
========================= */
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'not login'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'no post id'
    ]);
    exit;
}

/* =========================
   投稿者 user_id を取得
========================= */
$stmt = $pdo->prepare(
    "SELECT user_id FROM Post WHERE post_id = ?"
);
$stmt->execute([$post_id]);
$post_owner_id = $stmt->fetchColumn();

if (!$post_owner_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'post not found'
    ]);
    exit;
}

/* =========================
   いいね済み確認
========================= */
$stmt = $pdo->prepare(
    "SELECT 1 FROM PostLike WHERE post_id=? AND user_id=?"
);
$stmt->execute([$post_id, $user_id]);

$already_liked = $stmt->fetch();

/* =========================
   いいね解除
========================= */
if ($already_liked) {

    $pdo->prepare(
        "DELETE FROM PostLike WHERE post_id=? AND user_id=?"
    )->execute([$post_id, $user_id]);

    $liked = false;

} else {

    /* =========================
       いいね追加
    ========================= */
    $pdo->prepare(
        "INSERT INTO PostLike (post_id, user_id) VALUES (?, ?)"
    )->execute([$post_id, $user_id]);

    $liked = true;

    /* =========================
       通知 INSERT（重要）
    ========================= */

    // 自分の投稿には通知しない
    if ($post_owner_id != $user_id) {

        // 同じ未読通知があるか確認
        $stmt = $pdo->prepare(
            "SELECT 1 FROM Notifications
             WHERE user_id = ?
               AND from_user_id = ?
               AND post_id = ?
               AND type = 'like'
               AND is_read = 0"
        );
        $stmt->execute([
            $post_owner_id,
            $user_id,
            $post_id
        ]);

        // 未読通知がなければ INSERT
        if (!$stmt->fetch()) {

            $pdo->prepare(
                "INSERT INTO Notifications
                    (user_id, from_user_id, post_id, type, is_read, created_at)
                 VALUES
                    (?, ?, ?, 'like', 0, NOW())"
            )->execute([
                $post_owner_id,
                $user_id,
                $post_id
            ]);
        }
    }
}

/* =========================
   いいね数取得
========================= */
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM PostLike WHERE post_id=?"
);
$stmt->execute([$post_id]);
$like_count = $stmt->fetchColumn();

/* =========================
   レスポンス
========================= */
echo json_encode([
    'status' => 'ok',
    'liked' => $liked,
    'like_count' => (int)$like_count
]);
exit;
