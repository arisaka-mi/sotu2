<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../login/login.php");
    exit;
}

require_once '../login/config.php';

function getProfileImg($filename) {
    $baseDir = __DIR__ . '/../profile/u_img/';
    if (!empty($filename) && file_exists($baseDir . $filename)) {
        return '../profile/u_img/' . $filename;
    } else {
        return '../profile/u_img/default.png';
    }
}

// ★ 追加（開発中だけでOK）
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

$posts   = $_SESSION['posts'] ?? [];
$keyword = $_SESSION['keyword'] ?? '';

unset($_SESSION['posts'], $_SESSION['keyword']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>検索結果</title>
<style>
main{max-width:800px;margin:40px auto;padding:0 16px;}
.post-list{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}

.post{
    aspect-ratio:3/4;
    background:#fff;
    border:1px solid #ccc;
    border-radius:12px;
    padding:10px;
    cursor:pointer;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    transition:.2s;
}
.post:hover{
    transform:translateY(-4px);
    box-shadow:0 6px 16px rgba(0,0,0,.15);
}
/* 画像 */
.post img{
    width:100%;
    aspect-ratio:1/1;
    object-fit:cover;
    border-radius:8px;
    margin-bottom:8px;
    flex-shrink:0;
}
/* ===== テキストエリア ===== */
.post-body{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    overflow:hidden;
}
.post-text{
    font-size:14px;
    line-height:1.6;
    font-weight:500;
    margin-bottom:4px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    flex-shrink:0;
}
.post small{
    font-size:12px;
    color:#666;
    flex-shrink:0;
}
.post p{
    font-size:14px;
    line-height:1.6;
    margin-bottom:6px;
    display:-webkit-box;
    -webkit-line-clamp:2;   /* ← 3 → 2 に */
    -webkit-box-orient:vertical;
    overflow:hidden;
}
/* ===== タグ ===== */
.post-tags{
    display:flex;
    gap:6px;
    margin-top:6px;
    flex-shrink:0;
}

.tag{
    font-size:10px;
    padding:3px 8px;
    background:#f5f5f5;
    color:#666;
    border-radius:999px;
    cursor:pointer;
    white-space:nowrap;
}
.tag:hover{background:#333;color:#fff;}
.tag.more{background:transparent;color:#999;padding:0 4px;}


/* 検索バー */
.text_kwd { box-sizing: border-box; position: relative; border: 1px solid #999;
            padding: 4px 40px 4px 12px; border-radius: 20px; height: 2.3em; width: 400px; overflow: hidden;
            background: #fff; margin: 0 auto 20px auto; }
.text_kwd input[type="text"]{ border: none; height: 100%; width: 100%; font-size: 14px; }
.text_kwd input[type="text"]:focus { outline: none; }
.text_kwd a { position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
            display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; }
.text_kwd .search_btn { width: 18px; height: 18px; cursor: pointer; }

hr{
    margin-bottom: 20px;
}

.back-wrap{
    display: flex;
    justify-content: center;
    margin: 30px 0 10px;
}

.back-btn{
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 10px 20px;
    border-radius: 999px;

    background: #fff;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;

    border: 1px solid #ddd;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);

    transition: all 0.2s ease;
}

.back-btn:hover{
    background: #333;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

/* ===== 空状態（投稿なし） ===== */
.empty-state{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:12px;

    margin:80px auto;
    padding:40px 24px;
    max-width:420px;

    background:#fff;
    border-radius:20px;
    text-align:center;
}

.empty-icon{
    font-size:48px;
    line-height:1;
}

.empty-state h2{
    font-size:18px;
    font-weight:600;
    color:#333;
}

.empty-state p{
    font-size:14px;
    color:#666;
    line-height:1.6;
}

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
.modal-tags{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-bottom:8px;
}
.modal-tags .tag{
    font-size:12px;
    padding:4px 12px;
    border-radius:999px;
    background:#eee;
}


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
    <!-- 検索フォーム -->
    <form method="get" action="search_hit.php" class="text_kwd">
        <input type="text" name="keyword" size="25" placeholder="キーワード検索" value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" style="all:unset;position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;">
            <img src="../search/img/search_edge.PNG" alt="search" class="search_btn">
        </button>
    </form>

    <hr>

    <div class="back-wrap">
        <a href="search.php" class="back-btn">
            ← 検索に戻る
        </a>
    </div>

<?php if($posts): ?>
<div class="post-list">
<?php foreach($posts as $post):
    // 画像URL
    $img_url = !empty($post['media_url'])
        ? '../home/' . $post['media_url']   // ← home/uploads のまま
        : '';

    // ユーザーアイコン
    $userImg = getProfileImg($post['pro_img']);

    $tags = [];
    if (!empty($post['tags'])) {
        $tags = explode(',', $post['tags']);
    }
?>
<div class="post"
    data-post-id="<?= (int)($post['post_id'] ?? 0) ?>"
    data-img="<?= htmlspecialchars($img_url, ENT_QUOTES) ?>"
    data-text="<?= htmlspecialchars($post['content_text'], ENT_QUOTES) ?>"
    data-date="投稿日: <?= htmlspecialchars($post['created_at'], ENT_QUOTES) ?>"
    data-user="<?= htmlspecialchars($post['u_name'], ENT_QUOTES) ?>"
    <?php $userImg = getProfileImg($post['pro_img']); ?>
    data-user-img="<?= htmlspecialchars($userImg, ENT_QUOTES) ?>"
    data-likes="<?= (int)($post['like_count'] ?? 0) ?>"
    data-comments="<?= (int)($post['comment_count'] ?? 0) ?>"
    data-tags="<?= htmlspecialchars($post['tags'] ?? '', ENT_QUOTES) ?>">

    <?php if($img_url && file_exists($img_url)): ?>
        <img src="<?= htmlspecialchars($img_url, ENT_QUOTES) ?>">
    <?php endif; ?>
    <div class="post-body">
        <p class="post-text"><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
        <small>投稿日: <?= htmlspecialchars($post['created_at']) ?></small>

        <div class="post-tags">
            <?php foreach(array_slice($tags,0,2) as $tag): ?>
                <span class="tag" data-tag="<?= htmlspecialchars($tag, ENT_QUOTES) ?>">
                    #<?= htmlspecialchars($tag) ?>
                </span>
            <?php endforeach; ?>
            <?php if(count($tags) > 2): ?>
                <span class="tag more">…</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h2>投稿が見つかりません</h2>
        <p>
            条件に合う投稿がありません。<br>
            キーワードを変えて検索してみてください。
        </p>
    </div>
<?php endif; ?>

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
            <div id="modalTags" class="modal-tags"></div>
            <div class="post-actions">
                <input type="hidden" id="modalPostIdLike">
                <button type="button" class="like-btn" id="likeBtn">
                    <img src="../search/img/like_edge.PNG"
                        id="likeIcon"
                        class="like-icon"
                        data-liked="0">
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
        <div id="replyInfo" class="reply-info" style="display:none;">
            <span id="replyToName"></span> 返信中
            <button type="button" id="cancelReplyTop">×</button>
        </div>
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
/* ===============================
   グローバル変数
================================ */
let currentPostId = null;

/* ===============================
   DOM取得
================================ */
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

const openCommentBtn = document.getElementById('openCommentBtn');
const closeBtn = document.querySelector('.modal-close');

const commentList = document.getElementById('modalCommentsArea');
const commentForm = document.getElementById('commentForm');
const commentPostId = document.getElementById('modalPostIdComment');
const modalPostIdLike = document.getElementById('modalPostIdLike');

const commentTextarea = document.getElementById('commentTextarea');
const parentCmtId = document.getElementById('parentCmtId');

const modalTags = document.getElementById('modalTags');

/* ===============================
   投稿クリック → モーダル表示
================================ */
document.querySelectorAll('.post').forEach(post => {
    post.addEventListener('click', () => {

        currentPostId = post.dataset.postId;

        modalPostIdLike.value = currentPostId;
        commentPostId.value = currentPostId;

        modalImg.src = post.dataset.img || '';
        modalText.textContent = post.dataset.text;
        modalDate.textContent = post.dataset.date;
        modalUser.textContent = post.dataset.user;
        modalUserImg.src = post.dataset.userImg;

        modalLikes.textContent = post.dataset.likes;
        modalCommentsCount.textContent = post.dataset.comments;

        // 画像なし対策
        modalImg.style.display = post.dataset.img ? 'block' : 'none';

        // タグ
        modalTags.innerHTML = '';
        if(post.dataset.tags){
            post.dataset.tags.split(',').forEach(tag=>{
                const span = document.createElement('span');
                span.className = 'tag';
                span.textContent = '#' + tag;
                modalTags.appendChild(span);
            });
        }

        modal.style.display = 'flex';
    });
});

/* ===============================
   いいね処理
================================ */
likeBtn.addEventListener('click', async (e) => {
    e.stopPropagation();
    if(!currentPostId) return;

    const formData = new FormData();
    formData.append('post_id', currentPostId);

    try{
        const res = await fetch('../home/toggle_like.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if(data.status !== 'ok'){
            alert('いいね失敗');
            return;
        }

        // モーダル更新
        modalLikes.textContent = data.like_count;
        likeIcon.src = data.liked
            ? '../search/img/like_edge_2.PNG'
            : '../search/img/like_edge.PNG';

        // ★ 一覧側 dataset も同期（search.php と同じ）
        const postEl = document.querySelector(
            `.post[data-post-id="${currentPostId}"]`
        );
        if(postEl){
            postEl.dataset.likes = data.like_count;
        }

    }catch(err){
        console.error(err);
        alert('通信エラー');
    }
});


/* ===============================
   コメントモーダル表示
================================ */
openCommentBtn.addEventListener('click', (e)=>{
    e.stopPropagation();
    if(!currentPostId) return;

    commentModal.style.display = 'flex';
    loadComments(currentPostId);
});

/* ===============================
   コメント取得
================================ */
function loadComments(postId){
    fetch(`../home/add_comment.php?post_id=${postId}`)
        .then(res => res.text())
        .then(html => {
            commentList.innerHTML = html;
            attachReplyButtons();
        })
        .catch(()=> commentList.textContent = 'コメント取得失敗');
}

/* ===============================
   返信ボタン
================================ */
function attachReplyButtons(){
    document.querySelectorAll('.reply-btn').forEach(btn=>{
        btn.onclick = () => {
            parentCmtId.value = btn.dataset.parentId;

            document.getElementById('replyToName').textContent = btn.dataset.parentUser;
            document.getElementById('replyInfo').style.display = 'flex';

            commentTextarea.placeholder = `@${btn.dataset.parentUser} に返信`;
            commentTextarea.focus();
        }
    });
}

/* ===============================
   コメント送信
================================ */
commentForm.addEventListener('submit', e=>{
    e.preventDefault();

    const comment = commentTextarea.value.trim();
    if(!comment || !currentPostId) return;

    const data = new URLSearchParams();
    data.append('post_id', currentPostId);
    data.append('comment', comment);
    if(parentCmtId.value){
        data.append('parent_cmt_id', parentCmtId.value);
    }

    fetch('../home/add_comment.php', {
        method: 'POST',
        body: data
    }).then(()=>{

        commentTextarea.value = '';
        parentCmtId.value = '';
        document.getElementById('replyInfo').style.display = 'none';

        loadComments(currentPostId);

        // モーダル更新
        const newCount = Number(modalCommentsCount.textContent) + 1;
        modalCommentsCount.textContent = newCount;

        // ★ 一覧側 dataset 同期（重要）
        const postEl = document.querySelector(
            `.post[data-post-id="${currentPostId}"]`
        );
        if(postEl){
            postEl.dataset.comments = newCount;
        }
    });
});


/* ===============================
   モーダル閉じる
================================ */
closeBtn.addEventListener('click', ()=>{
    modal.style.display = 'none';
    commentModal.style.display = 'none';
});

modal.addEventListener('click', e=>{
    if(e.target === modal){
        modal.style.display = 'none';
        commentModal.style.display = 'none';
    }
});

document.addEventListener('keydown', e=>{
    if(e.key === 'Escape'){
        modal.style.display = 'none';
        commentModal.style.display = 'none';
    }
});
</script>

</main>
</body>
</html>
