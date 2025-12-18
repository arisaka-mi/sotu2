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
        main {
            max-width: 800px;   /* 好きな横幅 */
            margin: 40px auto;  /* ← これで中央寄せ */
            padding: 0 16px;    /* 画面端対策（スマホ） */
        }

        /*投稿 */
        .post-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* 投稿カード */
        .post {
            aspect-ratio: 3 / 4;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;

            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .post:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }

        /* 投稿画像 */
        .post img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        /* 投稿文 */
        .post p {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: auto;

            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* 日付 */
        .post small {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        /* 検索バー */
        .text_kwd{
            box-sizing: border-box;
            position: relative;
            border: 1px solid #999;
            padding: 4px 40px 4px 12px;
            border-radius: 20px;
            height: 2.3em;
            width: 400px;
            overflow: hidden;
            background: #fff;
            margin: 0 auto 20px auto; /* ← 中央寄せ */
        }
        .text_kwd input[type="text"]{
            border: none;
            height: 100%;
            width: 100%;
            font-size: 14px;
        }
        .text_kwd input[type="text"]:focus {
            outline: none;
        }
        /* 検索ボタン（aタグ） */
        .text_kwd a {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }

        /* 検索アイコン画像 */
        .text_kwd .search_btn {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .post-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .post-list {
                grid-template-columns: 1fr;
            }
        }

        /* ===== モーダル ===== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;

            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 480px;
            padding: 20px;
            border-radius: 16px;
            position: relative;
            animation: fadeIn 0.2s ease;
        }

        .modal-content img {
            width: 100%;
            max-width: 480px;
            max-height: 600px;
            object-fit: cover;
            margin: 0 auto 12px;
            display: block;
            border-radius: 12px;
        }

        .modal-close {
            position: absolute;
            top: -14px;        /* ← 画像の外へ */
            right: -14px;
            width: 32px;
            height: 32px;

            background: #fff;
            border-radius: 50%;
            font-size: 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;
            z-index: 30;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
        }


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* 画像ラッパー */
        .modal-img-wrapper {
            position: relative;
        }

        /* 投稿主オーバーレイ */
        .modal-user-overlay {
            position: absolute;
            top: 12px;
            left: 12px;

            display: flex;
            align-items: center;
            gap: 8px;

            padding: 6px 10px;
            border-radius: 999px;

            background: rgba(0, 0, 0, 0.55);
            color: #fff;

            z-index: 10;
        }

        /* 投稿主アイコン */
        .modal-user-overlay img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ユーザー名 */
        .modal-user-overlay span {
            font-size: 14px;
            font-weight: bold;
            white-space: nowrap;
        }


        /* ===== いいね・コメント横並び ===== */
        /* ===== いいね画像ボタン ===== */
        .like-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .like-icon {
            width: 28px;
            height: 28px;
            display: block;
        }

        .like-btn:hover .like-icon {
            transform: scale(1.1);
        }

        .like-icon {
            transition: transform 0.15s ease;
        }

        /* ===== コメント画像ボタン ===== */
        .comment-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .comment-icon {
            width: 28px;
            height: 28px;
            display: block;
        }

        .comment-btn:hover .comment-icon {
            transform: scale(1.1);
        }

        .comment-icon {
            transition: transform 0.15s ease;
        }

        .post-actions {
            display: flex;
            align-items: center;
            gap: 12px;
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

        <?php if (!empty($recommended_posts)): ?>
        <div class="post-list">

            <?php foreach ($recommended_posts as $post): ?>
            <?php
            $image_url = '';
            if (!empty($post['media_url'])) {
                $image_url = str_replace('../home/uploads/', '../search/uploads/', $post['media_url']);
            }
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
            </div>

                <?php endforeach; ?>

        </div>
        <?php else: ?>
        <p>おすすめ投稿はありません。</p>
        <?php endif; ?>


        <!-- ===== モーダル ===== -->
        <div id="postModal" class="modal">
            <div class="modal-content">
                <span class="modal-close">&times;</span>

                <!-- 画像ラッパー -->
                <div class="modal-img-wrapper">

                    <!-- 投稿主（画像に重ねる） -->
                    <div class="modal-user-overlay">
                        <img id="modalUserImg">
                        <span id="modalUser"></span>
                    </div>

                    <!-- 投稿画像 -->
                    <img id="modalImg">
                </div>



                <!-- 本文 -->
                <p id="modalText"></p>
                <small id="modalDate"></small>

                <div class="post-actions">

                    <input type="hidden" id="modalPostIdLike">
                    <input type="hidden" id="modalPostIdComment">

                    <button type="button"
                        class="like-btn"
                        id="likeBtn"
                        data-liked="0">
                        <img src="../search/img/like_edge.PNG"
                            alt="いいね"
                            class="like-icon"
                            id="likeIcon">
                    </button>

                    <span id="modalLikes">0</span>

                    <button type="button"
                        class="comment-btn"
                        id="openCommentBtn"
                        data-liked="0">
                        <img src="../search/img/comment_edge.PNG"
                            alt="コメント"
                            class="comment-icon"
                            id="ComentIcon">
                    </button>

                    <span id="modalCommentsCount">0</span>

                </div>


                <form method="post" action="../home/add_comment.php"
                    id="commentForm"
                    style="display:none; margin-top:10px;">
                <input type="hidden" name="post_id" id="modalPostIdComment">
                <textarea name="comment" required placeholder="コメントを書く..."
                            style="width:100%;height:60px;"></textarea>
                <button type="submit">送信</button>
                </form>

                <!-- コメント一覧 -->
                <div id="modalCommentsArea" style="margin-top:10px;"></div>

            </div>
        </div>

        <!-- ===== JavaScript ===== -->
        <script>
        /* ===============================
        モーダル要素取得
        ================================ */
        const modal = document.getElementById('postModal');
        const modalImg = document.getElementById('modalImg');
        const modalText = document.getElementById('modalText');
        const modalDate = document.getElementById('modalDate');
        const modalUser = document.getElementById('modalUser');
        const modalUserImg = document.getElementById('modalUserImg');
        const modalLikes = document.getElementById('modalLikes');

        const closeBtn = document.querySelector('.modal-close');

        const commentForm = document.getElementById('commentForm');
        const openCommentBtn = document.getElementById('openCommentBtn');

        const likeBtn = document.getElementById('likeBtn');
        const likeIcon = document.getElementById('likeIcon');

        const modalPostIdLike = document.getElementById('modalPostIdLike');
        const modalPostIdComment = document.getElementById('modalPostIdComment');
        const modalCommentsCount = document.getElementById('modalCommentsCount');



        /* ===============================
        投稿クリック → モーダル表示
        ================================ */
        document.querySelectorAll('.post').forEach(post => {
            post.addEventListener('click', () => {

                const postId = post.dataset.postId;

                // 画像
                if (post.dataset.img) {
                    modalImg.src = post.dataset.img;
                    modalImg.style.display = 'block';
                } else {
                    modalImg.style.display = 'none';
                }

                // テキスト
                modalText.textContent = post.dataset.text || '';
                modalDate.textContent = post.dataset.date || '';
                modalUser.textContent = post.dataset.user || '';
                modalUserImg.src = post.dataset.userImg || '';

                // いいね数
                modalLikes.textContent = post.dataset.likes ?? 0;

                // コメント数 ← ★追加
                modalCommentsCount.textContent = post.dataset.comments ?? 0;

                // post_id
                modalPostIdLike.value = postId;
                modalPostIdComment.value = postId;

                commentForm.style.display = 'none';

                fetch(`../home/add_comment.php?post_id=${postId}`)
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('modalCommentsArea').innerHTML = html;
                    })
                    .catch(() => {
                        document.getElementById('modalCommentsArea').textContent =
                            'コメントの取得に失敗しました';
                    });

                modal.style.display = 'flex';
            });
        });



        /* ===============================
        いいねボタン
        ================================ */
        likeBtn.addEventListener('click', () => {

            const postId = modalPostIdLike.value;
            if (!postId) return;

            fetch('../home/toggle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {

                if (data.status !== 'ok') {
                    alert(data.message || 'いいね処理に失敗しました');
                    return;
                }

                // ハート画像切り替え
                likeIcon.src = data.liked
                    ? '../search/img/like_edge_2.PNG'
                    : '../search/img/like_edge.PNG';

                // いいね数更新
                modalLikes.textContent = data.like_count;

            })
            .catch(() => alert('通信エラー'));
        });

        document.querySelectorAll('.post').forEach(post => {
            post.addEventListener('click', () => {

                const postId = post.dataset.postId;

                // 既存処理は省略…

                // コメント数を表示
                modalCommentsCount.textContent =
                    post.dataset.comments ?? 0;

                modal.style.display = 'flex';
            });
        });



        /* ===============================
        コメントフォーム開閉
        ================================ */
        openCommentBtn.addEventListener('click', () => {
            commentForm.style.display =
                commentForm.style.display === 'none' ? 'block' : 'none';
        });


        /* ===============================
        モーダルを閉じる
        ================================ */
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        modal.addEventListener('click', e => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
            }
        });
        </script>

    </main>
</body>
</html>