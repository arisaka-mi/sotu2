<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>美容SNS - 投稿</title>
</head>
<body>

<h1>新規投稿</h1>

<form action="upload.php" method="post" enctype="multipart/form-data">

    <label>画像を選択：</label><br>
    <input type="file" name="image" required><br><br>

    <label>説明文：</label><br>
    <textarea name="description" rows="4" cols="40"></textarea><br><br>

    <label>タグ（カンマ区切り）：</label><br>
    <input type="text" name="tags" placeholder="メイク, スキンケア"><br><br>

    <button type="submit">投稿する</button>
</form>

<br><br>
<button onclick="alert('シェア機能は後で実装します！')">シェアする</button>

</body>
</html>
