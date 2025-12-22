<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>投稿画面</title>

<style>
/* ===== 全体 ===== */
body {
    margin: 0;
    font-family: "Helvetica", "Arial", sans-serif;
    background: linear-gradient(135deg, #fde2e4, #e0f4ff);
}

/* ===== グローバルナビ分 ===== */
main {
    margin-left: 20%;
    padding: 40px;
}

/* ===== タイトル ===== */
h1 {
    text-align: center;
    color: #f48fb1;
    margin-bottom: 32px;
}

/* ===== レイアウト ===== */
.post-layout {
    display: flex;
    gap: 32px;
    max-width: 1100px;
    margin: 0 auto;
}

/* ===============================
   左：画像アップロード枠
================================ */
.image-area {
    flex: 1;
    background: #f2f2f2;
    border-radius: 24px;
    padding: 0;
    position: relative;
    overflow: hidden;

    /* ★ サイズ固定（正方形） */
    aspect-ratio: 1 / 1;
}

/* ファイル入力を隠す */
.image-area input[type="file"] {
    display: none;
}

/* プラスボタン */
.upload-label {
    position: absolute;
    inset: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
}

.upload-circle {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px dashed #bdbdbd;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 64px;
    color: #bdbdbd;
    transition: transform 0.2s ease;
}

.upload-circle:hover {
    transform: scale(1.05);
}

/* ===== プレビュー画像 ===== */
#preview {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;      /* ★ 枠いっぱい */
    border-radius: 24px;    /* ★ グレー枠と完全一致 */
    display: none;
}

/* ===============================
   右：入力欄
================================ */
.form-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.tag-input {
    width: 100%;
    padding: 14px 18px;
    border-radius: 18px;
    border: none;
    font-size: 15px;
    background: #f2f2f2;
    outline: none;
}

.content-text {
    width: 100%;
    min-height: 180px;
    padding: 18px;
    border-radius: 22px;
    border: none;
    font-size: 15px;
    background: #f2f2f2;
    outline: none;
    resize: vertical;
}

/* ===============================
   シェアボタン
================================ */
.share-buttons {
    display: flex;
    gap: 18px;
    margin-top: 8px;
}

.share-buttons button {
    flex: 1;
    padding: 14px 0;
    border-radius: 999px;
    border: none;
    font-size: 15px;
    color: #ffffff;
    cursor: pointer;
}

.public-btn {
    background: #90caf9;
}

.friends-btn {
    background: #f48fb1;
}
</style>
</head>

<body>

<?php include '../navigation/nav.php'; ?>

<main>

<h1>新規投稿</h1>

<form action="upload.php" method="post" enctype="multipart/form-data">
<div class="post-layout">

    <!-- 左：画像アップロード -->
    <div class="image-area">

        <label class="upload-label" id="uploadLabel">
            <div class="upload-circle">
                <span id="plus-icon">＋</span>
            </div>
            <input
                type="file"
                name="image"
                accept="image/*"
                onchange="previewImage(this)"
                required
            >
        </label>

        <!-- プレビュー -->
        <img id="preview">
    </div>

    <!-- 右：入力欄 -->
    <div class="form-area">

        <input
            type="text"
            name="tags"
            class="tag-input"
            placeholder="タグ（カンマ区切り：メイク,スキンケア）"
        >

        <textarea
            name="content_text"
            class="content-text"
            placeholder="投稿文を入力してください"
        ></textarea>

        <input type="hidden" name="visibility" value="public" id="visibilityInput">

        <div class="share-buttons">
            <button
                type="submit"
                class="public-btn"
                onclick="document.getElementById('visibilityInput').value='public'">
                全体公開
            </button>

            <button
                type="submit"
                class="friends-btn"
                onclick="document.getElementById('visibilityInput').value='friends'">
                フォロワー公開
            </button>
        </div>

    </div>
</div>
</form>

</main>

<!-- ===============================
     画像プレビューJS
================================ -->
<script>
function previewImage(input) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function(e) {
        const preview = document.getElementById('preview');
        const uploadLabel = document.getElementById('uploadLabel');

        preview.src = e.target.result;
        preview.style.display = 'block';

        // プラス＆アップロードUIを完全に消す
        uploadLabel.querySelector('.upload-circle').style.display = 'none';

        // input.disabled = true; ← 削除
    };

    reader.readAsDataURL(file);
}
</script>

</body>
</html>
