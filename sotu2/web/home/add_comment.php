<?php
session_start();
require_once '../login/config.php';

/* ===============================
   コメント一覧取得（GET）
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = (int)($_GET['post_id'] ?? 0);
    if (!$post_id) exit;

    $stmt = $pdo->prepare("
        SELECT 
            c.cmt_id,
            c.cmt,
            c.cmt_at,
            c.parent_cmt_id,
            u.u_name,
            u.pro_img
        FROM Comment c
        JOIN User u ON c.user_id = u.user_id
        WHERE c.post_id = ?
        ORDER BY c.cmt_at ASC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tree = [];
    foreach ($comments as $comment) {
        $parent = $comment['parent_cmt_id'] ?: 0;
        $tree[$parent][] = $comment;
    }

    function render_comments($parent_id, $tree) {
        if (!isset($tree[$parent_id])) return;
        foreach ($tree[$parent_id] as $cmt) {
            $icon = '../profile/' . ($cmt['pro_img'] ?: 'u_icon/default.png');
            $isReply = $cmt['parent_cmt_id'] ? ' comment-reply' : '';
            echo '<div class="comment-item'.$isReply.'" style="margin-left:' . ($cmt['parent_cmt_id'] ? '30px' : '0') . '">';
            echo '  <img src="' . htmlspecialchars($icon) . '" class="comment-user-icon">';
            echo '  <div class="comment-body">';
            echo '      <strong>' . htmlspecialchars($cmt['u_name']) . '</strong>';
            echo '      <p>' . nl2br(htmlspecialchars($cmt['cmt'])) . '</p>';
            echo '      <button class="reply-btn" data-parent-id="' . $cmt['cmt_id'] . '" data-parent-user="' . htmlspecialchars($cmt['u_name']) . '">返信</button>';
            echo '  </div>';
            echo '</div>';

            render_comments($cmt['cmt_id'], $tree);
        }
    }

    render_comments(0, $tree);
    exit;
}

/* ===============================
   コメント投稿（POST）
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_id'])) exit;

    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    $parent_cmt_id = $_POST['parent_cmt_id'] ?? null;

    if (!$post_id || $comment === '') exit;

    try {
        // コメント追加
        $stmt = $pdo->prepare("
            INSERT INTO Comment (post_id, user_id, cmt, cmt_at, parent_cmt_id)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$post_id, $user_id, $comment, $parent_cmt_id]);

        // 投稿主への通知
        $stmt2 = $pdo->prepare("
            INSERT INTO Notifications (user_id, from_user_id, type, post_id, created_at)
            VALUES (
                (SELECT user_id FROM Post WHERE post_id = ?),
                ?, 'comment', ?, NOW()
            )
        ");
        $stmt2->execute([$post_id, $user_id, $post_id]);

    } catch (PDOException $e) {
        exit;
    }

    // ★ AJAXなのでリダイレクトしない
    exit;
}
