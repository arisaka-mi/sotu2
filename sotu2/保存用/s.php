<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

require_once '../login/config.php'; // DB接続

// おすすめ投稿を取得（最新の public 投稿）
$sql_recommend = "
SELECT 
    p.post_id,
    p.user_id,
    p.media_url,
    p.content_text,
    p.created_at,
    u.u_name,
    u.pro_img,
    (SELECT COUNT(*) FROM Comment c WHERE c.post_id = p.post_id) AS comment_count,
    (SELECT COUNT(*) FROM PostLike l WHERE l.post_id = p.post_id) AS like_count
FROM Post p
JOIN User u ON p.user_id = u.user_id
WHERE p.visibility = 'public'
ORDER BY p.created_at DESC
";

$stmt_recommend = $pdo->query($sql_recommend);
$recommended_posts = $stmt_recommend->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>検索</title>
    <style>
        main { max-width: 800px; margin: 40px auto; padding: 0 16px; }
        .post-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .post { aspect-ratio: 3 / 4; background: #fff; border: 1px solid #ccc; border-radius: 12px;
            padding: 16px; cursor: pointer; display: flex; flex-direction: column; overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s; }
        .post:hover { transform: translateY(-4px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
        .post img { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .post p { font-size: 14px; line-height: 1.6; margin-bottom: auto;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .post small { font-size: 12px; color: #666; margin-top: 8px; }

        /* 検索バー */
        .text_kwd { box-sizing: border-box; position: relative; border: 1px solid #999;
            padding: 4px 40px 4px 12px; border-radius: 20px; height: 2.3em; width: 400px; overflow: hidden;
            background: #fff; margin: 0 auto 20px auto; }
        .text_kwd input[type="text"]{ border: none; height: 100%; width: 100%; font-size: 14px; }
        .text_kwd input[type="text"]:focus { outline: none; }
        .text_kwd a { position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
            display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; }
        .text_kwd .search_btn { width: 18px; height: 18px; cursor: pointer; }

        @media (max-width: 768px) { .post-list { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .post-list { grid-template-columns: 1fr; } }

        /* ===== 投稿モーダル ===== */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; width: 90%; max-width: 480px; max-height: 90vh;
            border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { position: sticky; top: 0; background: #fff; z-index: 20; padding-bottom: 8px; }
        .modal-user-overlay { position: absolute; top: 12px; left: 12px; display: flex;
            align-items: center; gap: 8px; padding: 6px 10px; border-radius: 999px;
            background: rgba(0,0,0,0.55); color: #fff; z-index: 10; }
        .modal-user-overlay img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .modal-user-overlay span { font-size: 14px; font-weight: bold; white-space: nowrap; }
        .modal-body { flex: 1; overflow-y: auto; padding: 0 8px 12px; }
        .modal-content img#modalImg { width: 100%; max-width: 480px; max-height: 600px; object-fit: cover;
            margin: 0 auto 12px; display: block; border-radius: 12px; }
        .modal-close { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px;
            background: #fff; border-radius: 50%; font-size: 22px; display: flex;
            align-items: center; justify-content: center; cursor: pointer; z-index: 30; }
        .post-actions { display: flex; align-items: center; gap: 12px; }
        .like-btn, .comment-btn { background: none; border: none; padding: 0; cursor: pointer; }
        .like-icon, .comment-icon { width: 28px; height: 28px; transition: transform 0.15s ease; }
        .like-btn:hover .like-icon, .comment-btn:hover .comment-icon { transform: scale(1.1); }

        /* ===== コメントモーダル ===== */
        #commentList { display:flex; flex-direction: column; gap:12px; max-height:400px; overflow-y:auto; padding:4px 0; }
        .comment-item { display:flex; gap:10px; align-items:flex-start; margin-bottom:4px; }
        .comment-user-icon { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .comment-body { flex:1; word-break: break-word; min-width:0; }
        .comment-body strong { font-size: 13px; display:block; }
        .comment-body p { font-size: 14px; margin-top:2px; line-height:1.4; }
        .reply-btn { font-size: 12px; background: none; border:none; color:#007bff; cursor:pointer; padding:0; margin-top:4px; }
        .reply-btn:hover { text-decoration: underline; }

        #commentTextareaModal { width:100%; min-height:50px; border-radius:12px; border:1px solid #ccc; padding:8px; resize:vertical; }
        .comment-submit { height:40px; padding:0 16px; border-radius:999px; border:none; background:#333; color:#fff; cursor:pointer; margin-left:8px; }
    </style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <!-- 検索バー -->
    <form method="get" action="search_control.php" class="text_kwd">
        <input type="text" size="25" placeholder="キーワード検索">
        <a href="search_hit.php" data-title="search">
            <img src="../search/img/search_edge.PNG" alt="search" class="search_btn">
        </a>
    </form>

    <hr>

    <h2>おすすめの投稿</h2>

    <?php if (!empty($recommended_posts)): ?>
        <div class="post-list">
        <?php foreach ($recommended_posts as $post):
            $image_url = !empty($post['media_url']) ? str_replace('../home/uploads/', '../search/uploads/', $post['media_url']) : '';
        ?>
            <div class="post"
                data-post-id="<?= (int)$post['post_id'] ?>"
                data-img="<?= htmlspecialchars($image_url) ?>"
                data-text="<?= htmlspecialchars($post['content_text'] ?? '') ?>"
                data-date="投稿日: <?= htmlspecialchars($post['created_at']) ?>"
                data-user="<?= htmlspecialchars($post['u_name']) ?>"
                data-user-img="<?= htmlspecialchars('../profile/' . ($post['pro_img'] ?: 'u_icon/default.png')) ?>"
                data-likes="<?= (int)$post['like_count'] ?>"
                data-comments="<?= (int)$post['comment_count'] ?>">
                
                <?php if ($image_url && file_exists($image_url)): ?>
                    <img src="<?= htmlspecialchars($image_url) ?>" alt="投稿画像">
                <?php endif; ?>

                <p><?= nl2br(htmlspecialchars($post['content_text'] ?? '内容なし')) ?></p>
                <small>投稿日: <?= htmlspecialchars($post['created_at'] ?? '') ?></small>

                <div class="post-actions">
                    <button type="button" class="like-btn">
                        <img src="../search/img/like_edge.PNG" class="like-icon">
                    </button>
                    <span><?= (int)$post['like_count'] ?></span>

                    <button type="button" class="comment-btn">
                        <img src="../search/img/comment_edge.PNG" class="comment-icon">
                    </button>
                    <span><?= (int)$post['comment_count'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>おすすめ投稿はありません。</p>
    <?php endif; ?>

    <!-- ===== コメントモーダル ===== -->
    <!-- 投稿モーダル -->
    <div id="postModal" class="modal">
        <div class="modal-content" id="postModalContent">
            <span class="modal-close" id="closePostModal">&times;</span>
            <div class="modal-user-overlay">
                <img id="modalUserImg">
                <span id="modalUser"></span>
            </div>
            <img id="modalImg">
            <p id="modalText"></p>
            <small id="modalDate"></small>
            <div class="post-actions">
                <button id="likeBtn">Like</button>
                <span id="modalLikes">0</span>
                <button id="openCommentBtn">コメント</button>
                <span id="modalCommentsCount">0</span>
            </div>
        </div>
    </div>

    <!-- コメントモーダル（右側） -->
    <div id="commentModal" class="modal" style="display:none; position:fixed; top:0; left:calc(50% + 10px); width:350px; height:90vh; background:#fff; border-radius:16px; z-index:1100; overflow-y:auto; box-shadow:0 4px 16px rgba(0,0,0,0.2);">
        <div style="padding:12px;">
            <span id="closeCommentModal" style="float:right; cursor:pointer;">&times;</span>
            <h3>コメント</h3>
            <div id="commentList" style="max-height:70%; overflow-y:auto;"></div>
            <form id="commentFormModal">
                <input type="hidden" name="post_id" id="commentPostId">
                <textarea id="commentTextareaModal" placeholder="コメントを書く..." style="width:100%; min-height:50px; margin-top:8px;"></textarea>
                <button type="submit">送信</button>
            </form>
        </div>
    </div>

<script>
const modal = document.getElementById('postModal');
const commentModal = document.getElementById('commentModal');
const modalImg = document.getElementById('modalImg');
const modalText = document.getElementById('modalText');
const modalDate = document.getElementById('modalDate');
const modalUser = document.getElementById('modalUser');
const modalUserImg = document.getElementById('modalUserImg');
const modalLikes = document.getElementById('modalLikes');
const modalCommentsCount = document.getElementById('modalCommentsCount');
const likeBtn = document.getElementById('likeBtn');
const likeIcon = document.getElementById('likeIcon');
const closeBtn = document.querySelector('.modal-close');
const openCommentBtn = document.getElementById('openCommentBtn');

const commentList = document.getElementById('commentList');
const commentFormModal = document.getElementById('commentFormModal');
const commentPostId = document.getElementById('commentPostId');
const commentTextareaModal = document.getElementById('commentTextareaModal');
const parentCmtId = document.getElementById('parentCmtId');
const cancelReplyBtn = document.getElementById('cancelReplyBtn');

let currentPostId = null;

// 投稿クリック
document.querySelectorAll('.post').forEach(p=>{
    p.addEventListener('click',()=>{
        currentPostId = p.dataset.postId;
        modalImg.src = p.dataset.img;
        modalText.textContent = p.dataset.text;
        modalDate.textContent = p.dataset.date;
        modalUser.textContent = p.dataset.user;
        modalUserImg.src = p.dataset.userImg;
        modalLikes.textContent = p.dataset.likes;
        modalCommentsCount.textContent = p.dataset.comments;
        commentPostId.value = currentPostId;
        modal.style.display='flex';
    });
});

// 投稿モーダル閉じる
closeBtn.addEventListener('click',()=>modal.style.display='none');
modal.addEventListener('click', e=>{if(e.target===modal) modal.style.display='none';});
document.addEventListener('keydown', e=>{if(e.key==='Escape') modal.style.display='none';});

// コメントモーダル表示
openCommentBtn.addEventListener('click', ()=>{
    commentModal.style.display='block';
    loadComments(currentPostId);
});

// コメント取得
function loadComments(postId){
    fetch(`../home/add_comment.php?post_id=${postId}`)
        .then(res=>res.text())
        .then(html=>{
            commentList.innerHTML = html;
            attachReplyButtons();
        })
        .catch(()=>commentList.textContent='コメント取得失敗');
}

// 親コメント返信
function attachReplyButtons(){
    document.querySelectorAll('.reply-btn').forEach(btn=>{
        btn.onclick = ()=>{
            parentCmtId.value = btn.dataset.parentId;
            commentTextareaModal.placeholder = `@${btn.dataset.userName} に返信...`;
            commentTextareaModal.focus();
            cancelReplyBtn.style.display='inline';
        }
    });
}

// 返信キャンセル
cancelReplyBtn.addEventListener('click', ()=>{
    parentCmtId.value='';
    commentTextareaModal.placeholder='コメントを書く...';
    cancelReplyBtn.style.display='none';
    commentTextareaModal.focus();
});

// コメント送信
commentFormModal.addEventListener('submit', e=>{
    e.preventDefault();
    const comment = commentTextareaModal.value.trim();
    if(!comment) return;

    const data = new URLSearchParams();
    data.append('post_id', commentPostId.value);
    data.append('comment', comment);
    if(parentCmtId.value) data.append('parent_cmt_id', parentCmtId.value);

    fetch('../home/add_comment.php',{
        method:'POST',
        body:data
    }).then(()=>{
        commentTextareaModal.value='';
        parentCmtId.value='';
        commentTextareaModal.placeholder='コメントを書く...';
        cancelReplyBtn.style.display='none';
        loadComments(commentPostId.value);
        modalCommentsCount.textContent = Number(modalCommentsCount.textContent)+1;
    }).catch(()=>alert('コメント送信失敗'));
});

// Enter送信
commentTextareaModal.addEventListener('keydown', e=>{
    if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); commentFormModal.requestSubmit(); }
});
</script>

</main>
</body>
</html>
