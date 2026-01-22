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
.text_kwd {
    display: flex;          /* 横並び */
    align-items: center;    /* 垂直中央 */
    max-width: 400px;
    margin: 0 auto 20px auto;
    padding: 4px 8px;
    border: 1px solid #999;
    border-radius: 20px;
    background: #fff;
    box-sizing: border-box;
}
.text_kwd input[type="text"] {
    flex: 1;                /* 入力欄が残り全部 */
    border: none;
    height: 2.3em;
    font-size: 14px;
    outline: none;
    padding: 0 8px;
}
.text_kwd input[type="text"]:focus { outline: none; }
.text_kwd a { position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
            display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; }
.text_kwd button.search_btn_wrap {
    all: unset;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0 6px;
}

.text_kwd .search_btn {
    width: 18px;
    height: 18px;
}
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
.modal-content{
    width:90%;
    max-width:480px;
    height:90vh;                 /* 高さ固定 */
    background:#fff;
    border-radius:16px;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    position:relative;
}
.modal-image-area{
    flex:1;                         /* 余白を全部ここで吸収 */
    display:flex;
    align-items:center;             /* 縦中央 */
    justify-content:center;         /* 横中央 */
    padding:8px;
    overflow:hidden;

    margin: auto 0;                 /* ★ 上下中央配置の決定打 */
}


.modal-image-area img{
    max-width:100%;
    max-height:100%;
    width:auto;
    height:auto;
    object-fit:contain;          /* ← 全体表示 */
    border-radius:12px;
}
.modal-user-overlay{position:absolute;top:12px;left:12px;display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(0,0,0,0.55);color:#fff;z-index:10;}
.modal-user-overlay img{width:32px;height:32px;border-radius:50%;object-fit:cover;}
.modal-user-overlay span{font-size:14px;font-weight:bold;white-space:nowrap;}
.modal-body{
    display:flex;
    flex-direction:column;
    gap:4px;               /* ← ここで間隔を管理 */
    padding:6px 12px ;  /* 下を詰める */
}
.modal-close{
    position:absolute;
    top:10px;
    right:10px;
    width:32px;
    height:32px;
    background:#fff;
    border-radius:50%;
    font-size:22px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    z-index:1200;
    box-shadow:0 2px 6px rgba(0,0,0,.2);
}
.modal-actions{
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px 12px;
    border-top:1px solid #eee;
    background:#fff;
    flex-shrink:0;
    margin-top:auto;
}
.modal-tags .tag{
    font-size:12px;
    padding:4px 12px;
    border-radius:999px;
    background:#eee;
}
.modal-tags:empty{
    display:none;          /* ← これが最重要 */
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
/* × ボタン */
.comment-close{
    position:absolute;
    top:10px;
    right:10px;
    width:32px;
    height:32px;
    background:#fff;
    border-radius:50%;
    font-size:22px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    z-index:1200;
    box-shadow:0 2px 6px rgba(0,0,0,.2);
}

.comment-close:hover{
    background:#f0f0f0;
}

/* 返信対象コメントの強調 */
.comment-item.reply-target {
    background: #f5f7ff;
    border-radius: 10px;
    padding: 6px;
}
.layout {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 16px;
}
</style>
</head>
<body>
<header>
    <div class="layout">
        <?php include '../navigation/nav.php'; ?>
    </div>
</header>
<main class="layout">
    <!-- 検索フォーム -->
    <form method="get" action="search_control.php" class="text_kwd">
        <input type="text" name="keyword"
            placeholder="キーワード検索"
            value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="search_btn_wrap">
            <img src="../search/img/search_edge.PNG" alt="search" class="search_btn">
        </button>
    </form>


    <hr>


    <div class="back-wrap">
        <a href="search.php" class="back-btn">
            ← 検索に戻る
        </a>
    </div>

<!-- ★ 投稿一覧 -->
<?php if($posts): ?>
<div class="post-list">
<?php foreach($posts as $post):
    // サーバー用パス
    $img_path = !empty($post['media_url']) ? __DIR__ . '/../home/' . $post['media_url'] : '';

    // ブラウザ用パス
    $img_url = !empty($post['media_url']) ? '../home/' . $post['media_url'] : '';

    $tags = [];
    if (!empty($post['tags'])) {
        $tags = explode(',', $post['tags']);
    }
    // 投稿者の骨格・パーソナルカラー取得（自分も含む）
    $user_tags = [];
    $stmt_tags = $pdo->prepare("
        SELECT 
            b.bt_name,
            p1.pc_name AS pc1_name,
            p2.pc_name AS pc2_name
        FROM User u
        LEFT JOIN Body_type b ON u.bt_id = b.bt_id
        LEFT JOIN Parsonal_color p1 ON u.pc_id = p1.pc_id
        LEFT JOIN Parsonal_color p2 ON u.pc_second_id = p2.pc_id
        WHERE u.user_id = :uid
    ");
    $stmt_tags->bindValue(':uid', $post['user_id'], PDO::PARAM_INT);
    $stmt_tags->execute();
    $tag_row = $stmt_tags->fetch(PDO::FETCH_ASSOC);

    // NULL でなければ追加
    if (!empty($tag_row['bt_name'])) $user_tags[] = $tag_row['bt_name'];
    if (!empty($tag_row['pc1_name'])) $user_tags[] = $tag_row['pc1_name'];
    if (!empty($tag_row['pc2_name'])) $user_tags[] = $tag_row['pc2_name'];

    // 投稿タグと統合
    $all_tags = array_merge($tags, $user_tags);
?>
<?php
$userIcon = getProfileImg($post['pro_img']);
?>
<div class="post"
    data-post-id="<?= $post['post_id'] ?>"
    data-img="<?= htmlspecialchars($img_url) ?>"
    data-text="<?= htmlspecialchars($post['content_text']) ?>"
    data-date="投稿日: <?= htmlspecialchars($post['created_at']) ?>"
    data-user="<?= htmlspecialchars($post['u_name']) ?>"
    data-user-img="<?= htmlspecialchars($userIcon, ENT_QUOTES) ?>"
    data-likes="<?= $post['like_count'] ?>"
    data-liked="<?= $post['is_liked'] ? 1 : 0 ?>"
    data-comments="<?= $post['comment_count'] ?>"
    data-tags="<?= htmlspecialchars(implode(',', $all_tags), ENT_QUOTES) ?>"
>
    <?php if($img_path && file_exists($img_path)): ?>
        <img src="<?= htmlspecialchars($img_url, ENT_QUOTES) ?>">
    <?php endif; ?>

    <div class="post-body">
        <p class="post-text"><?= nl2br(htmlspecialchars($post['content_text'])) ?></p>
        <small>投稿日: <?= htmlspecialchars($post['created_at']) ?></small>

        <div class="post-tags">
            <?php foreach(array_slice($tags,0,2) as $tag): ?>
                <span class="tag" data-tag="<?= htmlspecialchars($tag) ?>">
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

    <!-- ★ 画像専用エリア -->
    <div class="modal-image-area">
      <img id="modalImg">
    </div>

    <!-- ★ テキスト -->
    <div class="modal-body">
      <p id="modalText"></p>
      <small id="modalDate"></small>
      <div id="modalTags" class="modal-tags"></div>
    </div>

    <!-- ★ 最下部アクション -->
    <div class="modal-actions">
      <button class="like-btn" id="likeBtn">
        <img src="../search/img/like_edge.PNG" id="likeIcon" class="like-icon">
      </button>
      <span id="modalLikes">0</span>

      <button class="comment-btn" id="openCommentBtn">
        <img src="../search/img/comment_edge.PNG" class="comment-icon">
      </button>
      <span id="modalCommentsCount">0</span>
    </div>
  </div>
</div>

<!-- コメントモーダル -->
<div id="commentModal">
    <!-- ★ 追加：閉じるボタン -->
    <span class="comment-close">&times;</span>
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

const modalTags = document.getElementById('modalTags');

document.querySelectorAll('.tag').forEach(tag=>{
    tag.addEventListener('click', e=>{
        e.stopPropagation();
        const name = tag.dataset.tag;
        location.href = `search_control.php?keyword=${encodeURIComponent('#' + name)}`;
    });
});


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

        // ✅ タグ表示（ここが正しい）
        modalTags.innerHTML = '';

        const tags = p.dataset.tags?.trim();
        if(tags !== ''){
            tags.split(',').forEach(tag=>{
                const span = document.createElement('span');
                span.className = 'tag';
                span.textContent = '#' + tag;
                modalTags.appendChild(span);
            });
        }


        modal.style.display = 'flex';
    });
});
document.querySelectorAll('.tag').forEach(tag=>{
    tag.addEventListener('click', e=>{
        e.stopPropagation(); // モーダル開かない
        const name = tag.dataset.tag;
        location.href = `search_control.php?keyword=${encodeURIComponent(name)}`;
    });
});

