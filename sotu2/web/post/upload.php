<?php
session_start();
require_once('../login/config.php');

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   POSTデータ取得
========================= */
$content_text = $_POST['content_text'] ?? '';
$tags_input   = $_POST['tags'] ?? '';
$visibility   = $_POST['visibility'] ?? 'public';

/* =========================
   画像アップロード
========================= */
$media_url = null;

if (!empty($_FILES['image']['name'])) {
    $upload_dir = '../home/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = time() . '_' . basename($_FILES['image']['name']);
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        die('画像アップロードに失敗しました');
    }

    $media_url = $filepath;
}

/* =========================
   DB保存
========================= */
try {
    $sql = "
        INSERT INTO Post (user_id, media_url, content_text, visibility, created_at)
        VALUES (:uid, :media, :text, :visibility, NOW())
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid'        => $user_id,
        ':media'      => $media_url,
        ':text'       => $content_text,
        ':visibility' => $visibility
    ]);

} catch (PDOException $e) {
    die('DBエラー：' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>投稿完了</title>
<meta http-equiv="refresh" content="3;url=../home/timeline_public.php">
<style>
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #f5f5f5;
    font-family: sans-serif;
}
.box {
    background: white;
    padding: 32px 40px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.box h1 {
    margin-bottom: 10px;
    font-size: 20px;
}
.box p {
    color: #666;
    font-size: 14px;
}
</style>
</head>
<body>

<div class="box">
    <h1>投稿が完了しました！</h1>
    <p>3秒後にホームへ戻ります</p>
</div>

</body>
</html>
