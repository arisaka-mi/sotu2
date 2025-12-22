<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

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
        LIMIT 7
    ";
    $stmt_rec = $pdo->prepare($recommend_sql);
    $stmt_rec->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt_rec->execute();
    $recommend_users = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

    // ★ 投稿一覧取得（タグはサブクエリで）
    $sql = "
        SELECT 
            p.post_id,
            p.user_id,
            p.media_url,
            p.content_text,
            p.created_at,
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
        ORDER BY p.created_at DESC
    ";
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ★ コメント取得（ユーザー情報付き）
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

    // 投稿ごとに分類
    $comments_by_post = [];
    foreach ($all_comments as $c) {
        $comments_by_post[$c['post_id']][] = $c;
    }

} catch (PDOException $e) {
    die("データベースエラー：" . $e->getMessage());
}

// ★ コメント階層構造を作成
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

// ★ コメント表示
function render_comments($comments, $level = 0) {
    foreach ($comments as $c) {
        echo '<div style="margin-left:' . ($level*20) . 'px; border-left:1px solid #ccc; padding-left:5px; margin-top:10px;">';

        $icon = "../profile/" . ($c['pro_img'] ?: "u_icon/default.png");

        echo '<div style="display:flex; align-items:center; gap:8px;">';
        echo '<img src="'. htmlspecialchars($icon) .'" style="width:30px; height:30px; border-radius:50%;">';
        echo '<b>' . htmlspecialchars($c['u_name']) . '</b>';
        echo '</div>';

        echo '<p>' . nl2br(htmlspecialchars($c['cmt'])) . '</p>';
        echo '<small>' . htmlspecialchars($c['cmt_at']) . '</small>';

        // ★ 返信フォーム
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
<title>タイムライン</title>
<style>
main{max-width:800px;margin:40px auto;padding:0 16px;}
.post-list{
    display: flex;
    flex-direction: column; /* 縦に並べる */
    gap: 24px;              /* 投稿間の余白 */
    max-width: 800px;       /* 横幅を制限 */
    margin: 0 auto;         /* 中央寄せ */
}
.post-list .post {
    background: none;          /* 背景を白に戻す */
    border-radius: 0;          /* 角丸を解除 */
    box-shadow: none;          /* 影を削除 */
    padding: 0;                /* 内側余白をリセット */
    max-width: 800px;          /* 投稿幅は維持 */
    margin: 0 auto 24px;       /* 中央寄せ＋下マージン */
    transition: none;          /* ホバーアニメは無効 */
}

.post-list .post img.post-media {
    width: auto;       /* 投稿画像の横幅を統一 */
    height: 450px;      /* 投稿画像の高さを統一 */
    object-fit: cover;  /* 画像の比率を保ちつつ枠に収める */
    border-radius: 0;   /* 角丸が不要な場合は0 */
    margin: 8px 0;

}

.post-list .post p,
.post-list .post .tags,
.post-list .post .post-footer {
    margin: 4px 0;
    line-height: 1.4;
    color: #333;
}

.post-list .comment-btn,
.post-list form button {
    padding: 4px 8px;
    border-radius: 4px;       /* 角丸を小さく */
    background: #eee;          /* シンプルなグレー */
    color: #333;
    border: 1px solid #ccc;
    cursor: pointer;
}

.post-list .comment-btn:hover,
.post-list form button:hover {
    background: #ddd;
}
.post-image-wrapper {
    position: relative; /* 子要素を絶対配置可能に */
}

.post-media {
    width: 100%;
    height: 450px;
    object-fit: cover;
    display: block;
}

/* 投稿者情報＋投稿日のオーバーレイ */
.post-user-overlay {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,0,0,0.15); /* 半透明黒で文字を見やすく */
    padding: 4px 8px;
    border-radius: 12px;
    color: #fff;
}

.post-user-overlay img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 0.5px solid #ddd;
}

.post-user-info strong {
    display: block;
    font-size: 14px;
}

.post-user-info small {
    display: block;
    font-size: 12px;
    color: #eee;
}




/* ===== 上部エリア ===== */

/* おすすめユーザー全体 */
.recommend-section{
    margin-bottom: 24px;
}
.recommend-section h2{
    font-size: 16px;
    margin-bottom: 10px;
    color:#333;
}