//いいね
likeBtn.addEventListener('click', () => {

    const data = new URLSearchParams();
    data.append('post_id', currentPostId);

    fetch('../home/toggle_like.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.status !== 'ok') return;

        // 数値更新
        modalLikes.textContent = res.like_count;

        // アイコン切替
        if (res.liked) {
            likeIcon.src = "../search/img/like_edge_2.PNG";
            likeIcon.dataset.liked = "1";
        } else {
            likeIcon.src = "../search/img/like_edge.PNG";
            likeIcon.dataset.liked = "0";
        }

        // 一覧側データも同期
        const post = document.querySelector(
            `.post[data-post-id="${currentPostId}"]`
        );
        if (post) {
            post.dataset.likes = res.like_count;
            post.dataset.liked = res.liked ? 1 : 0;
        }
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
    commentModal.style.display='flex';
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
        btn.onclick = () => {
            parentCmtId.value = btn.dataset.parentId;

            // 名前表示
            document.getElementById('replyToName').textContent = btn.dataset.userName;
            document.getElementById('replyInfo').style.display = 'flex';

            // 対象コメントをハイライト
            document.querySelectorAll('.comment-item')
                .forEach(c => c.classList.remove('reply-target'));
            btn.closest('.comment-item').classList.add('reply-target');

            commentTextarea.placeholder = `@${btn.dataset.userName} に返信`;
            commentTextarea.focus();
        }
    });
}

// 返信キャンセル
function cancelReply() {
    parentCmtId.value = '';
    commentTextarea.placeholder = 'コメントを書く...';

    document.getElementById('replyInfo').style.display = 'none';
    document.querySelectorAll('.comment-item')
        .forEach(c => c.classList.remove('reply-target'));

    commentTextarea.focus();
}

cancelReplyBtn.onclick = cancelReply;
document.getElementById('cancelReplyTop').onclick = cancelReply;


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

//コメントを閉じる
const commentCloseBtn = document.querySelector('.comment-close');

commentCloseBtn.addEventListener('click', () => {
    commentModal.style.display = 'none';
});


// Enter送信
commentTextarea.addEventListener('keydown',e=>{
    if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); commentForm.requestSubmit(); }
});
</script>

</main>
</body>
</html>
