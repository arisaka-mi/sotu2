<?php
session_start();
require_once('../login/config.php');
$user_id = $_SESSION['user_id'] ?? null;

try {
    // おすすめユーザー取得
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

    // 投稿一覧取得
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

    // コメントをユーザー名付きで取得
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

    // 投稿ごとにコメントを整理
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

        $icon = "../profile/" . ($c['pro_img'] ?: "u_icon/default.png");

        // コメント本文
        echo '<p><b>' . htmlspecialchars($c['u_name']) . '</b>: ' . nl2br(htmlspecialchars($c['cmt'])) . '</p>';
        echo '<small>' . htmlspecialchars($c['cmt_at']) . '</small>';

        // 返信フォーム（初期非表示）
        echo '<form id="replyForm' . $c['cmt_id'] . '" method="post" action="./add_comment.php" style="display:none; margin-top:5px;">';
        echo '<input type="hidden" name="post_id" value="' . $c['post_id'] . '">';
        echo '<input type="hidden" name="parent_cmt_id" value="' . $c['cmt_id'] . '">';
        echo '<textarea name="comment" placeholder="返信..." required></textarea>';
        echo '<button type="submit">送信</button>';
        echo '</form>';

        // 返信コメントがある場合
        if (!empty($c['children'])) {
            $first = true;
            foreach ($c['children'] as $child) {
                $display = $first ? "block" : "none";
                $extra_id = $child['cmt_id'];
                echo '<div id="child' . $extra_id . '" style="display:' . $display . '; margin-left:20px; border-left:1px solid #ddd; padding-left:5px; margin-top:5px;">';
                render_comments([$child], $level + 1); 
                echo '</div>';
                $first = false;
            }
            if (count($c['children']) > 1) {
                echo '<button type="button" onclick="toggleReplies(' . $c['cmt_id'] . ')">返信を見る</button>';
            }
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
.user-box { display:flex; align-items:center; margin-bottom:8px; cursor:pointer; }
.user-box img { width:45px; height:45px; border-radius:50%; margin-right:10px; object-fit:cover; }
.comment-section { display:none; margin-top:5px; }
</style>
</head>
<body>

<h1>タイムライン</h1>

<?php if (!empty($posts)): ?>
    <?php foreach ($posts as $post): ?>
        <?php $icon_path = "../profile/" . ($post['pro_img'] ?: "u_icon/default.png"); ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">

            <!-- 投稿アイコンをクリックするとコメント表示 -->
            <div class="user-box" onclick="toggleComments('postComments<?= $post['post_id'] ?>')">
                <img src="<?= htmlspecialchars($icon_path) ?>">
                <strong><?= htmlspecialchars($post['u_name']) ?></strong>
            </div>

            <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>

            <!-- コメント全体（初期非表示） -->
            <div id="postComments<?= $post['post_id'] ?>" class="comment-section">
                <?php
                    $tree = $comments_by_post[$post['post_id']] ?? [];
                    $tree = build_comment_tree($tree);
                    render_comments($tree);
                ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function toggleComments(postId) {
    const div = document.getElementById(postId);
    div.style.display = (div.style.display === "none" || div.style.display === "") ? "block" : "none";
}

// 複数返信の表示切替
function toggleReplies(parentId) {
    const children = document.querySelectorAll('#child' + parentId + ' ~ div[id^="child"]');
    children.forEach(div => {
        div.style.display = div.style.display === "none" ? "block" : "none";
    });
}
</script>

</body>
</html>
