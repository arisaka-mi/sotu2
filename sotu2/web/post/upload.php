<?php
session_start();
require_once('../login/config.php'); // $pdo を作成済みと仮定

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$login_user_id = $_SESSION['user_id'];

$uploadDir = "../home/uploads/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $imageName = time() . "_" . basename($_FILES["image"]["name"]);
    $media_url = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $media_url)) {
        die("画像アップロードに失敗しました。");
    }

    $content_text = $_POST["content_text"] ?? "";
    $tags_input = $_POST["tags"] ?? ""; // カンマ区切り
    $tag_names = array_filter(array_map('trim', explode(',', $tags_input)));
    $created_at = date("Y-m-d");

    try {
        // 1. Postに登録
        $stmt = $pdo->prepare(
            "INSERT INTO Post (user_id, media_url, content_text, created_at, visibility)
             VALUES (:user_id, :media_url, :content_text, :created_at, :visibility)"
        );
        $stmt->execute([
            ':user_id' => $login_user_id,
            ':media_url' => $media_url,
            ':content_text' => $content_text,
            ':created_at' => $created_at,
            ':visibility' => 'public'
        ]);
        $post_id = $pdo->lastInsertId();


        // 2. タグ処理
        foreach ($tag_names as $tag_name) {
            // Tagに存在確認
            $stmtTag = $pdo->prepare("SELECT tag_id FROM Tag WHERE tag_name = :tag_name");
            $stmtTag->execute([':tag_name' => $tag_name]);
            $tag = $stmtTag->fetch();

            if ($tag) {
                $tag_id = $tag['tag_id'];
            } else {
                // 新しいタグ追加
                $stmtInsertTag = $pdo->prepare("INSERT INTO Tag (tag_name) VALUES (:tag_name)");
                $stmtInsertTag->execute([':tag_name' => $tag_name]);
                $tag_id = $pdo->lastInsertId();
            }

            // 3. posttagに登録
            $stmtPostTag = $pdo->prepare(
                "INSERT INTO posttag (post_id, tag_id) VALUES (:post_id, :tag_id)"
            );
            $stmtPostTag->execute([
                ':post_id' => $post_id,
                ':tag_id' => $tag_id
            ]);
        }

        echo "投稿が完了しました！<br>";
        echo "<a href='index.php'>戻る</a>";

    } catch (PDOException $e) {
        echo "データベースエラー：" . $e->getMessage();
    }
}
?>
