<?php
session_start();

if (!isset($_SESSION['posts'])) {
    $_SESSION['posts'] = [];
}

// 投稿リセット処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $_SESSION['posts'] = [];
    // リダイレクトしてリロード問題を防止
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 投稿処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && !empty(trim($_POST['content']))) {
    $content = htmlspecialchars(trim($_POST['content']));
    $post = [
        'content' => $content,
        'time' => date('Y-m-d H:i:s')
    ];
    array_unshift($_SESSION['posts'], $post);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>簡易Twitter風タイムライン</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 2em auto;
            padding: 0 1em;
        }
        form {
            margin-bottom: 1em;
        }
        textarea {
            width: 100%;
            height: 60px;
            resize: none;
        }
        button {
            margin-top: 0.5em;
            padding: 0.5em 1em;
        }
        .post {
            border-bottom: 1px solid #ddd;
            padding: 0.5em 0;
        }
        .time {
            font-size: 0.8em;
            color: #555;
        }
        /*リセットボタン*/
        .reset-btn {
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            margin-left: 1em;
        }
    </style>
</head>
<body>
    <h1>簡易Twitter風タイムライン</h1>

    <form method="POST">
        <textarea name="content" placeholder="いまどうしてる？" maxlength="140" required></textarea>
        <br>
        <button type="submit">投稿</button>
        <button type="submit" name="reset" value="1" class="reset-btn" onclick="return confirm('投稿をリセットしますか？')">リセット</button>
    </form>

    <div class="timeline">
        <?php if (empty($_SESSION['posts'])): ?>
            <p>まだ投稿はありません。</p>
        <?php else: ?>
            <?php foreach ($_SESSION['posts'] as $post): ?>
                <div class="post">
                    <p><?= nl2br($post['content']) ?></p>
                    <div class="time"><?= $post['time'] ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
