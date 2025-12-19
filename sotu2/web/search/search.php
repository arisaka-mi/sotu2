<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login/login.php"); exit; }
require_once '../login/config.php';

// おすすめ投稿取得
$sql_recommend = "
SELECT p.post_id, p.user_id, p.media_url, p.content_text, p.created_at,
       u.u_name, u.pro_img,
       (SELECT COUNT(*) FROM Comment c WHERE c.post_id=p.post_id) AS comment_count,
       (SELECT COUNT(*) FROM PostLike l WHERE l.post_id=p.post_id) AS like_count
FROM Post p
JOIN User u ON p.user_id = u.user_id
WHERE p.visibility='public'
ORDER BY p.created_at DESC
";
$recommended_posts = $pdo->query($sql_recommend)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>検索</title>
<style>
main{max-width:800px;margin:40px auto;padding:0 16px;}
.post-list{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.post{aspect-ratio:3/4;background:#fff;border:1px solid #ccc;border-radius:12px;padding:16px;cursor:pointer;display:flex;flex-direction:column;overflow:hidden;transition:transform 0.2s,box-shadow 0.2s;}
.post:hover{transform:translateY(-4px);box-shadow:0 6px 16px rgba(0,0,0,0.15);}
.post img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;margin-bottom:10px;}
.post p{font-size:14px;line-height:1.6;margin-bottom:auto;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
.post small{font-size:12px;color:#666;margin-top:8px;}

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

/* 投稿モーダル */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:1000;align-items:center;justify-content:center;}
.modal-content{background:#fff;width:90%;max-width:480px;max-height:90vh;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;position:relative;}
.modal-user-overlay{position:absolute;top:12px;left:12px;display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(0,0,0,0.55);color:#fff;z-index:10;}
.modal-user-overlay img{width:32px;height:32px;border-radius:50%;object-fit:cover;}
.modal-user-overlay span{font-size:14px;font-weight:bold;white-space:nowrap;}
.modal-body{flex:1;overflow-y:auto;padding:0 8px 12px;}
.modal-content img#modalImg{width:100%;max-width:480px;max-height:600px;object-fit:cover;margin:0 auto 12px;display:block;border-radius:12px;}
.modal-close{position:absolute;top:10px;right:10px;width:32px;height:32px;background:#fff;border-radius:50%;font-size:22px;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:30;}


/* 投稿一覧アイコン */
.like-btn,
.comment-btn {
    all: unset;        /* すべてのスタイルをリセット */
    cursor: pointer;   /* クリック可能にする */
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
#postModal .like-icon,
#postModal .comment-icon {
    width: 20px;
    height: 20px;
}
#postModal .like-btn:hover .like-icon,
#postModal .comment-btn:hover .comment-icon { transform: scale(1.05); }

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
    margin-bottom: 8px;
}
.comment-item{display:flex;gap:10px;align-items:flex-start;margin-bottom:4px;}
.comment-item img{width:32px;height:32px;border-radius:50%;object-fit:cover;}
.comment-body{flex:1;}
.comment-body strong{font-size:13px;display:block;}
.comment-body p{font-size:14px;margin-top:2px;line-height:1.4;}
.comment-replies{margin-left:30px;display:flex;flex-direction:column;gap:8px;}
.reply-btn{font-size:12px;background:none;border:none;color:#007bff;cursor:pointer;padding:0;margin-top:4px;}
.reply-btn:hover{text-decoration:underline;}
/* コメントフォーム部分 */
.comment-input-wrap {
    display: flex;
    gap: 8px;
    align-items: flex-end;
    /* 下部に固定 */
}
.comment-input-wrap textarea{flex:1;min-height:42px;resize:vertical;padding:10px 12px;border-radius:12px;border:1px solid #ccc;font-size:14px;}
.comment-input-wrap textarea:focus{outline:none;border-color:#666;}
.comment-submit{padding:0 16px;height:42px;border:none;border-radius:999px;background:#333;color:#fff;cursor:pointer;}
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

</style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <!-- 検索フォーム -->
    <form method="get" action="search_control.php" class="text_kwd">
        <input type="text" size="25" placeholder="キーワード検索">
        <a href="search_hit.php" data-title="search">
            <img src="../search/img/search_edge.PNG" alt="search" class="search_btn">
        </a>
    </form>

    <hr>

<h2>おすすめの投稿</h2>
<?php if(!empty($recommended_posts)): ?>
<div class="post-list">
<?php foreach($recommended_posts as $post):
    $img_url = !empty($post['media_url'])?str_replace('../home/uploads/','../search/uploads/',$post['media_url']):'';
?>
<div class="post"
    data-post-id="<?= $post['post_id'] ?>"
    data-img="<?= htmlspecialchars($img_url) ?>"
    data-text="<?= htmlspecialchars($post['content_text']) ?>"
    data-date="投稿日: <?= htmlspecialchars($post['created_at']) ?>"
    data-user="<?= htmlspecialchars($post['u_name']) ?>"
    data-user-img="<?= htmlspecialchars('../profile/'.($post['pro_img']?:'u_icon/default.png')) ?>"
    data-likes="<?= $post['like_count'] ?>"
    data-comments="<?= $post['comment_count'] ?>">
    <?php if($img_url && file_exists($img_url)): ?><img src="<?= htmlspecialchars($img_url) ?>"><?php endif; ?>
    <p><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
    <small>投稿日: <?= htmlspecialchars($post['created_at']) ?></small>
</div>
<?php endforeach; ?>
</div>
<?php else: ?><p>おすすめ投稿はありません。</p><?php endif; ?>

<!-- 投稿モーダル -->
<div id="postModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div class="modal-user-overlay">
            <img id="modalUserImg">
            <span id="modalUser"></span>
        </div>
        <img id="modalImg">
        <div class="modal-body">
            <p id="modalText"></p>
            <small id="modalDate"></small>
            <div class="post-actions">
                <input type="hidden" id="modalPostIdLike">
                <button type="button" class="like-btn" id="likeBtn">
                    <img src="../search/img/like_edge.PNG" id="likeIcon" class="like-icon">
                </button>
                <span id="modalLikes">0</span>
                <button type="button" class="comment-btn" id="openCommentBtn">
                    <img src="../search/img/comment_edge.PNG" id="commentIcon" class="comment-icon">
                </button>
                <span id="modalCommentsCount">0</span>
            </div>
        </div>
    </div>
</div>

<!-- コメントモーダル -->
<div id="commentModal">
    <h3>コメント</h3>
    <div id="modalCommentsArea"></div>
    <form id="commentForm">
        <input type="hidden" name="post_id" id="modalPostIdComment">
        <input type="hidden" name="parent_cmt_id" id="parentCmtId">
        <div class="comment-input-wrap">
            <textarea id="commentTextarea"  placeholder="コメントを書く..." required></textarea>
            <button type="submit" class="comment-submit">送信</button>
            <button type="button" id="cancelReplyBtn" style="display:none;">返信をキャンセル</button>
        </div>
    </form>
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

const commentList = document.getElementById('modalCommentsArea');
const commentForm = document.getElementById('commentForm');
const commentPostId = document.getElementById('modalPostIdComment');
const commentTextarea = document.getElementById('commentTextarea');
const parentCmtId = document.getElementById('parentCmtId');
const cancelReplyBtn = document.getElementById('cancelReplyBtn');

let currentPostId = null;

// 投稿クリックでモーダル表示
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
closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    commentModal.style.display = 'none'; // 追加：コメントモーダルも閉じる
});

modal.addEventListener('click', e => {
    if(e.target === modal){
        modal.style.display = 'none';
        commentModal.style.display = 'none'; // 追加：コメントモーダルも閉じる
    }
});

document.addEventListener('keydown', e => {
    if(e.key === 'Escape'){
        modal.style.display = 'none';
        commentModal.style.display = 'none'; // 追加：コメントモーダルも閉じる
    }
});


// コメントモーダル表示
openCommentBtn.addEventListener('click',()=>{
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
    }).catch(()=>commentList.textContent='コメント取得失敗');
}

// 親コメント返信
function attachReplyButtons(){
    document.querySelectorAll('.reply-btn').forEach(btn=>{
        btn.onclick=()=>{
            parentCmtId.value=btn.dataset.parentId;
            commentTextarea.placeholder=`@${btn.dataset.userName} に返信`;
            commentTextarea.focus();
            cancelReplyBtn.style.display='inline';
        }
    });
}

// 返信キャンセル
cancelReplyBtn.addEventListener('click',()=>{
    parentCmtId.value='';
    commentTextarea.placeholder='コメントを書く...';
    cancelReplyBtn.style.display='none';
    commentTextarea.focus();
});

// コメント送信
commentForm.addEventListener('submit',e=>{
    e.preventDefault();
    const comment = commentTextarea.value.trim();
    if(!comment) return;

    const data = new URLSearchParams();
    data.append('post_id', commentPostId.value);
    data.append('comment', comment);
    if(parentCmtId.value) data.append('parent_cmt_id', parentCmtId.value);

    fetch('../home/add_comment.php',{method:'POST',body:data})
    .then(()=>{
        commentTextarea.value='';
        parentCmtId.value='';
        commentTextarea.placeholder='コメントを書く...';
        cancelReplyBtn.style.display='none';
        loadComments(commentPostId.value);
        modalCommentsCount.textContent=Number(modalCommentsCount.textContent)+1;
    }).catch(()=>alert('コメント送信失敗'));
});

// Enter送信
commentTextarea.addEventListener('keydown',e=>{
    if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); commentForm.requestSubmit(); }
});
</script>

</main>
</body>
</html>
