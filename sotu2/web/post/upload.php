<?php
session_start();
require_once('../login/config.php'); // $pdo

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$login_user_id = $_SESSION['user_id'];

// アップロード先ディレクトリ
$homeDir   = "../home/uploads/";
$searchDir = "../search/uploads/";

// ディレクトリが存在しない場合は作成
if (!file_exists($homeDir)) mkdir($homeDir, 0777, true);
if (!file_exists($searchDir)) mkdir($searchDir, 0777, true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 画像ファイルの名前
    $imageName = time() . "_" . basename($_FILES["image"]["name"]);

    // home にアップロード
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $homeDir . $imageName)) {
        die("画像アップロードに失敗しました。");
    }

    // search にコピー
    if (!copy($homeDir . $imageName, $searchDir . $imageName)) {
        die("search用ディレクトリへのコピーに失敗しました。");
    }

    // DBに保存する値
    $media_url    = $homeDir . $imageName;  // home 側パスを保存
    $content_text = $_POST["content_text"] ?? "";
    $tags_input   = $_POST["tags"] ?? "";   // カンマ区切り
    $visibility   = $_POST["visibility"] ?? "public"; // デフォルト public
    $tag_names    = array_filter(array_map('trim', explode(',', $tags_input)));
    $created_at   = date("Y-m-d");

    try {
        // 1. Postに登録
        $stmt = $pdo->prepare(
            "INSERT INTO Post (user_id, media_url, content_text, created_at, visibility)
             VALUES (:user_id, :media_url, :content_text, :created_at, :visibility)"
        );
        $stmt->execute([
            ':user_id'      => $login_user_id,
            ':media_url'    => $media_url,
            ':content_text' => $content_text,
            ':created_at'   => $created_at,
            ':visibility'   => $visibility
        ]);
        $post_id = $pdo->lastInsertId();

        // 2. タグ処理
        foreach ($tag_names as $tag_name) {
            // タグが既に存在するか確認
            $stmtTag = $pdo->prepare("SELECT tag_id FROM Tag WHERE tag_name = :tag_name");
            $stmtTag->execute([':tag_name' => $tag_name]);
            $tag = $stmtTag->fetch();

            if ($tag) {
                $tag_id = $tag['tag_id'];
            } else {
                // 新しいタグを追加
                $stmtInsertTag = $pdo->prepare("INSERT INTO Tag (tag_name) VALUES (:tag_name)");
                $stmtInsertTag->execute([':tag_name' => $tag_name]);
                $tag_id = $pdo->lastInsertId();
            }

            // posttag に登録
            $stmtPostTag = $pdo->prepare(
                "INSERT INTO posttag (post_id, tag_id) VALUES (:post_id, :tag_id)"
            );
            $stmtPostTag->execute([
                ':post_id' => $post_id,
                ':tag_id'  => $tag_id
            ]);
        }

        // ✅ 投稿完了後に timeline_public.php にリダイレクト
        header("Location: ../home/timeline_public.php");
        exit(); // header後は必ず exit

    } catch (PDOException $e) {
        echo "データベースエラー：" . $e->getMessage();
    }
}
?>
