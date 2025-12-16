<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿画面</title>
    <style>
    </style>
</head>
<body>
    <header>
        <?php include '../navigation/nav.php'; ?>
    </header>
    <main>
        <h1>新規投稿</h1>

        <form action="upload.php" method="post" enctype="multipart/form-data">
            <!-- 画像 -->
            <label>画像を選択：</label><br>
            <input type="file" name="image" required><br><br>

            <!-- 説明文 -->
            <label>説明文：</label><br>
            <textarea name="content_text" rows="4" cols="40"></textarea><br><br>

            <!-- タグ（複数入力） -->
            <label>タグ（カンマ区切り）：</label><br>
            <input type="text" name="tags" placeholder="メイク, スキンケア"><br><br>

            <!-- 隠しフィールドで公開範囲を設定 -->
            <input type="hidden" name="visibility" value="public" id="visibilityInput">

            <!-- 投稿ボタン -->
            <button type="submit" onclick="document.getElementById('visibilityInput').value='public'">
                Public 投稿
            </button>
            <button type="submit" onclick="document.getElementById('visibilityInput').value='friends'">
                Friends 投稿
            </button>
        </form>
    </main>
</body>
</html>
