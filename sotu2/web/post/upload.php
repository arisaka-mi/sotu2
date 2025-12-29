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

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $upload_dir = '../home/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = time() . '_' . basename($_FILES['image']['name']);
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        $media_url = $filepath; // 成功したらパスをセット
    } else {
        // アップロード失敗時に詳細を表示
        echo '<pre>';
        var_dump($_FILES['image']);
        die('画像アップロードに失敗しました。アップロード先ディレクトリの権限を確認してください。');
    }

} elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    // ファイルが送信されているがエラーの場合
    die('ファイルアップロードエラー：' . $_FILES['image']['error']);
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

$post_id = $pdo->lastInsertId();

if (!empty($tags_input)) {

    // 例: "#cat #dog,food" → ["cat","dog","food"]
    $tags = preg_split('/[\s,#]+/', $tags_input, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($tags as $tag_name) {

        // 前後の空白除去
        $tag_name = trim($tag_name);
        if ($tag_name === '') continue;

        // ① Tag テーブルに存在するか確認
        $stmt = $pdo->prepare(
            "SELECT tag_id FROM Tag WHERE tag_name = :tag_name"
        );
        $stmt->execute([':tag_name' => $tag_name]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tag) {
            $tag_id = $tag['tag_id'];
        } else {
            // ② なければ Tag に追加
            $stmt = $pdo->prepare(
                "INSERT INTO Tag (tag_name) VALUES (:tag_name)"
            );
            $stmt->execute([':tag_name' => $tag_name]);
            $tag_id = $pdo->lastInsertId();
        }

        // ③ PostTag に紐付け（複合PK対応）
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO PostTag (post_id, tag_id)
             VALUES (:post_id, :tag_id)"
        );
        $stmt->execute([
            ':post_id' => $post_id,
            ':tag_id'  => $tag_id
        ]);
    }
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
