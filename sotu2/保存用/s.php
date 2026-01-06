<?php
session_start();
require_once('../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_from.php');
    exit();
}

// URLの user_id があれば他人のプロフィール、なければ自分
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];

// ★ ここで先に定義
$logged_in_user_id = $_SESSION['user_id'];

// URLの user_id
$profile_user_id = isset($_GET['user_id'])
    ? (int)$_GET['user_id']
    : $logged_in_user_id;

// プロフィール対象ユーザーの情報取得
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        u.u_name,
        u.pro_img,
        GROUP_CONCAT(t.tag_name) AS tags,
        (SELECT COUNT(*) FROM PostLike pl WHERE pl.post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM Comment c WHERE c.post_id = p.post_id) AS comment_count,
        EXISTS (
            SELECT 1 FROM PostLike pl
            WHERE pl.post_id = p.post_id
            AND pl.user_id = :login_user_id
        ) AS is_liked
    FROM Post p
    JOIN user u ON p.user_id = u.user_id
    LEFT JOIN PostTag pt ON p.post_id = pt.post_id
    LEFT JOIN Tag t ON pt.tag_id = t.tag_id
    WHERE p.user_id = :user_id
    GROUP BY p.post_id
    ORDER BY p.created_at DESC
");
$stmt->bindValue(':user_id', $profile_user_id, PDO::PARAM_INT);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->execute([
    ':user_id' => $profile_user_id,
    ':login_user_id' => $logged_in_user_id
]);

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);




if (!$user) {
    exit("ユーザー情報が見つかりません。");
}

// ログイン中ユーザーID
$logged_in_user_id = $_SESSION['user_id'];

// フォロー判定
$is_following = false;
if ($logged_in_user_id != $profile_user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM Follow WHERE follower_id = :follower AND followed_id = :followed");
    $stmt->execute([
        ':follower' => $logged_in_user_id,
        ':followed' => $profile_user_id
    ]);
    $is_following = $stmt->fetch() ? true : false;
}

// プロフィール画像
if (!empty($user['pro_img']) && file_exists(__DIR__ . '/u_icon/' . $user['pro_img'])) {
    $img_path = 'u_icon/' . $user['pro_img'];
} else {
    $img_path = 'u_icon/default.png';
}


// 表示用
$u_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
$u_name_id = htmlspecialchars($user['u_name_id'], ENT_QUOTES, 'UTF-8');
$u_text = htmlspecialchars($user['u_text'] ?? '', ENT_QUOTES, 'UTF-8');

// フォロー数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Follow WHERE follower_id = :id");
$stmt->execute([':id' => $profile_user_id]);
$follow_count = $stmt->fetchColumn();

// フォロワー数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Follow WHERE followed_id = :id");
$stmt->execute([':id' => $profile_user_id]);
$follower_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM PostLike pl WHERE pl.post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM Comment c WHERE c.post_id = p.post_id) AS comment_count
    FROM Post p
    WHERE p.user_id = :user_id
    ORDER BY p.created_at DESC
