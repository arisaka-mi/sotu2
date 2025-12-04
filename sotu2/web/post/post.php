<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿画面</title>
</head>
<body>
    <header>
        <!--グローバルナビ実装予定-->
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

            <!-- タグ（複数選択） -->
            <label>タグ（カンマ区切り）：</label><br>
            <input type="text" name="tags" placeholder="メイク, スキンケア"><br><br>

            <button type="submit">投稿する</button>
        </form>
    </main>
</body>
</html>