/* 横スクロール */
.recommend-users{
    display: flex;
    gap: 30px;
    overflow-x: auto;
    justify-content: center;
}

/* 横幅が足りない場合だけスクロール */
@media (max-width: 600px){
    .recommend-users{
        justify-content: flex-start;
    }
}
.recommend-users::-webkit-scrollbar{
    height:6px;
}
.recommend-users::-webkit-scrollbar-thumb{
    background:#ddd;
    border-radius:999px;
}

/* ユーザー1人 */
.recommend-user{
    flex-shrink:0;
    text-align:center;
    text-decoration:none;
    color:#333;
    width:72px;
}
.recommend-user img{
    width:64px;
    height:64px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #eee;
    transition:.2s;
}
.recommend-user:hover img{
    transform:scale(1.05);
    border-color:#333;
}
.recommend-user span{
    display:block;
    font-size:12px;
    margin-top:4px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

/* ===== タブ ===== */
.timeline-tabs{
    display:flex;
    gap:20px;
    margin-bottom:20px;
    border-bottom:1px solid #ddd;
}
.timeline-tabs .tab{
    padding:10px 4px;
    text-decoration:none;
    color:#777;
    font-weight:500;
    position:relative;
}
.timeline-tabs .tab.active{
    color:#000;
    font-weight:600;
}
.timeline-tabs .tab.active::after{
    content:'';
    position:absolute;
    left:0;
    bottom:-1px;
    width:100%;
    height:2px;
    background:#000;
    border-radius:2px;
}
.timeline-tabs .tab:hover{
    color:#000;
}
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

/* コメントモーダル */
#commentModal {
    display: none;            /* 初期は非表示 */
    position: fixed;
    top: 30px;
    left: calc(50% + 250px);
    width: 350px;
    height: 90vh;
    background: #fff;
    border-radius: 16px;
    z-index: 1100;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    padding: 12px;
    flex-direction: column;   /* display:flex は JS で表示するときに flex に切り替える */
}
/* コメントリスト */
#modalCommentsArea {
    flex: 1;               /* 高さを残り全部に */
    overflow-y: auto;      /* スクロール可能 */
    margin-bottom: 8px 12px;
}
.comment-item{display:flex;gap:10px;align-items:flex-start;margin-bottom:4px;}
.comment-item img{width:32px;height:32px;border-radius:50%;object-fit:cover;}
.comment-body{flex:1;}
.comment-body strong{font-size:13px;display:block;}
.comment-body p{font-size:14px;margin-top:2px;line-height:1.4;}
.comment-replies{margin-left:30px;display:flex;flex-direction:column;gap:8px;}
.reply-btn{font-size:12px;background:none;border:none;color:#007bff;cursor:pointer;padding:0;margin-top:4px;}
.reply-btn:hover{text-decoration:underline;}
#commentForm {
    border-top: 1px solid #ddd;
    padding: 8px;
    background: #fff;
}
/* コメントフォーム部分 */
.comment-input-wrap {
    display: flex;
    gap: 8px;
    align-items: flex-end;
    /* 下部に固定 */
}
.comment-input-wrap textarea {
    flex: 1;
    min-height: 42px;
    max-height: 120px;
    resize: none;              /* 重要：勝手に崩れない */
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid #ccc;
    font-size: 14px;
    line-height: 1.5;
}

.comment-input-wrap textarea:focus{outline:none;border-color:#666;}
.comment-submit {
    height: 42px;
    padding: 0 16px;
    border: none;
    border-radius: 999px;
    background: #333;
    color: #fff;
    cursor: pointer;
}
.comment-submit:hover{opacity:0.85;}
/* コメント入力用テキストボックス */
.comment-input {
    width: 100%;                   /* 幅いっぱい */
    min-height: 50px;              /* 最低高さ */
    padding: 10px 14px;            /* 内側余白 */
    border: 1px solid #ccc;        /* 薄いグレー枠線 */
    border-radius: 12px;           /* 角丸 */
    font-size: 14px;               /* 文字サイズ */
    line-height: 1.5;              /* 行間 */
    resize: vertical;              /* 高さだけ調整可能 */
    transition: border-color 0.2s, box-shadow 0.2s; /* フォーカス時アニメ */
    box-sizing: border-box;
    background-color: #f9f9f9;     /* 背景色 */
}

/* フォーカス時のデザイン */
.comment-input:focus {
    outline: none;
    border-color: #007bff;         /* 青く変化 */
    box-shadow: 0 0 6px rgba(0,123,255,0.25);
    background-color: #fff;        /* 背景を白に */
}
/* 返信中バー */
.reply-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f0f4ff;
    color: #333;
    font-size: 13px;
    padding: 6px 10px;
    border-radius: 8px;
    margin: 6px 8px;
}