");
$stmt->execute([':user_id' => $profile_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール</title>
    <style>
        body { 
            font-family: sans-serif; 
            margin: 0; 
            padding: 0; 
        }
        main {
            max-width: 800px;   /* 好きな横幅 */
            margin: 40px auto;  /* ← これで中央寄せ */
            padding: 0 16px;    /* 画面端対策（スマホ） */
        }
        .post-list{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:12px;
        }
        .post:hover{
            transform:translateY(-4px);
            box-shadow:0 6px 16px rgba(0,0,0,.15);
        }

        .profile-container { 
            max-width: 500px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #fff; 
            border-radius: 10px; 
            text-align: center; 
        }

        .profile-icon {
            width: 120px; 
            height: 120px;
            border-radius: 50%; 
            object-fit: cover;
            margin: 0 auto 20px; 
            display: block;
            border: 1px solid #ebebebff;
        }

        h1 { 
            margin: 0; 
            font-size: 24px; 
        }
        h2 { margin: 5px 0 20px; 
            font-size: 18px; 
            color: #555; 
        }
        p { margin-bottom: 20px; 
            line-height: 1.5; 
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 10px;
            background: #c6c6c6ff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            width: 140px;
            border: none;
            outline: none;
            cursor: pointer;
        }
        .btn:hover { 
            background: #b5b5b5; 
        }

        .unfollow-btn {
            background: #ff6b6b !important;
            border: none !important;
            outline: none !important;
        }
        .unfollow-btn:hover {
            background: #e55b5b !important;
        }

        .follow-block { 
            display: flex; 
            justify-content: center; 
            gap: 40px; 
            margin: 10px 0 20px; 
        }
        .follow-item { 
            text-align: center; 
        }
        .follow-number { 
            font-size: 24px; 
            font-weight: bold; 
            color: #333; 
        }
        .follow-label { 
            font-size: 14px; 
            color: #555; 
            margin-top: 4px; 
        }

        /* 既存のスタイルの下に追加 */
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

    </style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <div class="profile-container">

        <img src="<?= htmlspecialchars($img_path, ENT_QUOTES) ?>" class="profile-icon">

        <h1><?= $u_name ?></h1>
        <h2>@<?= $u_name_id ?></h2>

        <p><?= $u_text ?></p>

        <div class="follow-block">
            <div class="follow-item">
                <a href="follow_list.php?user_id=<?= $profile_user_id ?>" style="text-decoration:none; color:inherit;">
                    <span class="follow-number"><?= $follow_count ?></span>
                    <span class="follow-label">フォロー</span>
                </a>
            </div>
            <div class="follow-item">
                <a href="follower_list.php?user_id=<?= $profile_user_id ?>" style="text-decoration:none; color:inherit;">
                    <span class="follow-number"><?= $follower_count ?></span>
                    <span class="follow-label">フォロワー</span>
                </a>
            </div>
        </div>


        <!-- フォローボタン -->
        <?php if ($logged_in_user_id != $profile_user_id): ?>
            <?php if ($is_following): ?>
                <form action="unfollow.php" method="POST">
                    <input type="hidden" name="followed_id" value="<?= $profile_user_id ?>">
                    <button class="btn unfollow-btn">フォロー解除</button>
                </form>
            <?php else: ?>
                <form action="follow.php" method="POST">
                    <input type="hidden" name="followed_id" value="<?= $profile_user_id ?>">
                    <button class="btn">フォローする</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 自分のプロフィールのときだけ表示 -->
        <?php if ($logged_in_user_id == $profile_user_id): ?>
            <a href="profile_setting.php" class="btn">プロフィール編集</a>
            <a href="../diagnosis/diagnosis_form.php" class="btn">診断画面へ</a>
        <?php endif; ?>

<!-- ★ 投稿一覧 -->
<?php if ($logged_in_user_id == $profile_user_id): ?>

    <?php if (!empty($posts)): ?>
    <div class="post-list">

        <?php foreach ($posts as $post):
            $img_url = !empty($post['media_url']) ? '../home/' . $post['media_url'] : '';
            $tags = !empty($post['tags']) ? explode(',', $post['tags']) : [];
            $userImg = !empty($post['pro_img'])
                ? '../profile/' . $post['pro_img']
                : '../profile/u_icon/default.png';
        ?>
        <div class="post"
            data-post-id="<?= htmlspecialchars($post['post_id']) ?>"
            data-img="<?= htmlspecialchars($img_url, ENT_QUOTES) ?>"
            data-text="<?= htmlspecialchars($post['content_text'], ENT_QUOTES) ?>"
            data-date="投稿日: <?= htmlspecialchars($post['created_at']) ?>"
            data-user="<?= htmlspecialchars($post['u_name']) ?>"
            data-user-img="<?= htmlspecialchars($userImg, ENT_QUOTES) ?>"
            data-likes="<?= (int)$post['like_count'] ?>"
            data-liked="<?= !empty($post['is_liked']) ? 1 : 0 ?>"
            data-comments="<?= (int)$post['comment_count'] ?>"
            data-tags="<?= htmlspecialchars($post['tags'] ?? '') ?>">

            <?php if ($img_url && file_exists($img_url)): ?>
                <img src="<?= htmlspecialchars($img_url, ENT_QUOTES) ?>">
            <?php endif; ?>

            <div class="post-body">
                <p class="post-text">
                    <?= nl2br(htmlspecialchars($post['content_text'])) ?>
                </p>
                <small>投稿日: <?= htmlspecialchars($post['created_at']) ?></small>

                <div class="post-tags">
                    <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                        <span class="tag" data-tag="<?= htmlspecialchars($tag) ?>">
                            #<?= htmlspecialchars($tag) ?>
                        </span>
                    <?php endforeach; ?>

                    <?php if (count($tags) > 2): ?>
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
            <h2>投稿がありません</h2>
        </div>
    <?php endif; ?>

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
        
    </div>
</main>
</body>
</html>
