<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

require_once('../login/config.php');
$user_id = $_SESSION['user_id'] ?? null;

function getProfileImg($filename) {
    $baseDir = __DIR__ . '/../profile/u_img/';
    if (!empty($filename) && file_exists($baseDir . $filename)) {
        return '../profile/u_img/' . $filename;
    } else {
        return '../profile/u_img/default.png';
    }
}


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
        WHERE p.visibility = 'public'
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

        $icon = getProfileImg($c['pro_img']);

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
main {
    max-width: 800px;        /* 投稿コンテンツの最大幅 */
    margin: 40px auto;
    padding: 0 16px;
    box-sizing: border-box;
}

/* 投稿全体 */
.post-list .post {
    width: 75%;             /* 親にフィット */
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-sizing: border-box;
    margin: 0 auto; /* 中央寄せ */
}

/* 投稿画像 */
.post-image-wrapper {
    width: 75%; /* 元は100% */
    margin: 0 auto; /* 中央寄せ */
    position: relative;
}
.post-media {
    width: 100%;       /* 横幅を親に合わせる */
    height: auto;      /* 高さは自動で縦横比維持 */
    object-fit: contain; /* 画像を切り取らずに表示 */
    display: block;
    border-radius: 12px;
    margin: 0 auto; /* 中央寄せ */
}

/* 投稿者情報オーバーレイ */
.post-user-overlay {
    position: absolute;  /* 画像上に重ねる */
    top: 8px;            /* 画像の上端からの距離 */
    left: 8px;           /* 画像の左端からの距離 */
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,0,0,0.15); /* 半透明黒で文字を見やすく */
    padding: 4px 8px;
    border-radius: 12px;
    color: #fff;
    z-index: 10;          /* 画像より上に表示 */
}

/* 投稿アイコン */
.post-footer {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-top: 8px;
}

.like-btn,
.comment-btn {
    all: unset;               /* デフォルトスタイルリセット */
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;                 /* アイコンと数字の間 */
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 14px;
    transition: background 0.2s, transform 0.2s;
}

.like-btn img,
.comment-btn img {
    width: 24px;
    height: 24px;
}

.like-btn:hover,
.comment-btn:hover {
    background: #e0e0e0;
    transform: scale(1.05);
}

.like-btn img,
.comment-btn img {
    width: 24px;
    height: 24px;
}


