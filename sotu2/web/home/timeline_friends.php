<?php
session_start();
require_once('../login/config.php');
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("ログインしてください");
}

try {
    // ★ フォローしているユーザーの投稿のみ取得
    $sql = "
        SELECT 
            p.post_id,
            p.user_id,
            p.media_url,
            p.content_text,
            p.created_at,
            u.u_name,
            u.pro_img,
            -- タグをサブクエリで取得して重複防止
            (SELECT GROUP_CONCAT(t.tag_name SEPARATOR ', ')
             FROM PostTag pt
             JOIN Tag t ON pt.tag_id = t.tag_id
             WHERE pt.post_id = p.post_id) AS tags,
            -- コメント数をサブクエリで
            (SELECT COUNT(*) FROM Comment c WHERE c.post_id = p.post_id) AS comment_count,
            -- いいね数をサブクエリで
            (SELECT COUNT(*) FROM PostLike l WHERE l.post_id = p.post_id) AS like_count
        FROM Post p
        JOIN User u ON p.user_id = u.user_id
        JOIN Follow f ON f.followed_id = p.user_id AND f.follower_id = :uid
        ORDER BY p.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // コメント取得（ユーザー情報付き）
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

    // 投稿ごとにコメント整理
    $comments_by_post = [];
    foreach ($all_comments as $c) {
        $comments_by_post[$c['post_id']][] = $c;
    }

     // ★ フォロー・フォロワー取得
    $sql_following = "
        SELECT u.user_id, u.u_name, u.u_name_id, u.pro_img
        FROM Follow f
        JOIN User u ON f.followed_id = u.user_id
        WHERE f.follower_id = :uid
    ";
    $stmt = $pdo->prepare($sql_following);
    $stmt->execute([':uid' => $user_id]);
    $following_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql_followers = "
        SELECT u.user_id, u.u_name, u.u_name_id, u.pro_img
        FROM Follow f
        JOIN User u ON f.follower_id = u.user_id
        WHERE f.followed_id = :uid
    ";
    $stmt = $pdo->prepare($sql_followers);
    $stmt->execute([':uid' => $user_id]);
    $follower_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 表示順のためにマップ化
    $following_map = [];
    foreach ($following_users as $u) $following_map[$u['user_id']] = $u;
    $follower_map = [];
    foreach ($follower_users as $u) $follower_map[$u['user_id']] = $u;

    // 優先順：相互フォロー → フォロー → フォロワー
    $display_users = [];

    foreach ($following_map as $uid => $u) {
        if (isset($follower_map[$uid])) {
            $u['status'] = 'mutual';
            $display_users[$uid] = $u;
            unset($follower_map[$uid]);
        }
    }
    foreach ($following_map as $uid => $u) {
        if (!isset($display_users[$uid])) {
            $u['status'] = 'following';
            $display_users[$uid] = $u;
        }
    }
    foreach ($follower_map as $uid => $u) {
        $u['status'] = 'follower';
        $display_users[$uid] = $u;
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

        $icon = "../profile/" . ($c['pro_img'] ?: "u_icon/default.png");

        echo '<div style="display:flex; align-items:center; gap:8px;">';
        echo '<img src="'. htmlspecialchars($icon) .'" style="width:30px; height:30px; border-radius:50%;">';
        echo '<b>' . htmlspecialchars($c['u_name']) . '</b>';
        echo '</div>';

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

        // 子コメント
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
    <title>友達の投稿</title>
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
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <h1>タイムライン</h1>

    <!-- ★ フォロー・フォロワー表示 -->
    <h2>友達</h2>
    <?php if (!empty($display_users)): ?>
    <div style="display:flex; gap:15px; overflow-x:auto; padding-bottom:10px;">
        <?php foreach ($display_users as $u): ?>
            <?php $icon = "../profile/" . ($u['pro_img'] ?: "u_icon/default.png"); ?>
            <a href="../profile/profile.php?user_id=<?= htmlspecialchars($u['user_id']) ?>" 
            style="text-align:center; color:black; text-decoration:none;">
                <img src="<?= htmlspecialchars($icon) ?>" 
                    style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
                <div><?= htmlspecialchars($u['u_name']) ?></div>
                <div style="font-size:12px; color:#666;">@<?= htmlspecialchars($u['u_name_id']) ?></div>
                <div style="font-size:12px; color:#007BFF;">
                    <?php
                        if ($u['status'] === 'mutual') echo '相互フォロー';
                        elseif ($u['status'] === 'following') echo 'フォロー中';
                        else echo 'フォロワー';
                    ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p>友達をフォローしよう</p>
    <?php endif; ?>

    <!--public か friends-->
    <p><a href="timeline_public.php">おすすめ</a></p>
    <p>友達の投稿</p>

    <!-- ★ 投稿一覧 -->
    <?php if (empty($posts)): ?>
        <p>友達の投稿はありません。</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php $icon_path = "../profile/" . ($post['pro_img'] ?: "u_icon/default.png"); ?>

            <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">

                <!-- 投稿者 -->
                <div class="user-box">
                    <a href="../profile/profile.php?user_id=<?= htmlspecialchars($post['user_id']) ?>" 
                    style="display:flex; align-items:center; text-decoration:none; color:black;">
                        <img src="<?= htmlspecialchars($icon_path) ?>">
                        <strong style="margin-left:5px;"><?= htmlspecialchars($post['u_name']) ?></strong>
                    </a>
                </div>

                <!-- 画像 -->
                <?php if (!empty($post['media_url'])): ?>
                    <img src="<?= htmlspecialchars($post['media_url']) ?>" width="200"><br>
                <?php endif; ?>

                <!-- 投稿テキスト -->
                <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
                <p>タグ: <?= htmlspecialchars($post['tags']) ?></p>
                <p>投稿日: <?= htmlspecialchars($post['created_at']) ?></p>
                <p>コメント: <?= $post['comment_count'] ?>件</p>

                <!-- いいね -->
                <?php if ($user_id): ?>
                <p>いいね: <span id="like-count-<?= $post['post_id'] ?>"><?= $post['like_count'] ?></span>件</p>

                <form method="post" action="./toggle_like.php">
                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                    <button type="submit">いいね</button>
                </form>
                <?php endif; ?>

                <!-- コメントを見るボタン -->
                <button type="button" onclick="toggleComments('comments<?= $post['post_id'] ?>')">
                    コメントを見る（<?= $post['comment_count'] ?>件）
                </button>

                <!-- コメント一覧＋親コメントフォーム -->
                <div id="comments<?= $post['post_id'] ?>" style="display:none; margin-top:10px; border-top:1px solid #ccc; padding-top:10px;">

                    <!-- 親コメント投稿フォーム -->
                    <?php if ($user_id): ?>
                    <form method="post" action="./add_comment.php" style="margin-top:15px;">
                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                        <textarea name="comment" placeholder="コメントを書く..." required style="width:100%; height:60px;"></textarea>
                        <button type="submit" style="margin-top:5px;">投稿</button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- コメント一覧 -->
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
    // コメントフォームの表示切り替え（返信用）
    function toggleCommentForm(id) {
        const form = document.getElementById(id);
        form.style.display = (form.style.display === "none" || form.style.display === "") ? "block" : "none";
    }

    // コメント一覧の開閉
    function toggleComments(id) {
        const area = document.getElementById(id);
        area.style.display = (area.style.display === "none") ? "block" : "none";
    }
    </script>
</main>
</body>
</html>
