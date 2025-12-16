<?php
session_start();

$posts   = $_SESSION['posts'] ?? [];
$keyword = $_SESSION['keyword'] ?? '';

// セッションをクリアしておく
unset($_SESSION['posts']);
unset($_SESSION['keyword']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>検索結果</title>
    <style>
        main {
            max-width: 800px;   /* 好きな横幅 */
            margin: 40px auto;  /* ← これで中央寄せ */
            padding: 0 16px;    /* 画面端対策（スマホ） */
        }

        .post {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .post img {
            max-width: 200px;
            display: block;
            margin-bottom: 5px;
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

    </style>
</head>
<body>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<main>
    <form method="get" action="search_control.php" class="text_kwd">
        <input type="text" size="25" placeholder="キーワード検索">
        <a href="search_hit.php" data-title="search">
            <img src="../search/img/search_edge.PNG" alt="search" class="search_btn">
        </a>
    </form>


    <h2>検索ワード：<?= htmlspecialchars($keyword) ?></h2>

    <?php if (empty($posts)): ?>
        <p>見つかりませんでした。</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <?php
                    // DBに保存されている media_url を search/uploads に置換
                    $image_url = str_replace('../home/uploads/', '../search/uploads/', $post['media_url'] ?? '');
                ?>
                <?php if (!empty($post['media_url']) && file_exists($image_url)): ?>
                    <img src="<?= htmlspecialchars($image_url) ?>" alt="投稿画像">
                <?php endif; ?>

                <p><?= nl2br(htmlspecialchars($post['content_text'] ?? '内容なし')) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="search.php">← 検索に戻る</a>
</main>
</body>
</html>
