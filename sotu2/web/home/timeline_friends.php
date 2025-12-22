<?php
session_start();
require_once('../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {

    /* =========================
       フォロー＋自分の投稿
       public + friends
    ========================= */
    $sql = "
        SELECT 
            p.post_id,
            p.user_id,
            p.media_url,
            p.content_text,
            p.created_at,
            p.visibility,
            u.u_name,
            u.pro_img,

            (SELECT GROUP_CONCAT(t.tag_name SEPARATOR ', ')
             FROM PostTag pt
             JOIN Tag t ON pt.tag_id = t.tag_id
             WHERE pt.post_id = p.post_id) AS tags,

            (SELECT COUNT(*) FROM Comment c WHERE c.post_id = p.post_id) AS comment_count,
            (SELECT COUNT(*) FROM PostLike l WHERE l.post_id = p.post_id) AS like_count

        FROM Post p
        JOIN User u ON p.user_id = u.user_id
        WHERE
            p.visibility IN ('public', 'friends')
            AND (
                p.user_id = :uid
                OR p.user_id IN (
                    SELECT followed_id
                    FROM Follow
                    WHERE follower_id = :uid
                )
            )
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DBエラー：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>友達の投稿</title>
<style>
.post {
    border: 1px solid #ccc;
    padding: 12px;
    margin-bottom: 16px;
    background: #fff;
}
.user-box {
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-box img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
</style>
</head>

<body>

<?php include '../navigation/nav.php'; ?>

<main style="margin-left:20%; padding:40px;">

<h1>友達の投稿</h1>
<p><a href="timeline_public.php">← 全体公開に戻る</a></p>

<?php if (empty($posts)): ?>
    <p>表示できる投稿がありません。</p>

<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php $icon = "../profile/" . ($post['pro_img'] ?: "u_icon/default.png"); ?>

        <div class="post">

            <!-- 投稿者 -->
            <div class="user-box">
                <img src="<?= htmlspecialchars($icon) ?>">
                <strong><?= htmlspecialchars($post['u_name']) ?></strong>

                <?php if ($post['visibility'] === 'friends'): ?>
                    <span style="margin-left:8px; color:#f48fb1;">🔒 フォロワー公開</span>
                <?php endif; ?>
            </div>

            <!-- 画像 -->
            <?php if (!empty($post['media_url'])): ?>
                <img src="<?= htmlspecialchars($post['media_url']) ?>" style="max-width:200px; margin-top:10px;">
            <?php endif; ?>

            <!-- 本文 -->
            <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>

            <!-- タグ -->
            <?php if (!empty($post['tags'])): ?>
                <p>タグ：<?= htmlspecialchars($post['tags']) ?></p>
            <?php endif; ?>

            <small><?= htmlspecialchars($post['created_at']) ?></small>

        </div>
    <?php endforeach; ?>
<?php endif; ?>

</main>

</body>
</html>