/* × ボタン */
.reply-info button {
    all: unset;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    color: #555;
}
.reply-info button:hover {
    color: #000;
}

/* 返信対象コメントの強調 */
.comment-item.reply-target {
    background: #f5f7ff;
    border-radius: 10px;
    padding: 6px;
}
</style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <!-- ★ おすすめユーザー -->
    <?php if (!empty($recommend_users)): ?>
    <div class="recommend-section">
        <h2>おすすめユーザー</h2>
        <div class="recommend-users">
            <?php foreach ($recommend_users as $u): ?>
                <?php $icon = "../profile/" . ($u['pro_img'] ?: "u_icon/default.png"); ?>
                <a href="../profile/profile.php?user_id=<?= htmlspecialchars($u['user_id']) ?>"
                class="recommend-user">
                    <img src="<?= htmlspecialchars($icon) ?>">
                    <span><?= htmlspecialchars($u['u_name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>


    <div class="timeline-tabs">
        <a href="timeline_public.php" class="tab active">おすすめ投稿</a>
        <a href="timeline_friends.php" class="tab">友達の投稿</a>
    </div>

    <!-- ★ 投稿一覧 -->
    <?php if (empty($posts)): ?>
        <p>投稿はまだありません。</p>
    <?php else: ?>
    <div class="post-list">
    <?php foreach ($posts as $post): ?>
        <?php $icon_path = "../profile/" . ($post['pro_img'] ?: "u_icon/default.png"); ?>

    <div class="post" data-post-id="<?= $post['post_id'] ?>">

    <div class="post-image-wrapper">
        <!-- 投稿画像 -->
        <?php if (!empty($post['media_url'])): ?>
            <img class="post-media" src="<?= htmlspecialchars($post['media_url']) ?>">
        <?php endif; ?>

        <!-- 投稿者情報・投稿日を画像左上に重ねる -->
        <div class="post-user-overlay">
            <img src="<?= htmlspecialchars($icon_path) ?>" alt="">
            <div class="post-user-info">
                <strong><?= htmlspecialchars($post['u_name']) ?></strong>
                <small><?= htmlspecialchars($post['created_at']) ?></small>
            </div>
        </div>
    </div>


    <!-- 投稿テキスト -->
    <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
    <p class="tags">タグ: <?= htmlspecialchars($post['tags']) ?></p>

    <!-- フッター情報 -->
    <div class="post-footer">
        <span>コメント: <?= $post['comment_count'] ?>件</span>
        <span>いいね: <span id="like-count-<?= $post['post_id'] ?>"><?= $post['like_count'] ?></span></span>
    </div>

    <!-- いいねボタン -->
    <form method="post" action="./toggle_like.php">
        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
        <button type="submit">いいね</button>
    </form>

    <!-- コメントを見るボタン -->
    <button type="button" class="comment-btn">
        コメントを見る（<?= $post['comment_count'] ?>件）
    </button>
</div>

        <?php endforeach; ?>
    </div>
    <?php endif; ?>


   <!-- コメントモーダル -->
    <div id="commentModal" style="display:none; top:30px; right:50px; width:350px; height:90vh; background:#fff; border-radius:16px; z-index:1100; box-shadow:0 4px 16px rgba(0,0,0,.2); padding:12px; flex-direction:column;">
        <button id="closeCommentModal" style="position:absolute; top:10px; right:10px; font-size:20px; cursor:pointer;">×</button>
        <h3>コメント</h3>
        <div id="modalCommentsArea" style="flex:1; overflow-y:auto; margin-bottom:8px;"></div>
        <form id="commentForm">
            <div id="replyInfo" class="reply-info" style="display:none;">
                <span id="replyToName"></span> 返信中
                <button type="button" id="cancelReplyTop">×</button>
            </div>
            <input type="hidden" name="post_id" id="modalPostIdComment">
            <input type="hidden" name="parent_cmt_id" id="parentCmtId">
            <div class="comment-input-wrap">
                <textarea id="commentTextarea" placeholder="コメントを書く..." required></textarea>
                <button type="submit" class="comment-submit">送信</button>
                <button type="button" id="cancelReplyBtn" style="display:none;">返信をキャンセル</button>
            </div>
        </form>
    </div>



<script>
document.addEventListener('DOMContentLoaded', () => {
    const commentModal = document.getElementById('commentModal');
    const closeCommentModal = document.getElementById('closeCommentModal');
    const commentList = document.getElementById('modalCommentsArea');
    const commentForm = document.getElementById('commentForm');
    const commentPostId = document.getElementById('modalPostIdComment');
    const commentTextarea = document.getElementById('commentTextarea');
    const parentCmtId = document.getElementById('parentCmtId');
    const cancelReplyBtn = document.getElementById('cancelReplyBtn');
    const replyInfo = document.getElementById('replyInfo');
    const replyToName = document.getElementById('replyToName');
    const cancelReplyTop = document.getElementById('cancelReplyTop');

    // コメントを見るボタン
    document.querySelectorAll('.comment-btn').forEach(btn=>{
        btn.addEventListener('click', e=>{
            e.stopPropagation();
            const postDiv = btn.closest('.post');
            const postId = postDiv.dataset.postId;
            commentPostId.value = postId;

            commentModal.style.display = 'flex';
            loadComments(postId);
        });
    });

    // モーダル閉じるボタン
    closeCommentModal.addEventListener('click', () => {
        commentModal.style.display = 'none';
        commentList.innerHTML = '';
        cancelReply();
    });

    // 返信キャンセル
    function cancelReply(){
        parentCmtId.value = '';
        commentTextarea.placeholder = 'コメントを書く...';
        replyInfo.style.display = 'none';
        document.querySelectorAll('.comment-item').forEach(c=>c.classList.remove('reply-target'));
        commentTextarea.focus();
    }

    cancelReplyBtn.onclick = cancelReply;
    cancelReplyTop.onclick = cancelReply;

    // コメント取得
    function loadComments(postId){
        const scrollPos = commentList.scrollTop;
        fetch(`../home/add_comment.php?post_id=${postId}`)
        .then(res => res.text())
        .then(html => {
            commentList.innerHTML = html;
            commentList.scrollTop = scrollPos;
            attachReplyButtons();
        })
        .catch(()=>commentList.textContent='コメント取得失敗');
    }

    // 返信ボタン
    function attachReplyButtons(){
        document.querySelectorAll('.reply-btn').forEach(btn=>{
            btn.onclick = ()=>{
                parentCmtId.value = btn.dataset.parentId;
                replyToName.textContent = btn.dataset.userName;
                replyInfo.style.display = 'flex';
                document.querySelectorAll('.comment-item').forEach(c => c.classList.remove('reply-target'));
                btn.closest('.comment-item').classList.add('reply-target');
                commentTextarea.placeholder = `@${btn.dataset.userName} に返信`;
                commentTextarea.focus();
            }
        });
    }

    // コメント送信
    commentForm.addEventListener('submit', e => {
        e.preventDefault();
        const comment = commentTextarea.value.trim();
        if(!comment) return;

        const data = new URLSearchParams();
        data.append('post_id', commentPostId.value);
        data.append('comment', comment);
        if(parentCmtId.value) data.append('parent_cmt_id', parentCmtId.value);

        fetch('../home/add_comment.php', {method:'POST', body:data})
        .then(()=> {
            commentTextarea.value = '';
            cancelReply();
            loadComments(commentPostId.value);  
        })
        .catch(()=>alert('コメント送信失敗'));
    });

    // Enterで送信
    commentTextarea.addEventListener('keydown', e=>{
        if(e.key==='Enter' && !e.shiftKey){
            e.preventDefault();
            commentForm.requestSubmit();
        }
    });

});

</script>
</main>
</body>
</html>
