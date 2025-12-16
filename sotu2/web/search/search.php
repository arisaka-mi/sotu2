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
SELECT *
FROM Post
WHERE visibility = 'public'
ORDER BY created_at DESC
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
            max-width: 500px;
            padding: 20px;
            border-radius: 16px;
            position: relative;
            animation: fadeIn 0.2s ease;
        }

        .modal-content img {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            margin-bottom: 12px;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 14px;
            font-size: 24px;
            cursor: pointer;
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

        <!-- おすすめ投稿 -->
        <h2>おすすめの投稿</h2>

        <?php if (!empty($recommended_posts)): ?>
            <div class="post-list">
                <?php foreach ($recommended_posts as $post): ?>
                    <div class="post">
                        <?php
                            $image_url = str_replace('../home/uploads/', '../search/uploads/', $post['media_url'] ?? '');
                        ?>
                        <?php if (!empty($post['media_url']) && file_exists($image_url)): ?>
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
                <img id="modalImg" src="" alt="">
                <p id="modalText"></p>
                <small id="modalDate"></small>
            </div>
        </div>

        <!-- ===== JavaScript ===== -->
        <script>
        const modal = document.getElementById('postModal');
        const modalImg = document.getElementById('modalImg');
        const modalText = document.getElementById('modalText');
        const modalDate = document.getElementById('modalDate');
        const closeBtn = document.querySelector('.modal-close');

        document.querySelectorAll('.post').forEach(post => {
            post.addEventListener('click', () => {
                modalImg.src = post.dataset.img || '';
                modalText.textContent = post.dataset.text || '';
                modalDate.textContent = post.dataset.date || '';
                modal.style.display = 'flex';
            });
        });

        // 閉じる
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // 背景クリック
        modal.addEventListener('click', e => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // ESCキー
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
            }
        });
        </script>

    </main>
</body>
</html>