/* 投稿者情報＋投稿日のオーバーレイ */
.post-user-overlay {
    position: absolute;
    top: 12px;
    left: 12px;
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


#postModal .like-icon,
#postModal .comment-icon {
    background: #ddd;
}
#postModal .like-btn:hover .like-icon,
#postModal .comment-btn:hover .comment-icon { transform: scale(1.05); }



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
                <?php $icon = getProfileImg($u['pro_img']); ?>
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
        <?php $icon_path = getProfileImg($post['pro_img']); ?>

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
        <div class="post-text" style="margin-top:10px; padding:8px 0; font-size:14px; line-height:1.5; color:#333;">
            <?= nl2br(htmlspecialchars($post['content_text'])) ?>
        </div>

        <!-- 投稿タグ -->
        <?php $tags = isset($post['tags']) ? explode(', ', $post['tags']) : [];?>
        <?php if(!empty($tags)): ?>
            <div class="post-tags" style="margin-bottom:8px; display:flex; gap:6px; flex-wrap:wrap;">
                <?php foreach(array_slice($tags, 0, 2) as $tag): ?>
                    <span class="tag" style="background:#f0f0f0; padding:2px 6px; border-radius:8px; font-size:12px;">
                        #<?= htmlspecialchars($tag) ?>
                    </span>
                <?php endforeach; ?>
                <?php if(count($tags) > 2): ?>
                    <span class="tag more" style="font-size:12px;">…</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        <div class="post-footer">
            <!-- いいねボタン -->
            <button type="button" class="like-btn" data-post-id="<?= $post['post_id'] ?>">
                <img src="../home/img/like_edge.PNG" class="like-icon" data-liked="0">
                <span id="like-count-<?= $post['post_id'] ?>"><?= $post['like_count'] ?></span>
            </button>

            <!-- コメントボタン -->
            <button type="button" class="comment-btn" data-post-id="<?= $post['post_id'] ?>">
                <img src="../home/img/comment_edge.PNG" class="comment-icon" data-comments="0">
                <span id="comment-count-<?= $post['post_id'] ?>"><?= $post['comment_count'] ?></span>
            </button>
        </div>

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

    let lastPageScrollY = window.scrollY;

    /* ===== モーダルを閉じる共通処理 ===== */
    function closeCommentModalFunc() {
        commentModal.style.display = 'none';
        commentList.innerHTML = '';
        cancelReply();
    }

    /* ===== ページスクロールのみで閉じる ===== */
    window.addEventListener('scroll', () => {
        // モーダルが閉じているなら何もしない
        if (commentModal.style.display !== 'flex') return;

        // ページスクロールが発生したら閉じる
        if (Math.abs(window.scrollY - lastPageScrollY) >= 15) {
            closeCommentModalFunc();
        }

        lastPageScrollY = window.scrollY;
    });

    /* ===== コメントボタン ===== */
    document.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();

            const postDiv = btn.closest('.post');
            const postId = postDiv.dataset.postId;

            commentPostId.value = postId;
            commentModal.style.display = 'flex';

            lastPageScrollY = window.scrollY; // ★ 開いた瞬間の位置を記録
            loadComments(postId);
        });
    });

    /* ===== いいねボタン ===== */
    document.querySelectorAll('.like-btn').forEach(likeBtn => {
        likeBtn.addEventListener('click', async () => {
            const likeIcon = likeBtn.querySelector('.like-icon');
            const modalLikes = likeBtn.querySelector('span'); // ★ 修正
            const postId = likeBtn.dataset.postId;

            try {
                const formData = new FormData();
                formData.append('post_id', postId);

                const res = await fetch('../home/toggle_like.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status !== 'ok') {
                    alert('いいね処理に失敗しました');
                    return;
                }

                // アイコン切り替え
                if (data.liked) {
                    likeIcon.src = "../search/img/like_edge_2.PNG";
                    likeIcon.dataset.liked = "1";
                } else {
                    likeIcon.src = "../search/img/like_edge.PNG";
                    likeIcon.dataset.liked = "0";
                }

                // ★ 正しいいいね数を表示
                modalLikes.textContent = data.like_count;

            } catch (e) {
                console.error(e);
                alert('通信エラーが発生しました');
            }
        });
    });

    /* ===== 閉じるボタン ===== */
    closeCommentModal.addEventListener('click', closeCommentModalFunc);

    /* ===== 返信キャンセル ===== */
    function cancelReply(){
        parentCmtId.value = '';
        commentTextarea.placeholder = 'コメントを書く...';
        replyInfo.style.display = 'none';
        document.querySelectorAll('.comment-item')
            .forEach(c => c.classList.remove('reply-target'));
    }

    cancelReplyBtn.onclick = cancelReply;
    cancelReplyTop.onclick = cancelReply;

    /* ===== コメント取得 ===== */
    function loadComments(postId){
        fetch(`../home/add_comment.php?post_id=${postId}`)
            .then(res => res.text())
            .then(html => {
                commentList.innerHTML = html;
                attachReplyButtons();
            });
    }

    /* ===== 返信ボタン ===== */
    function attachReplyButtons(){
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.onclick = () => {
                parentCmtId.value = btn.dataset.parentId;
                replyToName.textContent = btn.dataset.userName;
                replyInfo.style.display = 'flex';
                commentTextarea.placeholder = `@${btn.dataset.userName} に返信`;
                commentTextarea.focus();
            };
        });
    }

    /* ===== コメント送信 ===== */
    commentForm.addEventListener('submit', e => {
        e.preventDefault();
        if (!commentTextarea.value.trim()) return;

        const data = new URLSearchParams({
            post_id: commentPostId.value,
            comment: commentTextarea.value
        });

        if (parentCmtId.value) {
            data.append('parent_cmt_id', parentCmtId.value);
        }

        fetch('../home/add_comment.php', { method:'POST', body:data })
            .then(() => {
                commentTextarea.value = '';
                cancelReply();
                loadComments(commentPostId.value);
            });
    });

    /* ===== Enter送信 ===== */
    commentTextarea.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            commentForm.requestSubmit();
        }
    });

});
</script>


</main>
</body>
</html>
