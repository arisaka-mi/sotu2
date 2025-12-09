<?php
session_start();
require_once('../login/config.php');
$user_id = $_SESSION['user_id'] ?? null;

try {
    // 投稿一覧取得（User情報・タグ・コメント数・いいね数）
    $sql = "
        SELECT 
            p.post_id, 
            p.user_id, 
            p.media_url, 
            p.content_text, 
            p.created_at,
            u.u_name,
            u.pro_img,
            GROUP_CONCAT(t.tag_name SEPARATOR ', ') AS tags,
            COUNT(DISTINCT c.cmt_id) AS comment_count,
            COUNT(DISTINCT l.like_id) AS like_count
        FROM Post p
        JOIN User u ON p.user_id = u.user_id
        LEFT JOIN posttag pt ON p.post_id = pt.post_id
        LEFT JOIN Tag t ON pt.tag_id = t.tag_id
        LEFT JOIN Comment c ON p.post_id = c.post_id
        LEFT JOIN PostLike l ON p.post_id = l.post_id
        GROUP BY p.post_id
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 全コメント取得（返信含む）
    $stmt2 = $pdo->query("SELECT * FROM Comment ORDER BY cmt_at ASC");
    $all_comments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // コメントを投稿ごとに整理
    $comments_by_post = [];
    foreach ($all_comments as $c) {
        $comments_by_post[$c['post_id']][] = $c;
    }

} catch (PDOException $e) {
    die("データベースエラー：" . $e->getMessage());
}

// コメント階層構造
function build_comment_tree($comments, $parent_id = null) {
    $tree = [];
    foreach ($comments as $c) {
        if ($c['parent_cmt_id'] == $parent_id) {
            $c['children'] = build_comment_tree($comments, $c['cmt_id']);
            $tree[] = $c;
        }
    }
    return $tree;
}

// コメント表示
function render_comments($comments, $level = 0) {
    foreach ($comments as $c) {
        echo '<div style="margin-left:' . ($level*20) . 'px; border-left:1px solid #ccc; padding-left:5px; margin-top:5px;">';
        echo '<p><b>User ' . htmlspecialchars($c['user_id']) . '</b>: ' . nl2br(htmlspecialchars($c['cmt'])) . '</p>';
        echo '<small>' . htmlspecialchars($c['cmt_at']) . '</small>';

        echo '<form method="post" action="./add_comment.php">';
        echo '<input type="hidden" name="post_id" value="' . $c['post_id'] . '">';
        echo '<input type="hidden" name="parent_cmt_id" value="' . $c['cmt_id'] . '">';
        echo '<textarea name="content" placeholder="返信..." required></textarea>';
        echo '<button type="submit">返信</button>';
        echo '</form>';

        if (!empty($c['children'])) {
            render_comments($c['children'], $level + 1);
        }
        echo '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>タイムライン</title>
<style>
.user-box {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}
.user-box img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
}
</style>
</head>
<body>

<h1>タイムライン</h1>
<p>おすすめ</p>

<?php if (empty($posts)): ?>
    <p>投稿はまだありません。</p>
    <p><a href="timeline_friends.php">友達の投稿</a></p>
    <p><a href="../profile/profile.php">プロフィールへ戻る</a></p>

<?php else: ?>
    <?php foreach ($posts as $post): ?>

        <?php 
        // アイコンパスを正規化
        $icon_path = "../profile/" . ($post['pro_img'] ?: "u_icon/default.png");
        ?>

        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">

            <!-- 投稿者アイコン & 名前にプロフィールリンク -->
            <div class="user-box">
                <a href="../profile/profile.php?user_id=<?= htmlspecialchars($post['user_id']) ?>" 
                   style="display:flex; align-items:center; text-decoration:none; color:black;">
                    <img src="<?= htmlspecialchars($icon_path) ?>">
                    <strong style="margin-left:5px;"><?= htmlspecialchars($post['u_name']) ?></strong>
                </a>
            </div>

            <!-- 投稿画像 -->
            <?php if (!empty($post['media_url'])): ?>
                <img src="<?= htmlspecialchars($post['media_url']) ?>" width="200"><br>
            <?php endif; ?>

            <!-- 投稿内容 -->
            <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
            <p>タグ: <?= htmlspecialchars($post['tags']) ?></p>
            <p>投稿日: <?= htmlspecialchars($post['created_at']) ?></p>
            <p>コメント: <?= $post['comment_count'] ?>件</p>
            <p>いいね: <?= $post['like_count'] ?>件</p>

            <?php if ($user_id): ?>
            <!-- いいね -->
            <form method="post" action="./toggle_like.php">
                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                <button type="submit">いいね</button>
            </form>

            <!-- コメント -->
            <form method="post" action="./add_comment.php">
                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                <textarea name="content" placeholder="コメント..." required></textarea>
                <button type="submit">コメント</button>
            </form>
            <?php endif; ?>

            <!-- コメント表示 -->
            <?php
                $tree = $comments_by_post[$post['post_id']] ?? [];
                $tree = build_comment_tree($tree);
                render_comments($tree);
            ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
