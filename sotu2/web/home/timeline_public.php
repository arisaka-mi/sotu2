<?php
session_start();
require_once('../login/config.php');
$user_id = $_SESSION['user_id'] ?? null;

try {
    // ★ おすすめユーザー取得
    $recommend_sql = "
        SELECT user_id, u_name, u_name_id, pro_img
        FROM User
        WHERE user_id != :uid
        AND user_id NOT IN (
            SELECT followed_id FROM Follow WHERE follower_id = :uid
        )
        ORDER BY RAND()
        LIMIT 5
    ";
    $stmt_rec = $pdo->prepare($recommend_sql);
    $stmt_rec->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt_rec->execute();
    $recommend_users = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);


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

    // ★ コメントをユーザー名付きで取得
    $stmt2 = $pdo->query("
        SELECT
            c.*,
            u.u_name,
            u.pro_img
        FROM Comment c
        JOIN User u ON c.user_id = u.user_id
        ORDER BY c.cmt_at ASC
    ");
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
        echo '<div style="margin-left:' . ($level*20) . 'px; border-left:1px solid #ccc; padding-left:5px; margin-top:10px;">';

        // アイコン
        $icon = "../profile/" . ($c['pro_img'] ?: "u_icon/default.png");

        echo '<div style="display:flex; align-items:center; gap:8px;">';
        echo '<img src="'. htmlspecialchars($icon) .'" style="width:30px; height:30px; border-radius:50%;">';
        echo '<b>' . htmlspecialchars($c['u_name']) . '</b>';
        echo '</div>';

        // コメント本文
        echo '<p>' . nl2br(htmlspecialchars($c['cmt'])) . '</p>';
        echo '<small>' . htmlspecialchars($c['cmt_at']) . '</small>';

        // 返信フォーム
        echo '<div style="margin-top:5px;">';
        echo '<button type="button" onclick="toggleCommentForm(\'replyForm' . $c['cmt_id'] . '\')">返信</button>';
        echo '<form id="replyForm' . $c['cmt_id'] . '" method="post" action="./add_comment.php" style="display:none; margin-top:5px;">';
        echo '<input type="hidden" name="post_id" value="' . $c['post_id'] . '">';
        echo '<input type="hidden" name="parent_cmt_id" value="' . $c['cmt_id'] . '">';
        echo '<textarea name="comment" placeholder="返信..." required></textarea>';
        echo '<button type="submit">送信</button>';
        echo '</form>';
        echo '</div>';

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

<!-- おすすめユーザー -->
<?php if (!empty($recommend_users)): ?>
    <h2>おすすめユーザー</h2>
    <div style="display:flex; gap:20px; overflow-x:auto; padding-bottom:10px;">
        <?php foreach ($recommend_users as $u): ?>
            <?php $icon = "../profile/" . ($u['pro_img'] ?: "u_img/default.png"); ?>
            <a href="../profile/profile.php?user_id=<?= htmlspecialchars($u['user_id']) ?>"
               style="text-align:center; color:black; text-decoration:none;">
                <img src="<?= htmlspecialchars($icon) ?>"
                     style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                <div style="font-weight:bold;"><?= htmlspecialchars($u['u_name']) ?></div>
                <div style="font-size:12px; color:#666;">@<?= htmlspecialchars($u['u_name_id']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<p>おすすめ投稿</p>

<!-- 投稿一覧 -->
<?php if (empty($posts)): ?>
    <p>投稿はまだありません。</p>
    <p><a href="timeline_friends.php">友達の投稿</a></p>
    <p><a href="../profile/profile.php">プロフィールへ戻る</a></p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php $icon_path = "../profile/" . ($post['pro_img'] ?: "u_icon/default.png"); ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">

            <!-- 投稿者アイコン & 名前 -->
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

            <!-- コメントフォーム（ボタンで開閉） -->
            <button type="button" onclick="toggleCommentForm('commentForm<?= $post['post_id'] ?>')">
                コメント
            </button>
            <form id="commentForm<?= $post['post_id'] ?>" method="post" action="./add_comment.php" style="display:none; margin-top:5px;">
                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                <textarea name="comment" placeholder="コメント..." required></textarea>
                <button type="submit">送信</button>
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

<script>
// コメントフォームの表示/非表示切り替え
function toggleCommentForm(formId) {
    const form = document.getElementById(formId);
    if (form.style.display === "none") {
        form.style.display = "block";
    } else {
        form.style.display = "none";
    }
}
</script>

</body>
</html>